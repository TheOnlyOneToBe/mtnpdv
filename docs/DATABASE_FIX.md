# 🔧 Correctif Base de Données - Clé Trop Longue

## Problème

Lors du setup avec MySQL, vous pouviez rencontrer l'erreur:

```
SQLSTATE[42000]: Erreur de syntaxe ou violation d'accès: 1071 La clé est trop longue
```

### Cause

MySQL a une limite sur la longueur des clés d'index:
- **InnoDB**: 767 bytes par défaut
- **Avec utf8mb4**: Chaque caractère = 4 bytes

Le problème survenait sur la colonne `facture_uniq` de la table `flux_ravitaillement`:
- **Ancien**: `VARCHAR(255)` = 255 × 4 = **1020 bytes** ❌ (dépasse 767)
- **Nouveau**: `VARCHAR(100)` = 100 × 4 = **400 bytes** ✅ (dans la limite)

## Solution

### 1️⃣ Entité (src/Domain/Entity/FluxRavitaillement.php)
```php
// ❌ Avant
#[ORM\Column(name: 'facture_uniq', type: Types::STRING, length: 255)]

// ✅ Après
#[ORM\Column(name: 'facture_uniq', type: Types::STRING, length: 100)]
```

### 2️⃣ Migration (migrations/Version20260702000001.php)

Une migration automatique a été créée pour:
- Supprimer l'index UNIQUE existant
- Réduire la colonne à VARCHAR(100)
- Recréer l'index UNIQUE

**Note**: Cette migration ne s'exécute que sur **MySQL** (ignorée sur SQLite)

## Installation

### Pour un nouveau projet

```bash
# Les migrations s'exécutent automatiquement
php bin/console app:setup
# ou
./setup.sh
```

### Pour un projet existant avec ancienne DB

```bash
# Option 1: Réinitialiser complètement
php bin/console app:db:reset

# Option 2: Appliquer seulement la migration
php bin/console doctrine:migrations:migrate
```

## Configuration MySQL Recommandée

Pour éviter les problèmes futurs:

```sql
-- Augmenter la limite de clé (MySQL 5.7.7+)
SET GLOBAL innodb_file_per_table=ON;
SET GLOBAL innodb_large_prefix=ON;

-- Ou utiliser innodb_file_format avec row_format DYNAMIC
ALTER TABLE flux_ravitaillement ROW_FORMAT=DYNAMIC;
```

## Bonnes Pratiques

### Longueurs de VARCHAR pour indexes

Lors de la création de nouvelles colonnes avec UNIQUE ou INDEX:

| Type de Donnée | Exemple | Longueur Suggérée |
|---|---|---|
| Email | john@example.com | 100-120 |
| Numéro Facture | FAC-2026-001234 | 50-100 |
| Code Référence | PDV-DOUALA-0001 | 50-100 |
| UUID | 550e8400-e29b-41d4-a716-446655440000 | 36-50 |
| Slug/URL | product-name-variant | 100-200 |
| Téléphone | +237123456789 | 20-30 |
| Numéro Compte | ACC-123456789 | 20-50 |

### Formule de Limite

```
Max VARCHAR length = 767 / (bytes per character) = 767 / 4 = 191 caractères

Pour du utf8mb4 (4 bytes): max 191 caractères
Pour du utf8mb3 (3 bytes): max 255 caractères
```

## Support MySQL par Version

| Version MySQL | Limite Défaut | Solution |
|---|---|---|
| 5.0 - 5.1 | 767 bytes | Réduire VARCHAR |
| 5.6 | 767 bytes | Augmenter innodb_file_format |
| 5.7+ | 3072 bytes | ✅ Aucun problème |
| 8.0+ | 3072 bytes | ✅ Aucun problème |

## Vérification

Pour vérifier que le correctif est appliqué:

```bash
# Afficher la structure de la table
php bin/console doctrine:schema:validate --skip-sync

# Ou en MySQL directement
SHOW CREATE TABLE flux_ravitaillement\G
```

Vous devriez voir:
```
facture_uniq varchar(100) NOT NULL
UNIQUE KEY UNIQ_FLUX_RAVITAILLEMENT_FACTURE (facture_uniq)
```

## Changelog

- **v1.0.0** - Initialisation avec bug (VARCHAR(255))
- **v1.0.1** - Correctif: VARCHAR(100) avec migration automatique
  - Migration Version20260702000001 ajoutée
  - Entité FluxRavitaillement mise à jour
  - Migration MySQL-safe (ignorée sur SQLite)

## Ressources

- [Doctrine Migrations](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html)
- [MySQL Key Length Limits](https://dev.mysql.com/doc/refman/8.0/en/column-count-limit.html)
- [Symfony Database Docs](https://symfony.com/doc/current/doctrine.html)
