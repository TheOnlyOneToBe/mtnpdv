<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutUtilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Email;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 *
 * @implements PasswordUpgraderInterface<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements UtilisateurRepositoryInterface, PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?Utilisateur
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findOneByEmail(Email $email): ?Utilisateur
    {
        return $this->findOneBy(['email' => $email->value()]);
    }

    /** @return list<Utilisateur> */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.statut = :statut')
            ->setParameter('statut', StatutUtilisateur::ACTIF)
            ->orderBy('u.nomUt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Utilisateur> */
    public function findByRole(string $codeRole): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.roles', 'r')
            ->andWhere('r.codeRole = :code')
            ->setParameter('code', strtoupper($codeRole))
            ->orderBy('u.nomUt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Utilisateur> */
    public function rechercher(string $terme): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.nomUt) LIKE :terme OR LOWER(u.prenomUt) LIKE :terme OR LOWER(u.email) LIKE :terme')
            ->setParameter('terme', '%'.mb_strtolower($terme).'%')
            ->orderBy('u.nomUt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Utilisateur $utilisateur, bool $flush = true): void
    {
        $this->getEntityManager()->persist($utilisateur);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Utilisateur $utilisateur, bool $flush = true): void
    {
        $this->getEntityManager()->remove($utilisateur);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Re-hache automatiquement le mot de passe quand l'algorithme évolue (contrat Symfony).
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances de "%s" non gérées.', $user::class));
        }

        $user->setMotPass($newHashedPassword);
        $this->getEntityManager()->flush();
    }
}
