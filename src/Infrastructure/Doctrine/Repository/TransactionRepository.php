<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\ValueObject\Montant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository implements TransactionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?Transaction
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /** @return list<Transaction> */
    public function findAll(): array
    {
        return $this->parDateDecroissante()
            ->getQuery()
            ->getResult();
    }

    /** @return list<Transaction> */
    public function findByPointVente(PointVente $pointVente): array
    {
        return $this->parDateDecroissante()
            ->andWhere('t.pointVente = :pdv')
            ->setParameter('pdv', $pointVente)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Transaction> */
    public function findByUtilisateur(Utilisateur $utilisateur): array
    {
        return $this->parDateDecroissante()
            ->andWhere('t.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Transaction> */
    public function findByType(TypeTransaction $type): array
    {
        return $this->parDateDecroissante()
            ->andWhere('t.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Transaction> */
    public function findByStatut(StatutTransaction $statut): array
    {
        return $this->parDateDecroissante()
            ->andWhere('t.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Transaction> */
    public function findEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        return $this->parDateDecroissante()
            ->andWhere('t.dateTransac BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
    }

    /** @return list<int> */
    public function findPdvIdsVisitesEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('DISTINCT IDENTITY(t.pointVente)')
            ->andWhere('t.type = :type')
            ->andWhere('t.pointVente IS NOT NULL')
            ->andWhere('t.dateTransac BETWEEN :debut AND :fin')
            ->setParameter('type', TypeTransaction::VISITE)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $result);
    }

    /** @return list<Transaction> */
    public function findVisitesEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        return $this->parDateDecroissante()
            ->andWhere('t.type = :type')
            ->andWhere('t.dateTransac BETWEEN :debut AND :fin')
            ->setParameter('type', TypeTransaction::VISITE)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Transaction> */
    public function findVisitesParAgent(Utilisateur $agent, \DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        return $this->parDateDecroissante()
            ->andWhere('t.type = :type')
            ->andWhere('t.utilisateur = :agent')
            ->andWhere('t.dateTransac BETWEEN :debut AND :fin')
            ->setParameter('type', TypeTransaction::VISITE)
            ->setParameter('agent', $agent)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Transaction> */
    public function findByFiltres(
        ?TypeTransaction $type = null,
        ?PointVente $pointVente = null,
        ?Utilisateur $agent = null,
        ?\DateTimeImmutable $debut = null,
        ?\DateTimeImmutable $fin = null,
        ?Montant $montantMin = null,
        ?Montant $montantMax = null,
    ): array {
        $qb = $this->parDateDecroissante();

        if (null !== $type) {
            $qb->andWhere('t.type = :type')->setParameter('type', $type);
        }

        if (null !== $pointVente) {
            $qb->andWhere('t.pointVente = :pdv')->setParameter('pdv', $pointVente);
        }

        if (null !== $agent) {
            $qb->andWhere('t.utilisateur = :agent')->setParameter('agent', $agent);
        }

        if (null !== $debut) {
            $qb->andWhere('t.dateTransac >= :debut')->setParameter('debut', $debut);
        }

        if (null !== $fin) {
            $qb->andWhere('t.dateTransac <= :fin')->setParameter('fin', $fin);
        }

        if (null !== $montantMin) {
            $qb->andWhere('t.montant >= :montantMin')->setParameter('montantMin', $montantMin->toDecimal());
        }

        if (null !== $montantMax) {
            $qb->andWhere('t.montant <= :montantMax')->setParameter('montantMax', $montantMax->toDecimal());
        }

        return $qb->getQuery()->getResult();
    }

    public function chiffreAffaires(
        PointVente $pointVente,
        ?\DateTimeImmutable $debut = null,
        ?\DateTimeImmutable $fin = null,
    ): Montant {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.montant)')
            ->andWhere('t.pointVente = :pdv')
            ->andWhere('t.type = :type')
            ->andWhere('t.statut = :statut')
            ->setParameter('pdv', $pointVente)
            ->setParameter('type', TypeTransaction::VENTE)
            ->setParameter('statut', StatutTransaction::VALIDEE);

        if (null !== $debut) {
            $qb->andWhere('t.dateTransac >= :debut')->setParameter('debut', $debut);
        }

        if (null !== $fin) {
            $qb->andWhere('t.dateTransac <= :fin')->setParameter('fin', $fin);
        }

        $somme = $qb->getQuery()->getSingleScalarResult();

        return null === $somme ? Montant::zero() : Montant::fromString((string) $somme);
    }

    public function save(Transaction $transaction, bool $flush = true): void
    {
        $this->getEntityManager()->persist($transaction);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $transaction, bool $flush = true): void
    {
        $this->getEntityManager()->remove($transaction);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPendingApprovisionnementsForAgent(Utilisateur $agent): array
    {
        return $this->parDateDecroissante()
            ->andWhere('t.type = :type')
            ->andWhere('t.statut = :statut')
            ->andWhere('t.pointVente IN (
                SELECT IDENTITY(a.pointVente)
                FROM App\Domain\Entity\AttributionPdv a
                WHERE a.agent = :agent
                AND a.actif = true
            )')
            ->setParameter('type', TypeTransaction::APPROVISIONNEMENT_FLOTTE)
            ->setParameter('statut', StatutTransaction::EN_ATTENTE)
            ->setParameter('agent', $agent)
            ->getQuery()
            ->getResult();
    }

    private function parDateDecroissante(): QueryBuilder
    {
        return $this->createQueryBuilder('t')->orderBy('t.dateTransac', 'DESC');
    }
}
