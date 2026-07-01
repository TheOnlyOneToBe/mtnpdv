<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Matrice des permissions du README pour les points de vente :
 * - VOIR : admin et agent (tous), gérant (uniquement le sien)
 * - CREER : admin et agent
 * - MODIFIER / SUPPRIMER : admin uniquement
 *
 * @extends Voter<string, PointVente|null>
 */
final class PointVenteVoter extends Voter
{
    public const VOIR = 'POINT_VENTE_VOIR';
    public const CREER = 'POINT_VENTE_CREER';
    public const MODIFIER = 'POINT_VENTE_MODIFIER';
    public const SUPPRIMER = 'POINT_VENTE_SUPPRIMER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [self::VOIR, self::CREER, self::MODIFIER, self::SUPPRIMER], true)) {
            return false;
        }

        return $subject instanceof PointVente || (self::CREER === $attribute && null === $subject);
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
            self::CREER => $estAdmin || $estAgent,
            self::MODIFIER, self::SUPPRIMER => $estAdmin,
            self::VOIR => $estAdmin || $estAgent
                || ($estGerant && $subject instanceof PointVente && $subject->getGerant() === $utilisateur),
            default => false,
        };
    }
}
