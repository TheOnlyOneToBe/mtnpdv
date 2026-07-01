<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Matrice des permissions du README pour les visites/transactions :
 * - VOIR : admin (toutes), agent (les siennes), gérant (celles de son kiosque)
 * - CREER : agent uniquement (l'admin ne fait pas de terrain)
 * - VALIDER : admin uniquement
 *
 * @extends Voter<string, Transaction|null>
 */
final class TransactionVoter extends Voter
{
    public const VOIR = 'TRANSACTION_VOIR';
    public const CREER = 'TRANSACTION_CREER';
    public const VALIDER = 'TRANSACTION_VALIDER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [self::VOIR, self::CREER, self::VALIDER], true)) {
            return false;
        }

        return $subject instanceof Transaction || (self::CREER === $attribute && null === $subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $utilisateur = $token->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }

        $roles = $utilisateur->getRoles();
        $estAdmin = \in_array('ROLE_ADMIN', $roles, true);
        $estAgent = \in_array('ROLE_AGENT', $roles, true);
        $estGerant = \in_array('ROLE_GERANT', $roles, true);

        return match ($attribute) {
            self::CREER => $estAgent,
            self::VALIDER => $estAdmin,
            self::VOIR => $estAdmin
                || ($subject instanceof Transaction && (
                    ($estAgent && $subject->getUtilisateur() === $utilisateur)
                    || ($estGerant && $subject->getPointVente()?->getGerant() === $utilisateur)
                )),
            default => false,
        };
    }
}
