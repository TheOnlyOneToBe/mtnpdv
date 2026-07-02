# 📚 Documentation MTNPDV - Index complet

Bienvenue dans la documentation complète du projet MTNPDV. Voici le guide pour naviguer dans toute la documentation.

---

## 🎯 Commencer rapidement

**Nouveau sur le projet?** Lire dans cet ordre:

1. **[README.md](README.md)** (5 min)
   - Vue d'ensemble du projet
   - Les 3 rôles utilisateurs
   - Technologie utilisée

2. **[INSTALLATION.md](INSTALLATION.md)** (15 min)
   - Comment installer localement
   - Configuration environnement
   - Lancer le serveur

3. **[FONCTIONNALITES.md](FONCTIONNALITES.md)** (20 min)
   - Toutes les features par rôle
   - Workflows complets
   - Actions disponibles

---

## 📖 Guide par type de lecteur

### 👨‍💻 Développeurs

```
1. README.md           (Contexte)
   ↓
2. INSTALLATION.md     (Setup local)
   ↓
3. ARCHITECTURE.md     (Structure DDD)
   ↓
4. PACKAGES.md         (Dépendances)
   ↓
5. SERVICES.md         (Handlers & Repositories)
   ↓
6. TESTS.md           (Couverture tests)
```

**Checklist devs:**
- [ ] Repo clonné et running
- [ ] Tous les tests passants
- [ ] `.env.local` configuré
- [ ] BD créée avec données test

### 👤 Product Managers / Métier

```
1. README.md           (Vue d'ensemble)
   ↓
2. FONCTIONNALITES.md  (Détail features)
   ↓
3. INSTALLATION.md     (Lancer pour démo)
```

**Pour tester:**
- Identifiants de test fournis
- 3 rôles prêts à tester
- Données MTN réelles

### 🚀 DevOps / Infrastructure

```
1. README.md           (Tech stack)
   ↓
2. PACKAGES.md         (Dépendances)
   ↓
3. INSTALLATION.md     (Production setup)
```

### 🧪 QA / Testeurs

```
1. FONCTIONNALITES.md  (Workflows)
   ↓
2. TESTS.md           (Couverture)
```

---

## 📑 Guide par document

### **README.md** - Le point de départ
- 📱 Vue d'ensemble application
- 👥 Présentation 3 rôles (Admin, Agent, Gérant)
- 🏗️ Architecture générale
- 🗄️ Données principales
- 🚀 Technologie utilisée
- 📊 Statistiques du projet

**À lire pour:** Comprendre le projet en 5 minutes

---

### **ARCHITECTURE.md** - Comprendre la structure
- 🏛️ Pattern DDD (Domain-Driven Design)
- 📁 Structure des dossiers
- 🔄 Flux de données complet
- 💾 Cycle de vie des entités
- 🔐 Système d'autorisation (Voters)
- 📊 Patterns utilisés (Repository, Value Objects, etc.)

**À lire pour:** Implémenter une nouvelle feature

---

### **PACKAGES.md** - Les dépendances
- 📦 Liste complète des packages
- 🎯 Raison du choix chaque package
- 🔧 Configuration Symfony
- 💻 Commandes disponibles
- 🔒 Sécurité des dépendances

**À lire pour:** Ajouter une dépendance, comprendre config

---

### **SERVICES.md** - Les cas d'usage métier
- 🎯 Vue d'ensemble services
- 📋 Détail chaque Handler
- 📦 Documentation Repositories
- 🔐 Services de sécurité
- 📸 Gestion des uploads
- 💻 Commandes console

**À lire pour:** Implémenter un handler, utiliser un repository

---

### **FONCTIONNALITES.md** - L'application en détail
- 👨‍💼 Toutes les features Admin
- 🚶 Toutes les features Agent
- 🏪 Toutes les features Gérant
- 👤 Fonctionnalités partagées
- 🔄 Workflows complets (2 scénarios)
- 📊 Tableau d'accès par rôle

**À lire pour:** Tester l'app, comprendre un workflow, créer un user story

---

### **INSTALLATION.md** - Installation & configuration
- 📋 Prérequis système
- 🚀 Installation pas à pas
- 🔐 Configuration production
- 🧪 Configuration tests
- 🛠️ Maintenance
- 🐛 Dépannage

**À lire pour:** Installer localement, déployer, déboguer

---

### **TESTS.md** - Couverture de tests
- 🧪 Vue d'ensemble (144 tests)
- 🏗️ Infrastructure tests
- 📋 Tests unitaires (Domaine)
- 🔗 Tests d'intégration (Doctrine)
- 🎯 Tests fonctionnels (Web)
- 🔐 Tests Voters
- 🚀 Exécution et rapports

**À lire pour:** Écrire des tests, vérifier couverture

---

## 🎯 Workflows par action

### "Je veux créer une nouvelle feature"
```
1. Lire ARCHITECTURE.md (patterns)
   ↓
2. Étudier SERVICES.md (handlers similaires)
   ↓
3. Coder l'entité Domain/
   ↓
4. Créer Application/Handler
   ↓
5. Ajouter Controller route
   ↓
6. Créer template Twig
   ↓
7. Tester (lire TESTS.md)
```

### "Je veux ajouter une dépendance"
```
1. Consulter PACKAGES.md (déjà présent?)
   ↓
2. composer require package/name
   ↓
3. Configurer config/services.yaml (si Handler)
   ↓
4. Tester: php bin/phpunit
```

### "Je veux tester l'app"
```
1. INSTALLATION.md (setup)
   ↓
2. FONCTIONNALITES.md (workflows)
   ↓
3. Lancer: php bin/console server:run
   ↓
4. Login avec credentials test
   ↓
5. Tester workflows
```

### "Je veux déployer en production"
```
1. INSTALLATION.md (Production section)
   ↓
2. PACKAGES.md (versions stables)
   ↓
3. TESTS.md (vérifier couverture)
   ↓
4. Backup base données
   ↓
5. Déployer avec migrations
```

### "Un test échoue"
```
1. Lire TESTS.md (infrastructure)
   ↓
2. Lancer test spécifique: php bin/phpunit test_file.php --filter test_name
   ↓
3. Vérifier SERVICES.md (handler logic)
   ↓
4. Debug avec $this->dd()
   ↓
5. Lancer suite: php bin/phpunit tests/
```

---

## 🔗 Index par fichier

| Fichier | Pages | Temps lecture | Pour qui |
|---------|-------|---------------|----------|
| **README.md** | 1 | 5 min | Tous |
| **ARCHITECTURE.md** | 4 | 20 min | Devs |
| **PACKAGES.md** | 3 | 15 min | Devs, DevOps |
| **SERVICES.md** | 5 | 25 min | Devs |
| **FONCTIONNALITES.md** | 6 | 20 min | Tous |
| **INSTALLATION.md** | 5 | 20 min | Devs, DevOps |
| **TESTS.md** | 5 | 20 min | QA, Devs |

**Temps total lecture complète:** ~2 heures

---

## 📚 Ressources externes

### Symfony
- [Symfony 7.2 Docs](https://symfony.com/doc/7.2)
- [Doctrine ORM](https://www.doctrine-project.org)

### Packages utilisés
- [VichUploader](https://symfony.com/doc/current/bundles/EasyAdminBundle)
- [Bootstrap 5](https://getbootstrap.com/docs)
- [Leaflet Maps](https://leafletjs.com)

### Bonnes pratiques
- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)

---

## 🔍 Recherche rapide

### Par concept

**Authentification**
- Security → SERVICES.md
- Login → FONCTIONNALITES.md (Authentification)
- Voters → ARCHITECTURE.md, SERVICES.md

**Base de données**
- Entités → ARCHITECTURE.md
- Repositories → SERVICES.md
- Migrations → INSTALLATION.md

**Upload fichiers**
- PhotoPreuveUploader → SERVICES.md
- Configuration → PACKAGES.md
- Dépannage → INSTALLATION.md

**Tests**
- Structure → TESTS.md
- Exécution → TESTS.md + INSTALLATION.md
- Couverture → TESTS.md

**Production**
- Setup → INSTALLATION.md
- Sécurité → PACKAGES.md
- Performance → ARCHITECTURE.md

---

## ✏️ Contribuer à la documentation

### Ajouter une section
1. Éditer le fichier pertinent
2. Suivre le format (headers, code blocks)
3. Garder les références à jour
4. Relire pour clarté

### Mettre à jour après changement code
- Mettre à jour SERVICES.md si nouveau Handler
- Mettre à jour FONCTIONNALITES.md si nouvelle feature
- Mettre à jour PACKAGES.md si nouvelles dépendances
- Mettre à jour TESTS.md si nouvelles couvertures

---

## 📞 Questions fréquentes

### "Où trouver l'API endpoint X?"
→ Consulter SERVICES.md ou FONCTIONNALITES.md

### "Comment ajouter un rôle?"
→ ARCHITECTURE.md (Voters), PACKAGES.md (config)

### "Pourquoi ce package?"
→ PACKAGES.md (section "Justifications des choix")

### "Les tests passent tous?"
→ TESTS.md (Exécution), INSTALLATION.md

### "Comment déboguer?"
→ INSTALLATION.md (Dépannage), TESTS.md

---

## 🎓 Learning Path suggéré

**Débutant (1 jour)**
```
1. README.md
2. INSTALLATION.md (local setup)
3. FONCTIONNALITES.md (tester chaque rôle)
```

**Intermédiaire (1 semaine)**
```
1-3 Débutant
4. ARCHITECTURE.md
5. SERVICES.md (un handler)
6. Modifier une feature existante
7. Écrire un test
```

**Avancé (2 semaines)**
```
1-7 Intermédiaire
8. Implémenter nouvelle feature complète
9. Ajouter dépendance
10. Déployer en environnement test
```

---

## 📊 Vue d'ensemble documentation

```
Niveau 1: README → Vue d'ensemble
   ↓
Niveau 2: INSTALLATION → Setup
   ↓
Niveau 3: FONCTIONNALITES → Qu'est-ce qu'on peut faire?
   ↓
Niveau 4: ARCHITECTURE → Comment c'est structuré?
   ↓
Niveau 5: SERVICES → Comment ça marche en détail?
   ↓
Niveau 6: TESTS → Comment vérifier que ça marche?
   ↓
Niveau 7: PACKAGES → Quels outils sont utilisés?
```

---

## ⭐ Documentation préférée par rôle

| Rôle | Starts with | Reads next | References |
|------|-------------|-----------|------------|
| **Dev Backend** | README → INSTALLATION → ARCHITECTURE | SERVICES → TESTS | PACKAGES |
| **Frontend Dev** | README → INSTALLATION → FONCTIONNALITES | ARCHITECTURE | PACKAGES |
| **DevOps** | README → INSTALLATION | PACKAGES | ARCHITECTURE |
| **QA/Tester** | README → FONCTIONNALITES → INSTALLATION | TESTS | - |
| **Manager** | README → FONCTIONNALITES | - | - |

---

## 🔄 Maintenance documentation

Documentation mises à jour à:
- Chaque release majeure
- Chaque nouveau Handler créé
- Chaque nouvelle feature
- Chaque nouveau test scenario

**Version actuelle:** 1.0  
**Dernière mise à jour:** 2026-07-02  
**Statut:** ✅ Complète et actualisée

---

**Happy coding! 🚀**

Pour des questions spécifiques, consultez le fichier documentation approprié ou posez une issue sur GitHub.
