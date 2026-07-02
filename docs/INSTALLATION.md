# Installation & Configuration - MTNPDV

## 📋 Prérequis

### Système
- **OS:** Linux, macOS, ou Windows (WSL2)
- **PHP:** 8.4+
- **MySQL:** 8.0+ (ou SQLite pour développement)
- **Composer:** 2.0+
- **Git:** 2.0+

### Extensions PHP requises
```bash
php -m | grep -E 'pdo|mysql|curl|intl|gd|xml|json'
```

Essentielles:
- `pdo` - Base de données
- `pdo_mysql` - Driver MySQL
- `curl` - Requêtes HTTP
- `intl` - Internationalisation
- `gd` - Images (avatars)
- `xml` - Parsing XML

---

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/theonyetobe/mtnpdv.git
cd mtnpdv
```

### 2. Installer les dépendances

```bash
composer install

# En développement (avec test-pack)
composer install --dev
```

### 3. Configuration d'environnement

Copier `.env` en `.env.local`:

```bash
cp .env .env.local
```

Éditer `.env.local`:

```dotenv
###> symfony/framework-bundle ###
APP_ENV=dev
APP_DEBUG=true
APP_NAME="MTNPDV - Gestion des Points de Vente MTN"
APP_DESCRIPTION="Système de gestion complet pour les points de vente MTN Cameroon"
APP_FAVICON="https://www.mtn.cm/favicon.ico"
APP_LOGO="https://www.mtn.cm/assets/logo.png"
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://user:password@127.0.0.1:3306/mtnpdv"
# Ou pour SQLite (développement):
# DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"
###< doctrine/doctrine-bundle ###

###> symfony/mailer ###
MAILER_DSN=smtp://localhost:1025
###< symfony/mailer ###

###> knp/knp-paginator-bundle ###
PAGINATOR_LIMIT=20
###< knp/knp-paginator-bundle ###

# Paramètres métier
TOLERANCE_METRES=100          # Rayon géolocalisation visites
PDF_EXPORT_ENABLED=true       # Export rapports PDF
```

### 4. Créer la base de données

```bash
# Créer la BD vide
php bin/console doctrine:database:create

# Générer le schéma
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

### 5. Initialiser les données

```bash
# Mode interactif (demande confirmations)
php bin/console app:init:database

# Mode auto (data fixture complète)
echo -e "yes\nyes" | php bin/console app:init:database
```

**Résultat:**
- ✅ Schéma créé
- ✅ 3 rôles: ADMIN, AGENT, GERANT
- ✅ 26 agences MTN
- ✅ 46 produits MTN
- ✅ 1 admin, 15 agents, 8 gérants
- ✅ 400+ transactions test
- ✅ 204 notifications

### 6. Créer un utilisateur admin

```bash
# Interactif
php bin/console app:utilisateur:creer

# Entrées:
# Nom: Dupont
# Prenom: Jean
# Email: admin@mtnpdv.local
# Telephone: +237690123456
# Password: admin123
# Roles: ADMIN
```

### 7. Créer les dossiers d'uploads

```bash
mkdir -p public/uploads/preuves
mkdir -p public/uploads/profils
mkdir -p public/uploads/pos
chmod -R 755 public/uploads
```

### 8. Générer les assets

```bash
# Dev avec hot reload
php bin/console assets:install
npm run watch

# Ou simplement (CDN)
php bin/console asset-map:compile
```

### 9. Démarrer le serveur

```bash
# Serveur intégré
php bin/console server:run

# Ou avec Symfony CLI
symfony server:start -d
```

**Accéder:** http://localhost:8000

---

## 🔐 Configuration Production

### 1. Variables d'environnement

```dotenv
APP_ENV=prod
APP_DEBUG=false

# MySQL produit
DATABASE_URL="mysql://user:secure_password@prod-db.example.com:3306/mtnpdv"

# Mailer
MAILER_DSN="smtp://smtp.sendgrid.net:587?encryption=tls&username=apikey&password=YOUR_SENDGRID_KEY"
```

### 2. Optimisation

```bash
# Cache clear
php bin/console cache:clear --env=prod

# Assets
php bin/console assets:install --env=prod --no-debug

# Preload opcache
php bin/console cache:warmup --env=prod
```

### 3. Permissions fichiers

```bash
chmod -R 755 public/
chmod -R 777 var/log/
chmod -R 777 var/cache/
chmod -R 777 public/uploads/
```

### 4. Sécurité

```bash
# Générer clé CSRF unique
php bin/console secrets:set DATABASE_PASSWORD

# SSL/TLS (obligatoire)
# Configurer certificat Let's Encrypt

# .htaccess ou Nginx
# Redirection HTTP → HTTPS
# Bloc /var, /config
```

### 5. Backup Base de données

```bash
# MySQL dump quotidien
mysqldump -u user -p mtnpdv > backups/mtnpdv_$(date +%Y%m%d).sql

# Restauration
mysql -u user -p mtnpdv < backups/mtnpdv_20260701.sql
```

---

## 🧪 Configuration Tests

### .env.test

Déjà configuré pour SQLite en mémoire:

```dotenv
APP_ENV=test
DATABASE_URL="sqlite:///:memory:"
APP_DEBUG=true
```

### Exécuter les tests

```bash
# Tous
php bin/phpunit tests/

# Avec couverture
php bin/phpunit tests/ --coverage-html build/coverage

# Un seul fichier
php bin/phpunit tests/Domain/ValueObject/MontantTest.php
```

---

## 🛠️ Configuration Services

### Docker Compose (optionnel)

Créer `docker-compose.yml`:

```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: mtnpdv
      MYSQL_USER: mtnpdv
      MYSQL_PASSWORD: secret123
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  php:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    environment:
      DATABASE_URL: "mysql://mtnpdv:secret123@mysql:3306/mtnpdv"
    depends_on:
      - mysql
    volumes:
      - .:/app

  mailhog:
    image: mailhog/mailhog
    ports:
      - "1025:1025"
      - "8025:8025"

volumes:
  mysql_data:
```

Démarrer:
```bash
docker-compose up -d
php bin/console doctrine:database:create
php bin/console app:init:database
```

---

## 📦 Maintenance

### Mises à jour dépendances

```bash
# Vérifier vulnérabilités
composer audit

# Mettre à jour
composer update

# Sécurité seulement
composer update --only=security
```

### Cache

```bash
# Vider cache
php bin/console cache:clear

# Réchauffer
php bin/console cache:warmup

# Prod seulement
php bin/console cache:clear --env=prod
```

### Logs

```bash
# Voir les logs
tail -f var/log/dev.log

# Prod
tail -f var/log/prod.log
```

### Base de données

```bash
# Valider schéma
php bin/console doctrine:schema:validate

# Synchroniser
php bin/console doctrine:schema:update --force

# Migrations
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:execute --up VERSION
```

---

## 🐛 Dépannage

### "Command not found: app:init:database"

```bash
# Vérifier services.yaml
php bin/console debug:container | grep init:database

# Recacher
php bin/console cache:clear
```

### "PDO Exception: SQLSTATE[HY000]"

Vérifier:
- Credentials `.env.local`
- Serveur MySQL lancé
- BD créée

```bash
php bin/console doctrine:database:create --if-not-exists
```

### "Unable to generate a URL"

Vérifier routes dans `config/routes.yaml`:
```bash
php bin/console debug:router
```

### Photos ne s'uploadent pas

```bash
# Vérifier dossier
ls -la public/uploads/

# Permissions
chmod -R 777 public/uploads/

# VichUploader config
php bin/console debug:config vich_uploader
```

### Tests échouent

```bash
# Vérifier DB test
php bin/phpunit tests/ -vv

# Schéma OK?
php bin/console --env=test doctrine:schema:validate

# Problème spécifique?
php bin/phpunit tests/Domain/ValueObject/MontantTest.php -vv
```

---

## ✅ Checklist post-installation

- [ ] PHP 8.4+ (`php -v`)
- [ ] Composer 2.0+ (`composer --version`)
- [ ] Dépendances installées (`vendor/` existe)
- [ ] `.env.local` configuré
- [ ] BD créée et migrée
- [ ] Données initialisées (26 PDV visibles)
- [ ] Admin créé et testé
- [ ] Dossiers uploads avec droits 777
- [ ] Serveur lancé et accessible
- [ ] Tests passants (`php bin/phpunit`)

---

## 🎯 Prochaines étapes

### Développement
```bash
# Serveur avec hot reload
symfony server:start -d
npm run watch

# Logs en direct
tail -f var/log/dev.log

# Console
php bin/console
```

### Déploiement
```bash
# Build assets
npm run build
php bin/console asset-map:compile

# Cache clear
php bin/console cache:clear --env=prod

# Migration
php bin/console doctrine:migrations:migrate --env=prod

# Tests prod
php bin/phpunit --env=prod
```

---

**Version:** 1.0  
**Dernière mise à jour:** 2026-07-02
