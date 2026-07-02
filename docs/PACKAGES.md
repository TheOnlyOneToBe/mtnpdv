# Packages et Dépendances MTNPDV

## 📦 Vue d'ensemble

MTNPDV utilise Symfony 7.2 comme framework et une sélection de packages éprouvés pour la production.

---

## 🎯 Dépendances Production

### Framework & Core

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/symfony** | 7.2 | Framework web principal |
| **symfony/console** | 7.2 | Commandes CLI |
| **symfony/form** | 7.2 | Gestion des formulaires |
| **symfony/security** | 7.2 | Authentification & autorisation |
| **symfony/messenger** | 7.2 | File d'attente messages |
| **symfony/uid** | 7.2 | Génération d'identifiants |

### Persistance & Base de données

| Package | Version | Usage |
|---------|---------|-------|
| **doctrine/orm** | 2.16 | Object-Relational Mapping |
| **doctrine/doctrine-bundle** | 2.11 | Intégration Symfony |
| **doctrine/dbal** | 3.7 | Abstraction base données |
| **doctrine/migrations** | 3.7 | Gestion versions BD |

**Détail DBAL:**
```yaml
Types custom:
  - montant: Montant (centimes, DECIMAL 10,2)
  - email: Email (VARCHAR 255)
  - telephone: Telephone (VARCHAR 20)
  - coordonnees: Coordonnees (DECIMAL lat/lng)
```

### Sécurité & Authentification

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/security-bundle** | 7.2 | Security bundle |
| **symfony/password-hasher** | 7.2 | Hachage bcrypt |
| **symfony/polyfill** | 1.* | Polyfills PHP |

**Authentification:**
- Méthode: **Form Login**
- Provider: **Email (Utilisateur.email)**
- Hasher: **Bcrypt** (coût: 4)

### Fichiers & Upload

| Package | Version | Usage |
|---------|---------|-------|
| **vich/uploader-bundle** | 2.3 | Gestion fichiers uploads |
| **symfony/filesystem** | 7.2 | Opérations filesystem |

**Configuration uploads:**
```yaml
photos_visite:
  storage: filesystem
  directory: public/uploads/preuves
  namer: Vich\UploaderBundle\Naming\SmartUniqueNamer

photos_profil:
  storage: filesystem
  directory: public/uploads/profils
  namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
```

### Frontend & Assets

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/asset** | 7.2 | Gestion des assets |
| **symfony/webpack-encore-bundle** | 2.1 | Bundling JavaScript |
| **bootstrap** | 5.3 | Framework CSS |
| **font-awesome** | 6.4 | Icônes SVG |
| **leaflet** | 1.9 | Cartes interactives |

**CDN utilisés:**
```html
Bootstrap: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0
Font-Awesome: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0
Leaflet: https://unpkg.com/leaflet@1.9.4
```

### Validation

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/validator** | 7.2 | Validation données |
| **symfony/property-access** | 7.2 | Accès propriétés |

---

## 🧪 Dépendances Développement

### Tests

| Package | Version | Usage |
|---------|---------|-------|
| **phpunit/phpunit** | 13.2 | Framework de tests |
| **symfony/test-pack** | 7.2 | Test utilities Symfony |
| **symfony/browser-kit** | 7.2 | Tests fonctionnels |
| **symfony/css-selector** | 7.2 | Sélecteurs CSS tests |

**Configuration tests:**
```yaml
# phpunit.dist.xml
KERNEL_CLASS: App\Kernel
KERNEL_DIR: src/
DATABASE_URL: sqlite:///:memory: (tests)
APP_ENV: test
```

### Code Quality & Linting

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/debug-pack** | 7.2 | Débogage |
| **symfony/profiler-pack** | 7.2 | Profilage |

---

## 🔧 Configuration Symfony

### Services principaux

#### Repositories
```yaml
services:
  App\Domain\Repository\PointVenteRepositoryInterface:
    class: App\Infrastructure\Doctrine\Repository\PointVenteRepository
  
  App\Domain\Repository\TransactionRepositoryInterface:
    class: App\Infrastructure\Doctrine\Repository\TransactionRepository
  
  App\Domain\Repository\UtilisateurRepositoryInterface:
    class: App\Infrastructure\Doctrine\Repository\UtilisateurRepository
```

#### Handlers (Use Cases)
```yaml
services:
  App\Application\Visite\EnregistrerVisiteHandler:
    arguments:
      - '@App\Domain\Repository\PointVenteRepositoryInterface'
      - '@App\Domain\Repository\TransactionRepositoryInterface'
  
  App\Application\Gerant\EnregistrerVenteHandler:
    arguments:
      - '@App\Domain\Repository\PointVenteRepositoryInterface'
      - '@App\Domain\Repository\TransactionRepositoryInterface'
```

#### Security Voters
```yaml
services:
  App\Infrastructure\Security\Voter\PointVenteVoter:
    tags: ['security.voter']
    public: false
  
  App\Infrastructure\Security\Voter\TransactionVoter:
    tags: ['security.voter']
    public: false
```

#### Upload Management
```yaml
services:
  App\Infrastructure\Upload\PhotoPreuveUploader:
    arguments:
      - '@knp_gaufrette.filesystem_map'
      - '%kernel.project_dir%'
```

### Access Control (security.yaml)

```yaml
security:
  role_hierarchy:
    ROLE_ADMIN: [ROLE_USER]
    ROLE_AGENT: [ROLE_USER]
    ROLE_GERANT: [ROLE_USER]
  
  access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/agent, roles: ROLE_AGENT }
    - { path: ^/gerant, roles: ROLE_GERANT }
    - { path: ^/profil, roles: ROLE_USER }
    - { path: ^/login, roles: PUBLIC_ACCESS }
```

---

## 💻 Commandes disponibles

### Symfony built-in
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
php bin/console server:run
```

### MTNPDV custom
```bash
# Initialisation complète
php bin/console app:init:database

# Créer un utilisateur
php bin/console app:utilisateur:creer
```

---

## 📊 Versions PHP & Symfony

- **PHP**: 8.4.19
- **Symfony**: 7.2
- **Doctrine ORM**: 2.16
- **Bootstrap**: 5.3.0
- **Leaflet**: 1.9.4

---

## 🔒 Sécurité des dépendances

### Policies appliquées
- ✅ Mises à jour sécurité régulières
- ✅ Dépendances minimales
- ✅ Versions stables uniquement
- ✅ Audit avec `composer audit`

### Vulnerabilities check
```bash
# Scanner les CVE
composer audit

# Mettre à jour sécurité
composer update --with-dependencies
```

---

## 📦 Fichier composer.json

```json
{
    "require": {
        "php": ">=8.4",
        "symfony/symfony": "7.2.*",
        "doctrine/orm": "^2.16",
        "doctrine/migrations": "^3.7",
        "vich/uploader-bundle": "^2.3"
    },
    "require-dev": {
        "phpunit/phpunit": "^13.2",
        "symfony/test-pack": "^7.2"
    }
}
```

---

## 🎯 Justifications des choix

### Pourquoi Symfony 7.2?
- ✅ LTS support long terme
- ✅ Architecture modulaire DDD-friendly
- ✅ Composants testables indépendamment
- ✅ Ecosystem mature (Doctrine, VichUploader, etc.)

### Pourquoi Doctrine ORM?
- ✅ Standard de facto PHP
- ✅ Support Value Objects natif
- ✅ Types DBAL personalisables
- ✅ Migrations versionées

### Pourquoi VichUploader?
- ✅ Gestion fichiers simplifiée
- ✅ Intégration Doctrine transparente
- ✅ Nommage intelligent
- ✅ Support plusieurs storage

### Pourquoi Bootstrap 5?
- ✅ Responsive design natif
- ✅ Composants prêts à l'emploi
- ✅ Thématisation facile
- ✅ Accessibilité WCAG

### Pourquoi Leaflet?
- ✅ Lightweight (39KB)
- ✅ API simple et bien documentée
- ✅ Plugin ecosystem riche
- ✅ Open source (BSD)

---

**Version:** 1.0  
**Dernière vérification:** 2026-07-02
