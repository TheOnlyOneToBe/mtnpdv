# 🛠️ Commandes Utiles - MTNPDV

Ce document décrit toutes les commandes console utiles pour développer et maintenir MTNPDV.

## 🚀 Setup et Initialisation

### Setup Automatique du Projet
```bash
php bin/console app:setup
# ou
./setup.sh
```
**Description:** Installe toutes les dépendances, crée la BD, exécute les migrations et configure les uploads.

### Initialisation Développement Complet
```bash
php bin/console app:dev:init
```
**Description:** Équivalent du setup mais avec génération automatique de données de test. Lance:
1. `app:setup` - Configuration du projet
2. `app:db:reset` - Réinitialisation BD
3. `app:fixtures:generate` - Données de test
4. `app:health` - Vérification santé

**Parfait pour:** Première mise en place complète de l'environnement de développement.

---

## 📊 Santé et Diagnostic

### Vérifier la Santé de l'Application
```bash
php bin/console app:health
```
**Vérifie:**
- ✅ Connexion à la base de données
- ✅ Dossiers uploads accessibles
- ✅ Fichiers de configuration présents
- ✅ Configuration PHP

**Affiche:** Tableau avec statut de chaque composant.

### Lister Toutes les Routes
```bash
php bin/console app:routes:list

# Filtrer par nom ou contrôleur
php bin/console app:routes:list --filter=admin

# Filtrer par rôle (si implémenté)
php bin/console app:routes:list --role=ADMIN
```
**Description:** Affiche toutes les routes groupées par contrôleur avec méthodes et chemins.

---

## 🗄️ Base de Données

### Réinitialiser la Base de Données (DEV UNIQUEMENT)
```bash
php bin/console app:db:reset

# Sans confirmation
php bin/console app:db:reset -n
```
**⚠️ ATTENTION:** Supprime TOUTES les données! À utiliser uniquement en développement.

**Fait:**
1. Supprime la base de données
2. Recrée la base de données
3. Exécute toutes les migrations

**Cas d'usage:**
- Nettoyer avant de committer
- Recommencer depuis zéro après des changements de schéma
- Tester les migrations

### Fixtures de Test
```bash
php bin/console app:fixtures:generate

# Avec options
php bin/console app:fixtures:generate --count=20 --agents=10
```
**Génère:**
- 1 Admin de test: `admin.test@example.com / AdminTest123!`
- N Agents: `agent1@example.com / Agent123!`... `agentN@example.com / Agent123!`
- N Points de Vente avec gérants associés

**Options:**
- `--count=10` (défaut) - Nombre de PDV à créer
- `--agents=5` (défaut) - Nombre d'agents à créer

**Cas d'usage:**
- Tester l'application avec données réalistes
- Développer les dashboards
- Tester les permissions

---

## 💾 Caches et Performance

### Vider Tous les Caches
```bash
php bin/console app:cache:clear-all

# Inclure aussi les logs
php bin/console app:cache:clear-all --include-logs
```
**Nettoie:**
- `var/cache` - Cache application
- `var/sessions` - Sessions PHP
- `public/uploads/temp` - Uploads temporaires
- (optionnel) `var/log` - Fichiers logs

**Plus complet que** `cache:clear` standard.

### Optimiser pour la Production
```bash
php bin/console app:optimize
```
**Fait:**
1. Nettoie le cache avec `--no-warmup`
2. Réchauffe le cache avec `cache:warmup`
3. Compile les assets
4. Vérifie OPCache PHP

**Cas d'usage:**
- Avant un déploiement
- Améliorer les performances de production
- Préparer une version stable

---

## 👤 Gestion Utilisateurs

### Créer un Utilisateur
```bash
php bin/console app:utilisateur:creer

# Exemple
Prénom: Jean
Nom: Dupont
Email: jean@example.com
Téléphone: +237123456789
Mot de passe: SecurePassword123!
Rôles: ADMIN
```
**Description:** Crée un nouvel utilisateur avec rôle(s) spécifié(s).

---

## 📝 Commandes Usuelles Symfony

### Vider le Cache Standard
```bash
php bin/console cache:clear
```

### Voir l'Environnement
```bash
php bin/console debug:env APP_ENV
```

### Vérifier la Configuration
```bash
php bin/console lint:container
```

### Valider le Schéma Doctrine
```bash
php bin/console doctrine:schema:validate --skip-sync
```

### Afficher les Variables Globales Twig
```bash
php bin/console debug:config twig
```

---

## 🧪 Tests

### Lancer Tous les Tests
```bash
php bin/phpunit
```

### Lancer un Test Spécifique
```bash
php bin/phpunit --filter=VisiteTest
php bin/phpunit tests/Domain/Entity/TransactionTest.php
```

### Avec Coverage
```bash
php bin/phpunit --coverage-html=coverage
```

---

## 🚀 Déploiement

### Checklist Pré-Déploiement
```bash
# 1. Vérifier la santé
php bin/console app:health

# 2. Valider la config
php bin/console lint:container

# 3. Valider le schéma
php bin/console doctrine:schema:validate --skip-sync

# 4. Lancer les tests
php bin/phpunit --no-coverage

# 5. Optimiser
php bin/console app:optimize

# 6. Vider les sessions
rm -rf var/sessions/*
```

---

## 📚 Variables d'Environnement Utiles

```bash
# Dev
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL="mysql://user:pass@localhost/mtnpdv"

# Test
APP_ENV=test
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"

# Production
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL="mysql://user:pass@prod-server/mtnpdv"
```

---

## 💡 Tips & Tricks

### Développement Fluide
```bash
# 1. Première fois
./setup.sh

# 2. Recommencer avec données de test
php bin/console app:dev:init

# 3. Développer
symfony serve
# Accès: http://localhost:8000
# Admin: admin.test@example.com / AdminTest123!

# 4. Avant commit
php bin/phpunit
php bin/console app:health
```

### Debug d'une Route
```bash
php bin/console debug:router app_admin_dashboard
```

### Debug d'un Service
```bash
php bin/console debug:container App\Application\Visite\EnregistrerVisiteHandler
```

### Voir les Tâches Planifiées (si Scheduler)
```bash
php bin/console debug:scheduler
```

---

## 🔗 Références

- [Symfony Console](https://symfony.com/doc/current/console.html)
- [Doctrine Migrations](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html)
- [PHPUnit](https://phpunit.de/)
- [SETUP.md](./SETUP.md) - Installation du projet
