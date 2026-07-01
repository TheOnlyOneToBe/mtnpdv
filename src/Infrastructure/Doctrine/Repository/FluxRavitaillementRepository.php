<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\FluxRavitaillement;
use App\Domain\Entity\PointVente;
use App\Domain\Enum\StatutFlux;
use App\Domain\Repository\FluxRavitaillementRepositoryInterface;
use App\Domain\ValueObject\Montant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FluxRavitaillement>
 */
class FluxRavitaillementRepository extends ServiceEntityRepository implements FluxRavitaillementRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FluxRavitaillement::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?FluxRavitaillement
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findOneByFacture(string $factureUniq): ?FluxRavitaillement
    {
        return $this->findOneBy(['factureUniq' => $factureUniq]);
    }

    /** @return list<FluxRavitaillement> */
    public function findByStatut(StatutFlux $statut): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.statutFlux = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<FluxRavitaillement> */
    public function findByPointVente(PointVente $pointVente): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.pointVente = :pdv')
            ->setParameter('pdv', $pointVente)
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<FluxRavitaillement> */
    public function findEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.dateCreation BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function montantTotalLivre(PointVente $pointVente): Montant
    {
        $somme = $this->createQueryBuilder('f')
            ->select('SUM(f.montantTotal)')
            ->andWhere('f.pointVente = :pdv')
            ->andWhere('f.statutFlux = :statut')
            ->setParameter('pdv', $pointVente)
            ->setParameter('statut', StatutFlux::LIVRE)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $somme ? Montant::zero() : Montant::fromString((string) $somme);
    }

    public function save(FluxRavitaillement $flux, bool $flush = true): void
    {
        $this->getEntityManager()->persist($flux);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FluxRavitaillement $flux, bool $flush = true): void
    {
        $this->getEntityManager()->remove($flux);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
