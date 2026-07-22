# 🌱 Stratégie de Génération des Fausses Données (Seeding)

**Date:** 2026-07-03  
**Objectif:** Créer un ensemble cohérent de fausses données pour développement/test

---

## 📐 Analyse des Relations

### Graphe de Dépendances

```
Role (déjà existant: ADMIN, AGENT, GERANT)
  ↓ ManyToMany
Utilisateur
  ├─ OneToMany → Transaction (agent)
  ├─ OneToMany → FluxRavitaillement
  ├─ OneToMany → Notification
  ├─ OneToMany → PointVente (gerant)
  └─ ManyToMany → Role

CategoriePdv
  ↓ OneToMany
PointVente ← ManyToOne (Utilisateur gerant)
  ├─ OneToMany → Transaction
  └─ OneToMany → FluxRavitaillement

CategorieProd
  ↓ OneToMany
Produit

FluxRavitaillement (ManyToOne: Utilisateur, PointVente)
  ↓ OneToMany
FluxProduit (ManyToOne: Produit)

Transaction (ManyToOne: Utilisateur agent, PointVente)
  ├─ dateTransac (auto-generated)
  ├─ statut (EN_ATTENTE, VALIDEE, REJETEE, ANNULEE)
  ├─ montant (aléatoire)
  └─ coordonneesCapture (proche du PDV)

Notification (ManyToOne: Utilisateur)
```

### Dépendances d'Ordre de Création

1. **Tier 0:** Entités indépendantes (Role - déjà en base)
2. **Tier 1:** Entités sans dépendances externes
   - Utilisateur → Role
   - CategoriePdv
   - CategorieProd
3. **Tier 2:** Entités dépendant du Tier 1
   - PointVente → (Utilisateur gerant, CategoriePdv)
   - Produit → (CategorieProd)
4. **Tier 3:** Entités dépendant du Tier 2
   - FluxRavitaillement → (Utilisateur, PointVente)
   - Transaction → (Utilisateur agent, PointVente)
5. **Tier 4:** Entités dépendant du Tier 3
   - FluxProduit → (FluxRavitaillement, Produit)
6. **Tier 5:** Notifications (PointVente → Utilisateur)
   - Notification → (Utilisateur)

---

## 🎯 Plan de Génération Cohérent

### Phase 1: Utilisateurs (Tier 1)

**Rôles (déjà en base):**
- ADMIN
- AGENT
- GERANT

**À générer:**
- **1 utilisateur ADMIN:**
  - Nom: "Admin" / Prénom: "Système"
  - Email: admin@mtnpdv.test
  - Tél: +237671234567
  - Statut: ACTIF

- **3 utilisateurs AGENT:**
  - Noms réalistes (Dupont, Martin, Bernard)
  - Emails: agent1@mtnpdv.test, agent2@mtnpdv.test, agent3@mtnpdv.test
  - Tél: +237671234568, +237671234569, +237671234570
  - Statut: ACTIF

- **2 utilisateurs GERANT:**
  - Noms réalistes (Diop, Sow)
  - Emails: gerant1@mtnpdv.test, gerant2@mtnpdv.test
  - Tél: +237671234571, +237671234572
  - Statut: ACTIF

**Total: 6 utilisateurs**

### Phase 2: Catégories (Tier 1)

**CategoriePdv (5 catégories):**
1. "Supérette" - Petits commerces
2. "Boutique" - Petits magasins
3. "Kiosque" - Points de vente très petits
4. "Épicerie" - Produits frais
5. "Marché" - Espaces commerciaux

**CategorieProd (4 catégories):**
1. "Boissons" (type: "BOISSON")
2. "Snacks" (type: "ALIMENTAIRE")
3. "Hygiène" (type: "COSMETIQUE")
4. "Autres" (type: "DIVERS")

**Total: 9 catégories**

### Phase 3: Produits (Tier 2)

**Par catégorie produit: 5-8 produits**
- Boissons: 8 produits (eau, sodas, jus, alcools)
- Snacks: 6 produits (biscuits, chips, chocolats)
- Hygiène: 7 produits (savon, dentifrice, shampoing)
- Autres: 5 produits (divers)

**Total: 26 produits**

**Montants:** 500 FCFA (eau) à 50 000 FCFA (alcools)

### Phase 4: Points de Vente (Tier 2)

**15 points de vente:**
- Répartis sur 5 catégories (3 par catégorie)
- Assignés à 2 gérants (7-8 PDV par gérant)
- Localisés à proximité de Douala (coordonnées réalistes)
- Ville: Douala
- Rayon GPS: ±0.01° (≈1km)

**Coordonnées de base (Douala):** 3.8667° N, 11.5167° E

Exemple PDV:
```
Nom: "PDV Centre Ville 01"
CodeRef: "PDV-001"
Catégorie: "Supérette"
Gerant: gerant1@mtnpdv.test
Coordonnées: 3.8667 ± 0.005, 11.5167 ± 0.005
Téléphone: +237671234567
Adresse: "Centre-Ville, Douala"
Statut: ACTIF
```

**Total: 15 points de vente**

### Phase 5: Flux de Ravitaillement (Tier 3)

**Par PDV: 1-2 flux**

**Stratégie:**
1. Pour chaque PDV, générer 1-2 flux de ravitaillement
2. Flux facteur: "FLUX-PDV{id}-{date}-{random}"
3. Statut: 50% EN_ATTENTE, 50% LIVRE
4. Lignes par flux: 3-8 produits

**Calcul montant total:**
```
Pour chaque produit de la catégorie correspondante:
  quantité = random(5, 50)
  prixUnitaire = produit.prixUnitaire
  sousTotal = quantité × prixUnitaire
  montantTotal = somme de tous les sous-totaux
```

**Total: 15-30 flux de ravitaillement**

### Phase 6: Transactions (Tier 3)

**Par PDV: 3-5 transactions**

**Stratégie:**
1. Pour chaque PDV, générer 3-5 transactions (visites agents)
2. Type: VISITE (nouveau type)
3. Statut: 40% EN_ATTENTE, 40% VALIDEE, 20% REJETEE
4. Montant: 0-100 000 FCFA (selon produits achetés)
5. Agent: assigné aléatoirement parmi les 3 agents
6. Coordonnées: proximité du PDV (rayon: ±50m)
7. Date: dans les 30 derniers jours

**Calcul coordonnées GPS:**
```
Pour chaque transaction:
  latitude = pdv.latitude ± random(0.0005, 0.0015)  # ±50-150m
  longitude = pdv.longitude ± random(0.0005, 0.0015)
  
Logique de statut:
  EN_ATTENTE: visite non encore validée par admin
  VALIDEE: visite acceptée (agent était effectivement à proximité)
  REJETEE: visite refusée (trop loin du PDV)
  ANNULEE: visite annulée pour raison quelconque
```

**Total: 45-75 transactions**

### Phase 7: Notifications (Tier 5)

**Par utilisateur: 2-5 notifications**

**Stratégie:**
1. Notifications de validation de visites (VISITE_VALIDEE, VISITE_REJETEE)
2. Notifications de livraison (FLUX_LIVRE)
3. Notifications administratives (ALERTE_GERANT)

**Total: 12-30 notifications**

---

## 📋 Ordre d'Exécution Recommandé

```
1. Créer/vérifier les rôles (déjà en base)
2. ├─ Créer utilisateurs (ADMIN, AGENT x3, GERANT x2)
3. ├─ Créer catégories PDV
4. ├─ Créer catégories produits
5. ├─ Créer produits
6. ├─ Créer points de vente (avec gérants)
7. ├─ Créer flux de ravitaillement (avec lignes produits)
8. ├─ Créer transactions (avec statuts variés)
9. └─ Créer notifications
```

---

## 🔐 Considérations de Sécurité & Validation

### Mots de Passe

- Tous les utilisateurs reçoivent le même mot de passe de test: `password123`
- ⚠️ À utiliser **UNIQUEMENT en développement**
- Les mots de passe sont hachés avec Symfony PasswordHasher

### Unicité

- ✅ Email: Unique (UNIQ_UTILISATEUR_EMAIL)
- ✅ Code Ref PDV: Unique (UNIQ_POINT_VENTE_CODE_REF)
- ✅ Facture PDV: Unique (UNIQ_FLUX_RAVITAILLEMENT_FACTURE)

### Cohérence Métier

- ✅ Statuts enum respectés (EN_ATTENTE, VALIDEE, etc.)
- ✅ Montants > 0 FCFA
- ✅ Quantités > 0
- ✅ Coordonnées dans les bornes valides (-90 à 90 pour lat, -180 à 180 pour lng)
- ✅ Distances GPS respectées (PDV proxies les uns des autres)

### Value Objects

- ✅ Email validée et normalisée
- ✅ Telephone formattée (+237 prefix)
- ✅ Montant en centimes (convertis automatiquement)
- ✅ Coordonnees avec précision 8 décimales

---

## 📊 Résumé des Données Générées

| Entité | Quantité | Notes |
|--------|----------|-------|
| Utilisateur | 6 | 1 admin, 3 agents, 2 gérants |
| Role | 3 | (déjà en base) |
| CategoriePdv | 5 | Supérette, Boutique, Kiosque, etc. |
| CategorieProd | 4 | Boissons, Snacks, Hygiène, Autres |
| Produit | 26 | Distribués dans les 4 catégories |
| PointVente | 15 | 3 par catégorie PDV, chacun avec un gérant |
| FluxRavitaillement | ~20 | 1-2 par PDV |
| FluxProduit | ~80 | 3-8 par flux |
| Transaction | ~60 | 3-5 par PDV |
| Notification | ~20 | 2-5 par utilisateur |
| **TOTAL** | ~270 entités | Densité de données cohérente |

---

## 🎬 Exécution

### Commande Console

```bash
# Générer les fausses données
php bin/console app:seed:faker

# Options (future)
# php bin/console app:seed:faker --reset   # Supprimer les anciennes données
# php bin/console app:seed:faker --count=100  # Nombre custom de transactions
```

### Dans la commande init:database

```bash
php bin/console app:init:database
# ├─ Supprimer/recréer la BD
# ├─ Lancer les migrations
# └─ Générer les fausses données (app:seed:faker)
```

---

## 🔍 Vérification Manuelle

Après la génération:

```bash
# Vérifier les entités en base
php bin/console doctrine:query:dql "SELECT COUNT(u) FROM App:Utilisateur u"
php bin/console doctrine:query:dql "SELECT COUNT(p) FROM App:PointVente p"
php bin/console doctrine:query:dql "SELECT COUNT(t) FROM App:Transaction t"

# Test login avec les données générées
# Email: admin@mtnpdv.test
# Mot de passe: password123
```

---

**Stratégie validée:** 2026-07-03  
**Prochaine étape:** Implémenter les commandes console
