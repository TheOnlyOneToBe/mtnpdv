<?php

declare(strict_types=1);

namespace App\Application\Supervision;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Agrège les données de supervision opérationnelle pour l'administrateur :
 * carte PDV, suivi agents, transactions filtrées, alertes de solde.
 */
class SupervisionService
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly TransactionRepositoryInterface $transactions,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        #[Autowire(param: 'app.rayon_tolerance_metres')]
        private readonly int $rayonToleranceMetres,
    ) {
    }

    /**
     * @return array{
     *     totalPdv: int,
     *     pdvVisites: int,
     *     pdvNonVisites: int,
     *     pdvSoldeCritique: int,
     *     totalTransactions: int,
     *     visitesPeriode: int,
     *     agentsActifs: int,
     *     alertesSolde: list<array{pdv: PointVente, cashSousSeuil: bool, flotteSousSeuil: bool}>,
     *     dernieresVisites: list<array{transaction: Transaction, distanceMetres: float, gpsConfirme: bool}>
     * }
     */
    public function getDashboardData(int $periodeJours = 30): array
    {
        $debut = (new \DateTimeImmutable())->modify(sprintf('-%d days', $periodeJours))->setTime(0, 0);
        $fin = new \DateTimeImmutable();

        $pointVentes = $this->pointVentes->findAll();
        $pdvVisitesIds = $this->transactions->findPdvIdsVisitesEntre($debut, $fin);

        $pdvVisites = 0;
        $pdvSoldeCritique = 0;
        $alertesSolde = [];

        foreach ($pointVentes as $pdv) {
            if (in_array($pdv->getId(), $pdvVisitesIds, true)) {
                ++$pdvVisites;
            }

            $cashSousSeuil = $pdv->soldeCashEstSousSeuil();
            $flotteSousSeuil = $pdv->soldeFlotteEstSousSeuil();

            if ($cashSousSeuil || $flotteSousSeuil) {
                ++$pdvSoldeCritique;
                $alertesSolde[] = [
                    'pdv' => $pdv,
                    'cashSousSeuil' => $cashSousSeuil,
                    'flotteSousSeuil' => $flotteSousSeuil,
                ];
            }
        }

        $visites = $this->transactions->findVisitesEntre($debut, $fin);
        $agents = $this->utilisateurs->findByRole('AGENT');

        $dernieresVisites = [];
        foreach (array_slice($visites, 0, 10) as $transaction) {
            $dernieresVisites[] = $this->enrichirAvecGps($transaction);
        }

        return [
            'totalPdv' => count($pointVentes),
            'pdvVisites' => $pdvVisites,
            'pdvNonVisites' => count($pointVentes) - $pdvVisites,
            'pdvSoldeCritique' => $pdvSoldeCritique,
            'totalTransactions' => count($this->transactions->findAll()),
            'visitesPeriode' => count($visites),
            'agentsActifs' => count($agents),
            'alertesSolde' => $alertesSolde,
            'dernieresVisites' => $dernieresVisites,
            'periodeJours' => $periodeJours,
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     nom: string,
     *     ville: string,
     *     lat: float,
     *     lng: float,
     *     statut: string,
     *     soldeCash: string,
     *     soldeFlotte: string,
     *     seuilMinCash: string,
     *     seuilMinFlotte: string,
     *     visite: bool,
     *     soldeCritique: bool,
     *     couleur: string
     * }>
     */
    public function getPdvMapData(int $periodeJours = 30): array
    {
        $debut = (new \DateTimeImmutable())->modify(sprintf('-%d days', $periodeJours))->setTime(0, 0);
        $fin = new \DateTimeImmutable();
        $pdvVisitesIds = $this->transactions->findPdvIdsVisitesEntre($debut, $fin);

        $markers = [];
        foreach ($this->pointVentes->findAll() as $pdv) {
            $soldeCritique = $pdv->soldeCashEstSousSeuil() || $pdv->soldeFlotteEstSousSeuil();
            $visite = in_array($pdv->getId(), $pdvVisitesIds, true);

            $couleur = match (true) {
                $soldeCritique => 'red',
                $visite => 'green',
                default => 'gray',
            };

            $coords = $pdv->getCoordonnees();
            $markers[] = [
                'id' => $pdv->getId(),
                'nom' => $pdv->getNomPdv(),
                'ville' => $pdv->getVille(),
                'codeRef' => $pdv->getCodeRef(),
                'lat' => $coords->latitude(),
                'lng' => $coords->longitude(),
                'statut' => $pdv->getStatutActuel()->value,
                'soldeCash' => $pdv->getSoldeCash()->toDecimal(),
                'soldeFlotte' => $pdv->getSoldeFlotte()->toDecimal(),
                'seuilMinCash' => $pdv->getSeuilMinCash()->toDecimal(),
                'seuilMinFlotte' => $pdv->getSeuilMinFlotte()->toDecimal(),
                'visite' => $visite,
                'soldeCritique' => $soldeCritique,
                'couleur' => $couleur,
            ];
        }

        return $markers;
    }

    /**
     * @return list<array{
     *     agent: Utilisateur,
     *     totalVisites: int,
     *     visitesValidees: int,
     *     visitesEnAttente: int,
     *     derniereVisite: ?\DateTimeImmutable
     * }>
     */
    public function getActiviteAgents(?\DateTimeImmutable $debut = null, ?\DateTimeImmutable $fin = null): array
    {
        $debut ??= (new \DateTimeImmutable())->modify('-30 days')->setTime(0, 0);
        $fin ??= new \DateTimeImmutable();

        $activite = [];
        foreach ($this->utilisateurs->findByRole('AGENT') as $agent) {
            $visites = $this->transactions->findVisitesParAgent($agent, $debut, $fin);

            $validees = 0;
            $enAttente = 0;
            $derniereVisite = null;

            foreach ($visites as $visite) {
                if ($visite->getStatut()->value === 'VALIDEE') {
                    ++$validees;
                } elseif ($visite->getStatut()->value === 'EN_ATTENTE') {
                    ++$enAttente;
                }

                if (null === $derniereVisite || $visite->getDateTransac() > $derniereVisite) {
                    $derniereVisite = $visite->getDateTransac();
                }
            }

            $activite[] = [
                'agent' => $agent,
                'totalVisites' => count($visites),
                'visitesValidees' => $validees,
                'visitesEnAttente' => $enAttente,
                'derniereVisite' => $derniereVisite,
            ];
        }

        usort($activite, fn ($a, $b) => $b['totalVisites'] <=> $a['totalVisites']);

        return $activite;
    }

    /**
     * @return list<array{transaction: Transaction, distanceMetres: float, gpsConfirme: bool}>
     */
    public function getHistoriqueAgent(Utilisateur $agent, ?\DateTimeImmutable $debut = null, ?\DateTimeImmutable $fin = null): array
    {
        $debut ??= (new \DateTimeImmutable())->modify('-30 days')->setTime(0, 0);
        $fin ??= new \DateTimeImmutable();

        $historique = [];
        foreach ($this->transactions->findVisitesParAgent($agent, $debut, $fin) as $transaction) {
            $historique[] = $this->enrichirAvecGps($transaction);
        }

        return $historique;
    }

    /**
     * @return list<Transaction>
     */
    public function getTransactionsFiltrees(SupervisionFiltreTransaction $filtre): array
    {
        return $this->transactions->findByFiltres(
            type: $filtre->type,
            pointVente: $filtre->pointVente,
            agent: $filtre->agent,
            debut: $filtre->debut,
            fin: $filtre->fin,
            montantMin: $filtre->montantMin,
            montantMax: $filtre->montantMax,
        );
    }

    /**
     * @return array{transaction: Transaction, distanceMetres: float, gpsConfirme: bool}
     */
    private function enrichirAvecGps(Transaction $transaction): array
    {
        $pdv = $transaction->getPointVente();
        $distanceMetres = 0.0;
        $gpsConfirme = false;

        if ($pdv) {
            $distanceMetres = round(
                $transaction->getCoordonneesCapture()->distanceVers($pdv->getCoordonnees()) * 1000,
                1,
            );
            $gpsConfirme = $distanceMetres <= $this->rayonToleranceMetres;
        }

        return [
            'transaction' => $transaction,
            'distanceMetres' => $distanceMetres,
            'gpsConfirme' => $gpsConfirme,
        ];
    }
}
