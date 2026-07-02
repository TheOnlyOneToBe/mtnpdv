# MTNPDV - Gestion des Points de Vente MTN Cameroon

## 📱 Vue d'ensemble

**MTNPDV** est une application web de gestion complète des points de vente MTN Cameroon. Elle permet aux administrateurs, agents terrain et gérants de kiosque de gérer efficacement les produits, les transactions et les opérations commerciales.

### 🎯 Objectifs principaux

- **Gestion centralisée** des 26 agences MTN à travers le Cameroon
- **Suivi temps réel** des transactions commerciales et visites terrain
- **Autonomie commerciale** pour les gérants de kiosque (validation directe des ventes)
- **Traçabilité complète** avec GPS, photos et commentaires
- **Reporting détaillé** par administrateur

---

## 👥 Trois rôles utilisateurs

### 1. **ADMIN** (Administrateur)
- Gestion complète du système
- Création et modification des points de vente
- Gestion des utilisateurs (admin, agents, gérants)
- Validation/rejet des visites terrain
- Accès aux rapports et statistiques
- Route: `/admin/dashboard`

### 2. **AGENT** (Agent terrain)
- Enregistrement des visites de contrôle
- Capture de preuves (photos GPS)
- Consultation de ses propres visites
- Carte interactive des PDV proches
- Route: `/agent/dashboard`

### 3. **GERANT** (Gérant de kiosque)
- **Gestion autonome de ses produits**
- **Enregistrement direct des ventes** (validation immédiate)
- Recherche et filtrage des produits
- Consultation historique des transactions
- Route: `/gerant/dashboard`

---

## 🏗️ Architecture générale

```
MTNPDV
├── Domain/ (Métier - DDD)
│   ├── Entity/ (Entités métier)
│   ├── ValueObject/ (Objets valeur)
│   ├── Enum/ (Énumérations)
│   └── Repository/ (Contrats d'accès aux données)
├── Application/ (Cas d'usage - Handlers)
│   ├── Visite/ (Visites terrain)
│   ├── Gerant/ (Ventes des gérants)
│   └── Utilisateur/ (Gestion profil)
├── Infrastructure/ (Implémentations concrètes)
│   ├── Doctrine/ (ORM + Repositories)
│   ├── Security/ (Authentification + Voters)
│   ├── Upload/ (Gestion des photos)
│   └── Console/ (Commandes CLI)
├── Controller/ (Points d'entrée HTTP)
│   ├── Admin/
│   ├── Agent/
│   └── Gerant/
└── Templates/ (Vues Twig)
    ├── admin/
    ├── agent/
    └── gerant/
```

---

## 🗄️ Données principales

### Entités métier
- **Utilisateur** - Comptes d'accès (admin, agents, gérants)
- **PointVente** - Kiosques MTN (26 agences Cameroon)
- **Produit** - Services MTN (46 produits)
- **Transaction** - Ventes et visites (400+ transactions)
- **FluxRavitaillement** - Livraisons de stock
- **Notification** - Alertes pour les utilisateurs
- **Role** - Permissions (ADMIN, AGENT, GERANT)

### Objets valeur (Value Objects)
- **Montant** - Représentation monétaire en FCFA (centimes)
- **Email** - Adresses email validées
- **Telephone** - Numéros de téléphone Cameroon (+237)
- **Coordonnees** - Positions GPS (latitude/longitude)

### Énumérations
- **StatutUtilisateur** - ACTIF, INACTIF
- **StatutPointVente** - ACTIF, FERME, SUSPENDU
- **StatutTransaction** - EN_ATTENTE, VALIDEE, REJETEE, ANNULEE
- **StatutFlux** - EN_ATTENTE, VALIDE, EXPEDIE, LIVRE
- **TypeTransaction** - VENTE, VISITE, AUTRE

---

## 🚀 Technologie

| Technologie | Version | Usage |
|---|---|---|
| **Symfony** | 7.2 | Framework web |
| **PHP** | 8.4 | Langage backend |
| **Doctrine ORM** | 2.16 | Persistence données |
| **Bootstrap 5** | 5.3 | UI/Responsive design |
| **Leaflet** | 1.9 | Cartes interactives |
| **VichUploader** | 2.3 | Gestion fichiers photos |
| **PHPUnit** | 13.2 | Tests automatisés |

---

## 📊 Statistiques du projet

- **139+ tests** (unitaires, intégration, fonctionnels)
- **9 entités métier** avec architecture DDD
- **4 value objects** pour les données sensibles
- **5 enums** pour les états et types
- **26 points de vente** MTN réels au Cameroon
- **46 produits** MTN avec tarifs réels
- **400+ transactions** générées pour tests

---

## 📚 Documentation complète

Consultez les autres fichiers pour des détails spécifiques:

- 📖 **[ARCHITECTURE.md](ARCHITECTURE.md)** - Architecture DDD et patterns utilisés
- 📦 **[PACKAGES.md](PACKAGES.md)** - Toutes les dépendances et configurations
- ⚙️ **[SERVICES.md](SERVICES.md)** - Handlers, repositories, services
- ✨ **[FONCTIONNALITES.md](FONCTIONNALITES.md)** - Détail de chaque feature
- 🛠️ **[INSTALLATION.md](INSTALLATION.md)** - Setup et configuration
- 📖 **[UTILISATION.md](UTILISATION.md)** - Guide par rôle utilisateur
- 💻 **[COMMANDES.md](COMMANDES.md)** - Commandes console disponibles
- 🧪 **[TESTS.md](TESTS.md)** - Structure et exécution des tests

---

## 🔐 Sécurité

- **Authentification** : Form login avec email/mot de passe
- **Autorisation** : Voters Symfony pour chaque action
- **Hachage** : Bcrypt pour les mots de passe
- **CSRF Protection** : Tokens Symfony
- **Validation** : Côté serveur et client
- **GPS Validation** : Vérification de proximité pour les visites

---

## 🌍 Données MTN Cameroon

### 26 Agences réelles
- Douala (Bonanjo, Akwa, Deido, New Bell, etc.)
- Yaoundé (Centre-ville, Bastos, Mpodol, etc.)
- Bamenda, Buea, Kribi, Limbé
- Toutes avec coordonnées GPS réelles

### 46 Produits MTN
- Cartes SIM
- Crédits de communication
- Services premium
- Avec tarification officielle FCFA

---

## 📝 License

© 2026 MTN Cameroon - Tous droits réservés

---

**Version:** 1.0  
**Dernière mise à jour:** 2026-07-02  
**Framework:** Symfony 7.2
