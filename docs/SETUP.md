# 🚀 Configuration du Projet MTNPDV

Ce guide explique comment installer et configurer le projet MTNPDV.

## Installation Rapide

### Option 1: Script automatisé (Recommandé)

```bash
# Rendre le script exécutable
chmod +x setup.sh

# Exécuter le script de setup
./setup.sh
```

Le script va automatiquement:
- ✅ Installer les dépendances Composer (PHP)
- ✅ Installer les dépendances npm (JavaScript)
- ✅ Créer les fichiers `.env.local` et `.env.test`
- ✅ Créer la base de données
- ✅ Exécuter les migrations
- ✅ Créer les dossiers de stockage (uploads)
- ✅ Construire les assets

### Option 2: Commande Symfony

```bash
php bin/console app:setup
```

Cette commande Symfony fait exactement la même chose que le script shell, avec une interface colorée.

### Option 3: Manuel (Pas recommandé)

```bash
# Installer Composer
composer install --optimize-autoloader

# Installer npm
npm install

# Configurer l'environnement
cp .env .env.local

# Créer la base de données
php bin/console doctrine:database:create --if-not-exists

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Créer les dossiers
mkdir -p public/uploads/{profils,preuves,pos}

# Construire les assets
npm run build
```

## Après l'Installation

### 1. Créer le premier utilisateur Admin

```bash
php bin/console app:utilisateur:creer
```

Suivez les instructions pour créer un utilisateur avec le rôle ADMIN.

**Exemple:**
```
Prénom: Jean
Nom: Dupont
Email: admin@example.com
Téléphone: +237123456789
Mot de passe: SecurePassword123!
Rôles: ADMIN
```

### 2. Lancer le serveur de développement

#### Avec Symfony CLI:
```bash
symfony serve:start
```

Accédez à: http://localhost:8000

#### Avec PHP intégré:
```bash
php -S localhost:8000 -t public
```

### 3. Vérifier l'installation

```bash
# Tester l'application
php bin/phpunit

# Valider la configuration
php bin/console lint:container
php bin/console doctrine:schema:validate --skip-sync
```

## Structure des Répertoires

```
mtnpdv/
├── bin/              # Scripts exécutables (console, phpunit)
├── config/           # Configuration Symfony
├── migrations/       # Migrations Doctrine
├── public/           # Racine web (uploads, assets compilés)
│   └── uploads/      # Photos et documents
├── src/              # Code source (DDD)
│   ├── Application/  # Handlers, Services
│   ├── Domain/       # Entities, Enums, ValueObjects
│   ├── Infrastructure/ # Repositories, Doctrine, Upload
│   └── Controller/   # Contrôleurs par rôle
├── templates/        # Templates Twig
├── tests/            # Tests (unitaires, intégration, fonctionnels)
├── assets/           # Sources CSS/JS
├── .env              # Configuration par défaut
├── .env.test         # Configuration tests
├── .env.local        # Configuration locale (créé lors du setup)
├── composer.json     # Dépendances PHP
├── package.json      # Dépendances npm
└── phpunit.dist.xml  # Configuration PHPUnit
```

## Variables d'Environnement

### `.env.local` (à créer)

```env
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL="mysql://user:password@localhost:3306/mtnpdv"
MAILER_DSN="sendmail://default"
APP_SECRET=your-random-secret-key-here
```

### `.env.test` (créé automatiquement)

```env
APP_ENV=test
APP_DEBUG=1
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
```

## Dépendances Principales

### PHP (Composer)
- **Symfony 7.2** - Framework web
- **Doctrine ORM** - ORM
- **PHPUnit** - Tests unitaires
- **Symfony BrowserKit** - Tests fonctionnels
- **VichUploader** - Gestion des fichiers
- **Dompdf** - Génération PDF

### Node.js (npm)
- **Bootstrap 5** - Framework CSS
- **Font Awesome** - Icônes
- **Leaflet** - Cartes interactives
- **Chart.js** - Graphiques

## Dépannage

### Erreur: "Composer not found"
```bash
# Installer Composer globalement
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Erreur: "npm not found"
```bash
# Installer Node.js et npm
# Sur macOS:
brew install node

# Sur Debian/Ubuntu:
sudo apt-get install nodejs npm

# Sur Windows:
# Télécharger depuis https://nodejs.org/
```

### Erreur: "Database connection failed"
```bash
# Vérifier la configuration .env.local
php bin/console doctrine:database:create --if-not-exists

# Vérifier les permissions
chmod -R 755 var/cache var/log
```

### Erreur: "Permission denied" pour uploads
```bash
# Corriger les permissions
chmod -R 755 public/uploads
```

## Commandes Utiles

```bash
# Tests
php bin/phpunit                    # Tous les tests
php bin/phpunit --filter=VisiteTest # Tests spécifiques

# Base de données
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate
php bin/console doctrine:database:drop --force

# Utilisateurs
php bin/console app:utilisateur:creer
php bin/console app:utilisateur:list

# Cache
php bin/console cache:clear
php bin/console cache:warmup

# Assets
npm run dev       # Mode développement (watch)
npm run build     # Production
npm run lint      # Vérification code
```

## Variables de Configuration

Dans `services.yaml`:

```yaml
parameters:
    app.name: 'MTNPDV'
    app.rayon_tolerance_metres: 100
    app.photo.dir.users: 'uploads/profils'
    app.photo.dir.visits: 'uploads/preuves'
    app.photo.dir.pos_declarations: 'uploads/pos'
    app.photo.max_size_bytes: 5242880  # 5 MB
```

## Support

- 📖 Documentation complète: `docs/`
- 🐛 Problèmes de tests: `tests/README.md`
- 🏗️ Architecture: `docs/ARCHITECTURE.md`
