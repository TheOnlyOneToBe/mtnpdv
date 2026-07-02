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

    public function getLibelle(): string
    {
        return match ($this) {
            self::VISITE_VALIDEE => 'Visite validée',
            self::VISITE_REJETEE => 'Visite rejetée',
            self::VISITE_CREEE => 'Visite créée',
            self::PRODUIT_LIVRE => 'Produit livré',
            self::MESSAGE_ADMIN => 'Message administrateur',
            self::ALERTE_SYSTEME => 'Alerte système',
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
        };
    }
}
