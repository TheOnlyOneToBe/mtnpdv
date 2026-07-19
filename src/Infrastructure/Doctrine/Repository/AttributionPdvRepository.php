<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\AttributionPdv;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\AttributionPdvRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AttributionPdv> */
class AttributionPdvRepository extends ServiceEntityRepository implements AttributionPdvRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttributionPdv::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?AttributionPdv
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /** @return list<AttributionPdv> */
    public function findAll(): array
    {
        return $this->findBy([], ['dateAttribution' => 'DESC']);
    }

    /** @return list<AttributionPdv> */
    public function findByAgent(Utilisateur $agent): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.agent = :agent')
            ->setParameter('agent', $agent)
            ->orderBy('a.dateAttribution', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<AttributionPdv> */
    public function findByPointVente(int $pointVenteId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.pointVente = :pointVenteId')
            ->setParameter('pointVenteId', $pointVenteId)
            ->orderBy('a.dateAttribution', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<AttributionPdv> */
    public function findAttives(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('a.dateAttribution', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<AttributionPdv> */
    public function findAttivesByAgent(Utilisateur $agent): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.agent = :agent')
            ->andWhere('a.actif = :actif')
            ->setParameter('agent', $agent)
            ->setParameter('actif', true)
            ->orderBy('a.dateAttribution', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<AttributionPdv> */
    public function findAttivesByPointVente(PointVente $pointVente): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.pointVente = :pointVente')
            ->andWhere('a.actif = :actif')
            ->setParameter('pointVente', $pointVente)
            ->setParameter('actif', true)
            ->orderBy('a.dateAttribution', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<AttributionPdv> */
    public function search(string $terme, ?bool $actif = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.agent', 'u')
            ->leftJoin('a.pointVente', 'p')
            ->addSelect('u', 'p');

        if ('' !== trim($terme)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(u.nomUt) LIKE :terme',
                    'LOWER(u.prenomUt) LIKE :terme',
                    'LOWER(u.email) LIKE :terme',
                    'LOWER(p.nomPdv) LIKE :terme',
                    'LOWER(p.codeRef) LIKE :terme',
                    'LOWER(p.ville) LIKE :terme',
                ),
            );
            $qb->setParameter('terme', '%'.mb_strtolower($terme).'%');
        }

        if (null !== $actif) {
            $qb->andWhere('a.actif = :actif')
                ->setParameter('actif', $actif);
        }

        return $qb->orderBy('a.dateAttribution', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(AttributionPdv $attribution, bool $flush = true): void
    {
        $this->getEntityManager()->persist($attribution);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AttributionPdv $attribution, bool $flush = true): void
    {
        $this->getEntityManager()->remove($attribution);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
