<?php

declare(strict_types=1);

namespace App\Controller\Gerant;

use App\Application\Gerant\EnregistrerVenteCommande;
use App\Application\Gerant\EnregistrerVenteHandler;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\ProduitRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GERANT')]
#[Route('/gerant', name: 'app_gerant_')]
class GerantController extends AbstractController
{
    public function __construct(
        private PointVenteRepositoryInterface $pointVenteRepository,
        private ProduitRepositoryInterface $produitRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private EnregistrerVenteHandler $enregistrerVenteHandler,
    ) {}

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $gerant = $user;

        if (!$gerant || !$gerant->aLeRole('GERANT')) {
            throw $this->createAccessDeniedException();
        }

        $pointVente = $this->pointVenteRepository->findByGerant($gerant)[0] ?? null;

        $data = [
            'gerant' => $gerant,
            'pointVente' => $pointVente,
        ];

        if ($pointVente) {
            $produits = $this->produitRepository->findLivresAuPointVente($pointVente);
            $allTransactions = $this->transactionRepository->findByPointVente($pointVente);
            $transactions = array_slice($allTransactions, 0, 10);

            $data['produits'] = $produits;
            $data['transactions'] = $transactions;
            $data['statistiques'] = [
                'totalProduits' => count($produits),
                'totalTransactions' => count($transactions),
                'chiffreAffaires' => $this->calculerChiffreAffaires($transactions),
                'transactionsValidees' => count(array_filter($transactions, fn($t) => $t->getStatut()->value === 'VALIDEE')),
                'transactionsEnAttente' => count(array_filter($transactions, fn($t) => $t->getStatut()->value === 'EN_ATTENTE')),
            ];
        }

        return $this->render('gerant/dashboard.html.twig', $data);
    }

    #[Route('/produits', name: 'produits_list')]
    public function listProduits(Request $request): Response
    {
        $user = $this->getUser();
        $gerant = $user;

        if (!$gerant || !$gerant->aLeRole('GERANT')) {
            throw $this->createAccessDeniedException();
        }

        $pointVente = $this->pointVenteRepository->findByGerant($gerant)[0] ?? null;

        if (!$pointVente) {
            throw $this->createAccessDeniedException('Aucun kiosque assigné');
        }

        $recherche = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');
        $prix_min = $request->query->get('prix_min', '');
        $prix_max = $request->query->get('prix_max', '');

        $produits = $this->produitRepository->findLivresAuPointVente($pointVente);

        // findLivresAuPointVente() retourne des lignes {produit, quantiteLivree}
        if ($recherche) {
            $produits = array_filter($produits, fn(array $ligne) =>
                stripos($ligne['produit']->getNomProduit(), $recherche) !== false
            );
        }

        if ($categorie) {
            $produits = array_filter($produits, fn(array $ligne) =>
                $ligne['produit']->getCategorieProduit()?->getNomCategorie() === $categorie
            );
        }

        if ($prix_min !== '') {
            $min = (int)$prix_min;
            $produits = array_filter($produits, fn(array $ligne) =>
                $ligne['produit']->getPrix()->montantCentimes() >= $min * 100
            );
        }

        if ($prix_max !== '') {
            $max = (int)$prix_max;
            $produits = array_filter($produits, fn(array $ligne) =>
                $ligne['produit']->getPrix()->montantCentimes() <= $max * 100
            );
        }

        $categories = [];
        foreach ($this->produitRepository->findAll() as $produit) {
            $cat = $produit->getCategorieProduit()?->getNomCategorie();
            if ($cat && !in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }

        return $this->render('gerant/produits_list.html.twig', [
            'pointVente' => $pointVente,
            'produits' => $produits,
            'categories' => $categories,
            'search' => $recherche,
            'categorie' => $categorie,
            'prix_min' => $prix_min,
            'prix_max' => $prix_max,
        ]);
    }

    #[Route('/vente/new', name: 'vente_new', methods: ['GET', 'POST'])]
    public function newVente(Request $request): Response
    {
        $user = $this->getUser();
        $gerant = $user;

        if (!$gerant || !$gerant->aLeRole('GERANT')) {
            throw $this->createAccessDeniedException();
        }

        $pointVente = $this->pointVenteRepository->findByGerant($gerant)[0] ?? null;

        if (!$pointVente) {
            throw $this->createAccessDeniedException('Aucun kiosque assigné');
        }

        if ('POST' === $request->getMethod()) {
            $produitId = (int)$request->request->get('produit_id');
            $quantite = (int)$request->request->get('quantite', 1);
            $montantCentimes = (int)$request->request->get('montant_centimes');
            $latitude = (float)$request->request->get('latitude', $pointVente->getCoordonnees()->latitude());
            $longitude = (float)$request->request->get('longitude', $pointVente->getCoordonnees()->longitude());
            $commentaire = $request->request->get('commentaire');

            try {
                $commande = new EnregistrerVenteCommande(
                    $pointVente->getId(),
                    $produitId,
                    $quantite,
                    $montantCentimes,
                    $latitude,
                    $longitude,
                    $commentaire,
                );

                $vente = $this->enregistrerVenteHandler->handle($commande);

                $this->addFlash('success', sprintf(
                    'Vente validée avec succès - %s FCFA (Transaction #%d)',
                    number_format($vente->getMontant()->montantCentimes() / 100, 0, ',', ' '),
                    $vente->getId()
                ));

                return $this->redirectToRoute('app_gerant_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement: ' . $e->getMessage());
            }
        }

        $produits = $this->produitRepository->findLivresAuPointVente($pointVente);

        return $this->render('gerant/vente_form.html.twig', [
            'pointVente' => $pointVente,
            'produits' => $produits,
        ]);
    }

    #[Route('/transactions', name: 'transactions')]
    public function listTransactions(Request $request): Response
    {
        $user = $this->getUser();
        $gerant = $user;

        if (!$gerant || !$gerant->aLeRole('GERANT')) {
            throw $this->createAccessDeniedException();
        }

        $pointVente = $this->pointVenteRepository->findByGerant($gerant)[0] ?? null;

        if (!$pointVente) {
            throw $this->createAccessDeniedException('Aucun kiosque assigné');
        }

        $statut = $request->query->get('statut', '');
        $type = $request->query->get('type', '');
        $limit = (int)$request->query->get('limit', 50);

        $allTransactions = $this->transactionRepository->findByPointVente($pointVente);
        $transactions = array_slice($allTransactions, 0, $limit);

        if ($statut) {
            $transactions = array_filter($transactions, fn($t) =>
                $t->getStatut()->value === $statut
            );
        }

        if ($type) {
            $transactions = array_filter($transactions, fn($t) =>
                $t->getType()->value === $type
            );
        }

        return $this->render('gerant/transactions_list.html.twig', [
            'pointVente' => $pointVente,
            'transactions' => array_values($transactions),
            'statut' => $statut,
            'type' => $type,
        ]);
    }

    private function calculerChiffreAffaires(array $transactions): float
    {
        $total = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->getStatut()->value === 'VALIDEE' && $transaction->getType()->value === 'VENTE') {
                $total += $transaction->getMontant()->montantCentimes() / 100;
            }
        }
        return $total;
    }
}
