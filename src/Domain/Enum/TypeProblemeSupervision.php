<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Types de problèmes constatés lors de la supervision d'un point de vente.
 */
enum TypeProblemeSupervision: string
{
    case RUPTURE_STOCK = 'RUPTURE_STOCK';
    case ABSENCE_GERANT = 'ABSENCE_GERANT';
    case CONNEXION_INTERNET_INDISPONIBLE = 'CONNEXION_INTERNET_INDISPONIBLE';
    case PROBLEME_TERMINAL_MOMO = 'PROBLEME_TERMINAL_MOMO';
    case PROBLEME_ALIMENTATION_ELECTRIQUE = 'PROBLEME_ALIMENTATION_ELECTRIQUE';
    case FERMETURE_EXCEPTIONNELLE = 'FERMETURE_EXCEPTIONNELLE';
    case CLIENT_INSATISFAIT = 'CLIENT_INSATISFAIT';
    case BESOIN_FONDS_ROULEMENT = 'BESOIN_FONDS_ROULEMENT';
    case POINT_VENTE_INACCESSIBLE = 'POINT_VENTE_INACCESSIBLE';
    case AUCUN_PROBLEME = 'AUCUN_PROBLEME';

    public function libelle(): string
    {
        return match ($this) {
            self::RUPTURE_STOCK => 'Rupture de stock',
            self::ABSENCE_GERANT => 'Absence du gérant',
            self::CONNEXION_INTERNET_INDISPONIBLE => 'Connexion Internet indisponible',
            self::PROBLEME_TERMINAL_MOMO => 'Problème de terminal MoMo',
            self::PROBLEME_ALIMENTATION_ELECTRIQUE => 'Problème d\'alimentation électrique',
            self::FERMETURE_EXCEPTIONNELLE => 'Fermeture exceptionnelle',
            self::CLIENT_INSATISFAIT => 'Client insatisfait',
            self::BESOIN_FONDS_ROULEMENT => 'Besoin de fonds de roulement',
            self::POINT_VENTE_INACCESSIBLE => 'Point de vente inaccessible',
            self::AUCUN_PROBLEME => 'Aucun problème constaté',
        };
    }

    public function urgence(): string
    {
        return match ($this) {
            self::RUPTURE_STOCK,
            self::PROBLEME_TERMINAL_MOMO,
            self::PROBLEME_ALIMENTATION_ELECTRIQUE,
            self::BESOIN_FONDS_ROULEMENT => 'HAUTE',

            self::ABSENCE_GERANT,
            self::CONNEXION_INTERNET_INDISPONIBLE,
            self::FERMETURE_EXCEPTIONNELLE,
            self::CLIENT_INSATISFAIT => 'MOYENNE',

            self::POINT_VENTE_INACCESSIBLE => 'CRITIQUE',

            self::AUCUN_PROBLEME => 'BASSE',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::RUPTURE_STOCK => 'fa-boxes-stacked',
            self::ABSENCE_GERANT => 'fa-user-slash',
            self::CONNEXION_INTERNET_INDISPONIBLE => 'fa-wifi',
            self::PROBLEME_TERMINAL_MOMO => 'fa-phone',
            self::PROBLEME_ALIMENTATION_ELECTRIQUE => 'fa-plug',
            self::FERMETURE_EXCEPTIONNELLE => 'fa-door-closed',
            self::CLIENT_INSATISFAIT => 'fa-face-frown',
            self::BESOIN_FONDS_ROULEMENT => 'fa-money-bill',
            self::POINT_VENTE_INACCESSIBLE => 'fa-triangle-exclamation',
            self::AUCUN_PROBLEME => 'fa-circle-check',
        };
    }

    /**
     * Couleur Bootstrap pour l'affichage
     */
    public function couleur(): string
    {
        return match ($this) {
            self::POINT_VENTE_INACCESSIBLE => 'danger',
            self::RUPTURE_STOCK,
            self::PROBLEME_TERMINAL_MOMO,
            self::PROBLEME_ALIMENTATION_ELECTRIQUE,
            self::BESOIN_FONDS_ROULEMENT => 'warning',

            self::ABSENCE_GERANT,
            self::CONNEXION_INTERNET_INDISPONIBLE,
            self::FERMETURE_EXCEPTIONNELLE,
            self::CLIENT_INSATISFAIT => 'info',

            self::AUCUN_PROBLEME => 'success',
        };
    }
}
