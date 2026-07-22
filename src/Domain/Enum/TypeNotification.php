<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum TypeNotification: string
{
    case VISITE_VALIDEE = 'VISITE_VALIDEE';
    case VISITE_REJETEE = 'VISITE_REJETEE';
    case VISITE_CREEE = 'VISITE_CREEE';
    case PRODUIT_LIVRE = 'PRODUIT_LIVRE';
    case MESSAGE_ADMIN = 'MESSAGE_ADMIN';
    case ALERTE_SYSTEME = 'ALERTE_SYSTEME';
    case APPROVISIONNEMENT_DEMANDE = 'APPROVISIONNEMENT_DEMANDE';
    case ARGENT_RECU_PAR_AGENT = 'ARGENT_RECU_PAR_AGENT';
    case APPROVISIONNEMENT_TERMINE = 'APPROVISIONNEMENT_TERMINE';

    public function getLibelle(): string
    {
        return match ($this) {
            self::VISITE_VALIDEE => 'Visite validée',
            self::VISITE_REJETEE => 'Visite rejetée',
            self::VISITE_CREEE => 'Visite créée',
            self::PRODUIT_LIVRE => 'Produit livré',
            self::MESSAGE_ADMIN => 'Message administrateur',
            self::ALERTE_SYSTEME => 'Alerte système',
            self::APPROVISIONNEMENT_DEMANDE => 'Demande d\'approvisionnement',
            self::ARGENT_RECU_PAR_AGENT => 'Argent reçu par l\'agent',
            self::APPROVISIONNEMENT_TERMINE => 'Approvisionnement terminé',
        };
    }

    public function getCouleur(): string
    {
        return match ($this) {
            self::VISITE_VALIDEE => 'success',
            self::VISITE_REJETEE => 'danger',
            self::VISITE_CREEE => 'info',
            self::PRODUIT_LIVRE => 'primary',
            self::MESSAGE_ADMIN => 'warning',
            self::ALERTE_SYSTEME => 'secondary',
            self::APPROVISIONNEMENT_DEMANDE => 'info',
            self::ARGENT_RECU_PAR_AGENT => 'success',
            self::APPROVISIONNEMENT_TERMINE => 'success',
        };
    }

    public function getIcone(): string
    {
        return match ($this) {
            self::VISITE_VALIDEE => 'fa-check-circle',
            self::VISITE_REJETEE => 'fa-times-circle',
            self::VISITE_CREEE => 'fa-receipt',
            self::PRODUIT_LIVRE => 'fa-box',
            self::MESSAGE_ADMIN => 'fa-envelope',
            self::ALERTE_SYSTEME => 'fa-exclamation-triangle',
            self::APPROVISIONNEMENT_DEMANDE => 'fa-truck',
            self::ARGENT_RECU_PAR_AGENT => 'fa-hand-holding-dollar',
            self::APPROVISIONNEMENT_TERMINE => 'fa-circle-check',
        };
    }
}
