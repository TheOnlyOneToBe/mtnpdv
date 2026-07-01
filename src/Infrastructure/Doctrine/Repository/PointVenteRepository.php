<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutPointVente;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PointVente>
 */
class PointVenteRepository extends ServiceEntityRepository implements PointVenteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PointVente::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?PointVente
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findOneByCodeRef(string $codeRef): ?PointVente
    {
        return $this->findOneBy(['codeRef' => $codeRef]);
    }

    /** @return list<PointVente> */
    public function findByVille(string $ville): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.ville) = :ville')
            ->setParameter('ville', mb_strtolower($ville))
            ->orderBy('p.nomPdv', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<PointVente> */
    public function rechercher(string $terme): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.nomPdv) LIKE :terme OR LOWER(p.ville) LIKE :terme OR LOWER(p.codeRef) LIKE :terme')
            ->setParameter('terme', '%'.mb_strtolower($terme).'%')
            ->orderBy('p.nomPdv', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<PointVente> */
    public function findByStatut(StatutPointVente $statut): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.statutActuel = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('p.nomPdv', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<PointVente> */
    public function findByGerant(Utilisateur $gerant): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.gerant = :gerant')
            ->setParameter('gerant', $gerant)
            ->orderBy('p.nomPdv', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par distance avec la formule de Haversine (SQL natif, non exprimable en DQL).
     *
     * @return list<PointVente>
     */
    public function findProches(Coordonnees $position, float $rayonKm): array
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(PointVente::class, 'p');

        $sql = sprintf(
            <<<'SQL'
                SELECT %s,
                       (6371 * ACOS(
                           COS(RADIANS(:lat)) * COS(RADIANS(p.latitude))
                           * COS(RADIANS(p.longitude) - RADIANS(:lng))
                           + SIN(RADIANS(:lat)) * SIN(RADIANS(p.latitude))
                       )) AS distance_km
                FROM point_vente p
                HAVING distance_km <= :rayon
                ORDER BY distance_km ASC
                SQL,
            $rsm->generateSelectClause(),
        );

        return $this->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameter('lat', $position->latitude())
            ->setParameter('lng', $position->longitude())
            ->setParameter('rayon', $rayonKm)
            ->getResult();
    }

    /** @return array<string, int> */
    public function compterParStatut(): array
    {
        $lignes = $this->createQueryBuilder('p')
            ->select('p.statutActuel AS statut, COUNT(p.id) AS total')
            ->groupBy('p.statutActuel')
            ->getQuery()
            ->getArrayResult();

        $resultat = [];
        foreach ($lignes as $ligne) {
            $statut = $ligne['statut'] instanceof StatutPointVente ? $ligne['statut']->value : (string) $ligne['statut'];
            $resultat[$statut] = (int) $ligne['total'];
        }

        return $resultat;
    }

    public function save(PointVente $pointVente, bool $flush = true): void
    {
        $this->getEntityManager()->persist($pointVente);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PointVente $pointVente, bool $flush = true): void
    {
        $this->getEntityManager()->remove($pointVente);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
