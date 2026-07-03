<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\DemandeVisite;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutDemandeVisite;
use App\Domain\Repository\DemandeVisiteRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeVisite>
 */
class DemandeVisiteRepository extends ServiceEntityRepository implements DemandeVisiteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeVisite::class);
    }

    public function save(DemandeVisite $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function findByPointVente(PointVente $pointVente): array
    {
        return $this->findBy(['pointVente' => $pointVente], ['dateCreation' => 'DESC']);
    }

    public function findByAgent(Utilisateur $agent): array
    {
        return $this->findBy(['agent' => $agent], ['dateCreation' => 'DESC']);
    }

    public function findByAdministrateur(Utilisateur $admin): array
    {
        return $this->findBy(['administrateur' => $admin], ['dateCreation' => 'DESC']);
    }

    public function findPendantesParAgent(Utilisateur $agent): array
    {
        return $this->createQueryBuilder('dv')
            ->where('dv.agent = :agent')
            ->andWhere('dv.statut IN (:statuts)')
            ->setParameter('agent', $agent)
            ->setParameter('statuts', [
                StatutDemandeVisite::ASSIGNEE,
                StatutDemandeVisite::EFFECTUEE,
            ])
            ->orderBy('dv.dateDemandee', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAValiderParAdmin(): array
    {
        return $this->createQueryBuilder('dv')
            ->where('dv.statut = :statut')
            ->setParameter('statut', StatutDemandeVisite::EFFECTUEE)
            ->orderBy('dv.dateEffectuee', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function compterParStatut(): array
    {
        $result = $this->createQueryBuilder('dv')
            ->select('dv.statut, COUNT(dv) as count')
            ->groupBy('dv.statut')
            ->getQuery()
            ->getResult()
        ;

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['statut']->value] = $row['count'];
        }

        return $counts;
    }

    public function findDemandesPourPdv(PointVente $pointVente, string $statut = null): array
    {
        $query = $this->createQueryBuilder('dv')
            ->where('dv.pointVente = :pdv')
            ->setParameter('pdv', $pointVente)
            ->orderBy('dv.dateDemandee', 'DESC')
        ;

        if ($statut) {
            $query->andWhere('dv.statut = :statut')
                ->setParameter('statut', StatutDemandeVisite::from($statut));
        }

        return $query->getQuery()->getResult();
    }
}
