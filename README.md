# MTNPDV - Système de Gestion des Points de Vente

> **Plateforme web moderne pour la gestion centralisée des points de vente, des transactions et des utilisateurs**

[![Symfony](https://img.shields.io/badge/Symfony-7.2-000000?style=flat&logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php)](https://www.php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Fonctionnalités](#fonctionnalités)
- [Installation](#installation)
- [Architecture](#architecture)
- [Documentation](#documentation)
- [Contribuer](#contribuer)

## 🎯 Vue d'ensemble

MTNPDV est une application web de gestion complète pour les réseaux de points de vente. Elle permet aux administrateurs de gérer les PDV, les utilisateurs, les transactions et les rapports de manière centralisée et efficace.

### Technologies principales

- **Backend**: Symfony 7.2 (PHP 8.2+)
- **Frontend**: Bootstrap 5.3, Leaflet.js, Stimulus, Turbo
- **Database**: MySQL/MariaDB (SQLite pour dev/tests)
- **Architecture**: Domain-Driven Design (DDD)

## ✨ Fonctionnalités

### 🔐 Authentification & Autorisation
- Login sécurisé par email et mot de passe
- 3 rôles d'utilisateurs: Admin, Agent, Gérant
- Contrôle d'accès basé sur les rôles (RBAC)
- Protection CSRF sur tous les formulaires

### 📍 Gestion des Points de Vente
- CRUD complet des PDV
- Recherche en temps réel (60 req/min par utilisateur)
- Pagination (12 PDV par page)
- Affichage en liste et sur carte Leaflet
- Localisation GPS des PDV
- Statuts: ACTIF, FERME, SUSPENDU
- Filtrage par département et gérant

### 👥 Gestion des Utilisateurs
- CRUD des utilisateurs
- Attribution de rôles
- Gestion des mots de passe
- Statuts: ACTIF, INACTIF
- Profil personnel modifiable
- Changement de mot de passe sécurisé

### 💰 Gestion des Transactions
- Enregistrement des visites terrain
- Support des types: VENTE, VISITE
- Statuts: EN_ATTENTE, VALIDEE, REJETEE
- Montants en centimes (précision garantie)
- Validation par l'admin
- Calcul automatique du chiffre d'affaires
- Historique complet

### 📊 Rapports & Analytics
- Dashboard avec 5 graphiques interactifs
- Rapport PDV (statuts, gérants)
- Rapport Transactions (7 derniers jours)
- Rapport Utilisateurs (répartition par rôle)
- Statistiques en temps réel

### 🔔 Notifications
- System de toasts (succès, erreur, attention, info)
- Intégration Symfony Flash Messages
- Auto-fermeture après 5 secondes
- Non-intrusif (coin supérieur droit)

### 🗺️ Cartographie
- Carte interactive Leaflet
- Marqueurs colorés par statut
- Géolocalisation de l'utilisateur
- Recherche géographique
- Vue hybride liste/carte

## 🚀 Installation

### Prérequis

- PHP 8.2+
- Composer
- MySQL 8.0+ / MariaDB 10.5+
- Node.js 18+ (pour les assets)

### Étapes

1. **Cloner le projet**
```bash
git clone https://github.com/TheOnlyOneToBe/mtnpdv.git
cd mtnpdv
```

2. **Installer les dépendances**
```bash
composer install
npm install
```

3. **Configuration**
```bash
cp .env .env.local
# Éditer .env.local avec vos paramètres
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

4. **Créer un utilisateur admin**
```bash
php bin/console app:utilisateur:creer \
  --email=admin@example.com \
  --prenom=Admin \
  --nom=User \
  --telephone=000000000 \
  --password=SecurePassword123! \
  --role=ADMIN
```

5. **Démarrer le serveur**
```bash
symfony server:start
# Ou
php -S localhost:8000 -t public
```

Accédez à `http://localhost:8000` et connectez-vous!

## 🏗️ Architecture

### Structure du projet

```
mtnpdv/
├── src/
│   ├── Application/          # Handlers métier
│   ├── Controller/           # Contrôleurs HTTP
│   ├── Domain/              # Logique métier (DDD)
│   │   ├── Entity/          # Entités
│   │   ├── Enum/            # Énumérations
│   │   ├── Repository/      # Interfaces repo
│   │   └── ValueObject/     # Value Objects
│   └── Infrastructure/      # Implémentations
│       ├── Doctrine/        # Doctrine ORM
│       ├── Pagination/      # Service pagination
│       ├── RateLimit/       # Rate limiting
│       ├── Security/        # Voters, authentification
│       └── Upload/          # Gestion des uploads
├── templates/               # Templates Twig
├── public/
│   ├── js/                 # Stimulus controllers
│   ├── css/                # Styles
│   └── uploads/            # Fichiers uploadés
├── tests/                  # Tests (unitaires, intégration)
├── migrations/             # Migrations Doctrine
└── docs/                   # Documentation
```

### Patterns utilisés

- **Domain-Driven Design**: Séparation claire domaine/infrastructure
- **Repository Pattern**: Abstraction de la persistance
- **Value Objects**: Montant, Email, Telephone, Coordonnees
- **Enums**: Statuts, types, rôles
- **RBAC**: Voters Symfony pour l'autorisation

## 📚 Documentation

Consultez les guides détaillés:

- **[SETUP.md](docs/SETUP.md)** - Configuration avancée et déploiement
- **[ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md)** - Guide complet pour les administrateurs
- **[TOASTS_GUIDE.md](docs/TOASTS_GUIDE.md)** - Système de notifications
- **[ARCHITECTURE.md](docs/ARCHITECTURE.md)** - Architecture technique détaillée
- **[FEATURES.md](docs/FEATURES.md)** - Liste complète des fonctionnalités
- **[ROLES_PERMISSIONS.md](docs/ROLES_PERMISSIONS.md)** - Matrice des rôles et permissions

## 🔧 Configuration

### Variables d'environnement principales

```bash
# Database
DATABASE_URL=mysql://user:password@localhost/mtnpdv

# Symfony
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=your-secret-key

# Upload
UPLOAD_DIR=public/uploads
MAX_UPLOAD_SIZE=5242880

# Email (optionnel)
MAILER_DSN=smtp://localhost:25
```

Voir `.env.example` pour la liste complète.

## 📊 Utilisateurs de test

Créez des utilisateurs avec différents rôles:

```bash
# Admin
php bin/console app:utilisateur:creer \
  --email=admin@mtnpdv.test \
  --prenom=Admin \
  --nom=Test \
  --telephone=651000001 \
  --password=Test123456 \
  --role=ADMIN

# Agent (terrain)
php bin/console app:utilisateur:creer \
  --email=agent@mtnpdv.test \
  --prenom=Agent \
  --nom=Test \
  --telephone=651000002 \
  --password=Test123456 \
  --role=AGENT

# Gérant (kiosque)
php bin/console app:utilisateur:creer \
  --email=gerant@mtnpdv.test \
  --prenom=Gérant \
  --nom=Test \
  --telephone=651000003 \
  --password=Test123456 \
  --role=GERANT
```

## 🧪 Tests

### Exécuter la suite de tests

```bash
# Tous les tests
php bin/phpunit

# Uniquement tests unitaires
php bin/phpunit tests/Unit/

# Uniquement tests d'intégration
php bin/phpunit tests/Integration/

# Uniquement tests fonctionnels
php bin/phpunit tests/Functional/

# Avec couverture de code
php bin/phpunit --coverage-html coverage/
```

### Configuration de test

Les tests utilisent SQLite en mémoire (`.env.test`):

```bash
DATABASE_URL=sqlite:///:memory:
```

## 🚀 Déploiement

### Production

1. **Configuration**
```bash
export APP_ENV=prod
export APP_DEBUG=false
```

2. **Build**
```bash
composer install --no-dev --optimize-autoloader
npm run build
php bin/console cache:clear
```

3. **Permissions**
```bash
chmod -R 755 var/
chmod -R 755 public/uploads/
```

4. **Migrations**
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

5. **Restart PHP-FPM**
```bash
sudo systemctl restart php8.2-fpm
```

Voir [SETUP.md](docs/SETUP.md) pour les détails avancés.

## 🐛 Dépannage

### Erreurs courantes

**Problème**: `No database found`
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**Problème**: Permissions sur `var/` ou `public/uploads/`
```bash
chmod -R 777 var/
chmod -R 777 public/uploads/
```

**Problème**: Cache Symfony obsolète
```bash
php bin/console cache:clear --all
```

## 📞 Support

- 📧 Email: support@mtnpdv.test
- 📋 Issues: [GitHub Issues](https://github.com/TheOnlyOneToBe/mtnpdv/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/TheOnlyOneToBe/mtnpdv/discussions)

## 📄 Licence

Ce projet est sous licence MIT. Voir [LICENSE](LICENSE) pour les détails.

## 👥 Auteurs

- **Développement**: Claude & Équipe
- **2026** - MTNPDV Project

---

**Dernière mise à jour**: 2026-07-02  
**Version**: 1.0.0
