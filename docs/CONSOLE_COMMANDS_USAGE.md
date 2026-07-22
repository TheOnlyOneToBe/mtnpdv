# 🎯 Guide d'Utilisation des Commandes Console

**Date:** 2026-07-03  
**Objectif:** Documenter les commandes disponibles pour l'initialisation et la gestion des données

---

## 📋 Commandes Disponibles

### 1. `app:init:database` - Initialisation Complète

**Fonction:** Initialise la base de données de zéro avec migrations et données de test.

**Options:**
- `--with-data`: Générer les données de test pour 3 mois (MTN Cameroon réaliste)
- `--no-data`: Passer la génération des données
- `--force`, `-f`: Sauter les prompts de confirmation

**Utilisation:**

```bash
# Initialisation interactive (avec confirmation)
php bin/console app:init:database

# Initialisation complète sans confirmation
php bin/console app:init:database --force --with-data

# Initialisation sans données
php bin/console app:init:database --force --no-data

# Initialisation avec données spécifiques
php bin/console app:init:database --force --with-data
```

**Ce qu'elle fait:**
1. Crée/recréé la base de données
2. Valide le schéma des entités
3. Crée les rôles de base (ADMIN, AGENT, GERANT)
4. Crée les catégories (PDV et Produits)
5. (Optionnel) Génère des données de test réalistes MTN Cameroon:
   - 1 Admin
   - 15 Agents terrain
   - 8 Gérants
   - 26+ Agences MTN réelles au Cameroun
   - 50+ Produits MTN
   - Flux de ravitaillement sur 3 mois
   - 400+ Transactions/visites agents

**Données de test par défaut:**
```
Email: admin@mtnpdv.local
Mot de passe: password123
```

**Temps d'exécution:** ~30-60 secondes (selon le volume)

---

### 2. `app:seed:faker` - Génération Simple de Données

**Fonction:** Génère un ensemble cohérent et simple de fausses données pour développement.

**Options:**
- `--reset`: Supprimer les anciennes données avant la génération

**Utilisation:**

```bash
# Générer des données (sans reset)
php bin/console app:seed:faker

# Générer avec reset (supprimer anciennes données)
php bin/console app:seed:faker --reset
```

**Ce qu'elle fait:**
1. Génère des utilisateurs:
   - 1 Admin
   - 3 Agents
   - 2 Gérants
2. Crée les catégories de base
3. Génère 26 produits variés
4. Crée 15 points de vente
5. Génère flux de ravitaillement
6. Crée 60-75 transactions
7. Crée 20-30 notifications

**Avantage:** Plus simple et plus rapide que `app:init:database`

**Données de test par défaut:**
```
Admin:
  Email: admin@mtnpdv.test
  Mot de passe: password123

Agents (agent1, agent2, agent3):
  Email: agent{1,2,3}@mtnpdv.test
  Mot de passe: password123

Gérants (gerant1, gerant2):
  Email: gerant{1,2}@mtnpdv.test
  Mot de passe: password123
```

**Temps d'exécution:** ~5-10 secondes

---

## 🔄 Comparaison des Commandes

| Aspect | `app:init:database` | `app:seed:faker` |
|--------|-------------------|------------------|
| **Fonction** | Initialisation complète | Génération de données |
| **Crée BD** | ✅ Oui | ❌ Non |
| **Migrations** | ✅ Oui | ❌ Non |
| **Reset Données** | ✅ Oui (automatique) | ✅ Option `--reset` |
| **Rôles** | ✅ Crée | ✅ Utilise existants |
| **Utilisateurs** | 1 + 15 agents + 8 gérants | 1 + 3 agents + 2 gérants |
| **PDV** | 26+ agences MTN réelles | 15 PDV génériques |
| **Produits** | 50+ Services MTN réalistes | 26 Produits génériques |
| **Transactions** | 400+ sur 3 mois | 60-75 sur période récente |
| **Temps** | ~30-60 secondes | ~5-10 secondes |
| **Contexte** | Production-ready | Développement local |

---

## 🎬 Workflows Recommandés

### Workflow 1: Premier Setup (Recommandé)

```bash
# Initialiser complètement avec données MTN réalistes
php bin/console app:init:database --force --with-data

# ✅ Résultat: BD prête avec 3 mois de données
```

### Workflow 2: Setup Minimal + Données Simples

```bash
# Créer la BD sans données
php bin/console app:init:database --force --no-data

# Générer des données simples
php bin/console app:seed:faker

# ✅ Résultat: BD avec données minimales
```

### Workflow 3: Nettoyer et Régénérer

```bash
# Réinitialiser complètement
php bin/console app:init:database --force --with-data

# OU

# Juste régénérer les fausses données
php bin/console app:seed:faker --reset
```

### Workflow 4: Développement Itératif

```bash
# Premier setup
php bin/console app:init:database --force --with-data

# Pendant le développement, régénérer rapidement si besoin
php bin/console app:seed:faker --reset

# Retour à l'état initial
php bin/console app:init:database --force --with-data
```

---

## 📊 Stratégie de Génération - Ordre de Création

### Pour `app:init:database`:

```
1. SchemaTool: Crée le schéma de la BD
2. Rôles: ADMIN, AGENT, GERANT
3. Catégories: PDV et Produits
4. Utilisateurs: Admin + Agents + Gérants
5. Produits MTN: 50+ produits réalistes
6. Agences MTN: 26 agences au Cameroun
7. Flux Ravitaillement: Ravitaillement sur 3 mois
8. Transactions: Visites agents sur 3 mois
9. Notifications: Notifications correspondantes
```

### Pour `app:seed:faker`:

```
1. Rôles: ADMIN, AGENT, GERANT (ou uses existants)
2. Utilisateurs: Admin + Agents + Gérants
3. Catégories PDV: 5 catégories génériques
4. Catégories Produits: 4 catégories génériques
5. Produits: 26 produits variés
6. Points de Vente: 15 PDV génériques
7. Flux Ravitaillement: 1-2 par PDV
8. Transactions: 3-5 par PDV
9. Notifications: Aléatoires par utilisateur
```

---

## 🔐 Sécurité & Notes Importantes

### Mots de Passe

⚠️ **ATTENTION:** Tous les utilisateurs de test reçoivent le même mot de passe:
```
password123
```

**CECI EST À UTILISER UNIQUEMENT EN DÉVELOPPEMENT/TEST**

Jamais en production!

### Unicité des Données

✅ **Garanties:**
- Emails uniques
- Code Ref PDV uniques
- Factureuniq uniques
- Pas de doublons de roles

### Validations

✅ **Respectées:**
- Enums (StatutTransaction, StatutPointVente, etc.)
- Value Objects (Email, Telephone, Montant, Coordonnees)
- Contraintes métier (quantités > 0, montants valides, etc.)

---

## 🛠️ Troubleshooting

### Erreur: "Foreign key check failed"

```bash
# Essayer avec --force
php bin/console app:init:database --force
```

### Erreur: "Email already exists"

Les données ne ont pas été complètement supprimées. Utiliser:
```bash
php bin/console app:seed:faker --reset
```

### Erreur: "Faker not found"

Vérifier que Faker est installé:
```bash
composer require fakerphp/faker
```

### La commande s'éxécute mais ne crée rien

- Vérifier que la BD est accessible
- Vérifier la variable `DATABASE_URL` dans `.env`
- Pour SQLite: S'assurer que le répertoire `var/` est accessible

---

## 📈 Monitoring & Vérification

### Après initialisation, vérifier:

```bash
# Compter les utilisateurs
php bin/console doctrine:query:dql "SELECT COUNT(u) FROM App:Utilisateur u"

# Compter les PDV
php bin/console doctrine:query:dql "SELECT COUNT(p) FROM App:PointVente p"

# Compter les transactions
php bin/console doctrine:query:dql "SELECT COUNT(t) FROM App:Transaction t"

# Lister les utilisateurs
php bin/console doctrine:query:dql "SELECT u.email, u.statut FROM App:Utilisateur u"
```

### Tester un login:

```bash
# Utiliser l'interface web
# Email: admin@mtnpdv.local (ou admin@mtnpdv.test si app:seed:faker)
# Mot de passe: password123
```

---

## 🚀 Prochaines Étapes

1. **Lancer le serveur:**
   ```bash
   php bin/console server:run
   ```

2. **Accès à l'application:**
   ```
   http://localhost:8000
   ```

3. **Se connecter:**
   ```
   Email: admin@mtnpdv.local
   Mot de passe: password123
   ```

4. **Explorer les données:**
   - Dashboard admin
   - Liste des points de vente
   - Historique des transactions
   - Notifications

---

**Documentation validée:** 2026-07-03  
**Commandes testées:** ✅ `app:init:database` ✅ `app:seed:faker`
