<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Notification;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\NotificationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineNotificationRepository implements NotificationRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        $this->repository = $em->getRepository(Notification::class);
    }

    public function save(Notification $notification): void
    {
        $this->em->persist($notification);
        $this->em->flush();
    }

    public function findById(int $id): ?Notification
    {
        return $this->repository->find($id);
    }

    public function findNonLuesParUtilisateur(Utilisateur $utilisateur, int $limit = 10): array
    {
        return $this->repository->findBy(
            ['utilisateur' => $utilisateur, 'lu' => false],
            ['dateCreation' => 'DESC'],
            $limit
        );
    }

    public function findParUtilisateur(Utilisateur $utilisateur, int $limit = 50): array
    {
        return $this->repository->findBy(
            ['utilisateur' => $utilisateur],
            ['dateCreation' => 'DESC'],
            $limit
        );
    }

    public function compterNonLues(Utilisateur $utilisateur): int
    {
        return $this->repository->count(['utilisateur' => $utilisateur, 'lu' => false]);
    }

    public function marquerCommeLue(Notification $notification): void
    {
        $notification->marquerCommeLue();
        $this->em->flush();
    }

    public function marquerToutesCommeLues(Utilisateur $utilisateur): void
    {
        $qb = $this->em->createQueryBuilder();
        $qb->update(Notification::class, 'n')
            ->set('n.lu', 'true')
            ->set('n.dateLecture', ':now')
            ->where('n.utilisateur = :utilisateur')
            ->andWhere('n.lu = false')
            ->setParameter(':utilisateur', $utilisateur)
            ->setParameter(':now', new \DateTimeImmutable());

        $qb->getQuery()->execute();
    }

    public function supprimer(Notification $notification): void
    {
        $this->em->remove($notification);
        $this->em->flush();
    }
}
