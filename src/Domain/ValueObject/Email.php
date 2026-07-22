<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Adresse e-mail validée et normalisée (minuscules, sans espaces).
 * Supporte les caractères Unicode dans la partie locale.
 */
final class Email implements \Stringable
{
    private readonly string $value;
    private readonly string $originalValue;

    public function __construct(string $value)
    {
        $this->originalValue = $value;
        
        // Nettoyer et normaliser l'email
        $normalise = $this->normalizeEmail($value);
        
        // Valider avec plusieurs méthodes
        if (!$this->isValidEmail($normalise)) {
            throw new InvalidArgumentException(sprintf('Adresse e-mail invalide : "%s".', $value));
        }

        $this->value = $normalise;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function getOriginalValue(): string
    {
        return $this->originalValue;
    }

    public function domaine(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function partieLocale(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    public function equals(self $autre): bool
    {
        return $this->value === $autre->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Normalise l'email en translittérant les caractères accentués
     * et en nettoyant les espaces.
     */
    private function normalizeEmail(string $email): string
    {
        // Nettoyer les espaces
        $email = trim($email);
        
        // Vérifier si l'email contient un @
        if (!str_contains($email, '@')) {
            return $email;
        }

        // Séparer partie locale et domaine
        [$local, $domain] = explode('@', $email, 2);
        
        // Normaliser la partie locale
        $local = $this->normalizeLocalPart($local);
        
        // Normaliser le domaine (supprimer accents, mettre en minuscule)
        $domain = $this->normalizeDomain($domain);
        
        // Reconstruire l'email
        return $local . '@' . $domain;
    }

    /**
     * Normalise la partie locale de l'email.
     * Supprime les accents et caractères spéciaux non autorisés.
     */
    private function normalizeLocalPart(string $local): string
    {
        // 1. Supprimer les accents (é -> e, è -> e, etc.)
        $local = $this->removeAccents($local);
        
        // 2. Remplacer certains caractères spéciaux
        $local = $this->sanitizeSpecialChars($local);
        
        // 3. Supprimer les caractères non autorisés dans un email
        // CORRECTION : Échapper le tiret en le mettant à la fin ou en l'échappant
        $local = preg_replace('/[^a-zA-Z0-9._+ -]/', '', $local);
        // OU ALORS (plus sûr) :
        // $local = preg_replace('/[^a-zA-Z0-9._+ -]/u', '', $local);
        
        // 4. Mettre en minuscule
        $local = mb_strtolower($local);
        
        return $local;
    }

    /**
     * Normalise le domaine de l'email.
     */
    private function normalizeDomain(string $domain): string
    {
        // Supprimer les accents
        $domain = $this->removeAccents($domain);
        
        // Nettoyer le domaine
        $domain = trim($domain);
        
        // Supprimer les caractères non autorisés dans un domaine
        // CORRECTION : Échapper le tiret
        $domain = preg_replace('/[^a-zA-Z0-9.-]/', '', $domain);
        
        // Mettre en minuscule
        $domain = mb_strtolower($domain);
        
        // Supprimer les points en début et fin
        $domain = trim($domain, '.');
        
        return $domain;
    }

    /**
     * Supprime les accents d'une chaîne de caractères.
     */
    private function removeAccents(string $string): string
    {
        // Méthode 1 : Utiliser Transliterator (PHP 5.4+)
        if (class_exists(\Transliterator::class)) {
            $transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($transliterator) {
                $result = $transliterator->transliterate($string);
                if ($result !== false) {
                    return $result;
                }
            }
        }
        
        // Méthode 2 : Tableau de correspondance manuel (fallback)
        $accents = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ÿ' => 'Y', 'ÿ' => 'y',
            'Ç' => 'C', 'ç' => 'c',
            'Ñ' => 'N', 'ñ' => 'n',
            'Æ' => 'AE', 'æ' => 'ae',
            'Œ' => 'OE', 'œ' => 'oe',
        ];
        
        return strtr($string, $accents);
    }

    /**
     * Nettoie les caractères spéciaux dans la partie locale.
     */
    private function sanitizeSpecialChars(string $local): string
    {
        // Remplacer les caractères spéciaux courants
        $specials = [
            ' ' => '.',    // Espaces en points
            '_' => '.',    // Underscores en points (optionnel)
        ];
        
        return strtr($local, $specials);
    }

    /**
     * Valide l'email avec plusieurs méthodes.
     */
    private function isValidEmail(string $email): bool
    {
        // Vérification basique
        if (empty($email) || !str_contains($email, '@')) {
            return false;
        }

        // Séparer pour validation détaillée
        [$local, $domain] = explode('@', $email, 2);
        
        // Vérifier que les parties ne sont pas vides
        if (empty($local) || empty($domain)) {
            return false;
        }

        // Méthode 1 : FILTER_VALIDATE_EMAIL (validation standard)
        if (filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        // Méthode 2 : Validation personnalisée avec regex
        // Pattern plus permissif pour la partie locale
        // CORRECTION : Échapper le tiret
        $patternLocal = '/^[a-zA-Z0-9._+ -]+$/';
        if (!preg_match($patternLocal, $local)) {
            return false;
        }

        // Validation du domaine
        // Le domaine doit avoir au moins un point et des caractères valides
        if (!str_contains($domain, '.')) {
            return false;
        }

        // Vérifier que le domaine ne contient que des caractères valides
        // CORRECTION : Échapper le tiret
        $patternDomain = '/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        if (!preg_match($patternDomain, $domain)) {
            return false;
        }

        // Vérifier la longueur du domaine (TLD)
        $parts = explode('.', $domain);
        $tld = end($parts);
        if (strlen($tld) < 2 || strlen($tld) > 10) {
            return false;
        }

        // Vérifier la longueur totale (max 254 caractères)
        if (strlen($email) > 254) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie si l'email est dans un format valide (méthode utilitaire).
     */
    public function isValid(): bool
    {
        return $this->isValidEmail($this->value);
    }

    /**
     * Retourne une version masquée de l'email (pour protection des données).
     */
    public function masquer(): string
    {
        $parts = explode('@', $this->value);
        $local = $parts[0];
        $domain = $parts[1] ?? '';
        
        if (strlen($local) <= 2) {
            $masque = ($local[0] ?? '') . str_repeat('*', max(0, strlen($local) - 1));
        } else {
            $masque = $local[0] . str_repeat('*', max(0, strlen($local) - 2)) . substr($local, -1);
        }
        
        return $masque . '@' . $domain;
    }

    /**
     * Vérifie si l'email appartient à un domaine spécifique.
     */
    public function aDomaine(string $domaine): bool
    {
        return $this->domaine() === strtolower(trim($domaine));
    }

    /**
     * Vérifie si l'email a un domaine en liste blanche.
     */
    public function domaineDansListe(array $domainesAutorises): bool
    {
        return in_array($this->domaine(), array_map('strtolower', $domainesAutorises));
    }

    /**
     * Vérifie si l'email a un domaine en liste noire.
     */
    public function domaineDansListeNoire(array $domainesInterdits): bool
    {
        return in_array($this->domaine(), array_map('strtolower', $domainesInterdits));
    }
}