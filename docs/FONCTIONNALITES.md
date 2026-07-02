# Fonctionnalités par Rôle - MTNPDV

## 👨‍💼 ADMIN - Administrateur Système

### Dashboard Admin
**Route:** `GET /admin/dashboard`

Affiche:
- 📊 Statistiques globales:
  - Total points de vente
  - Total transactions
  - Visites en attente de validation
  - Chiffre d'affaires cumulé
- 🔴 Visites à valider (tableau)
- 📍 Carte interactive de tous les PDV

### Gestion des Points de Vente

#### Lister les PDV
**Route:** `GET /admin/pdv`

Affiche:
- Tableau de tous les PDV (26 agences)
- Statut (ACTIF, SUSPENDU, FERME)
- Ville, code de référence
- Actions: Voir, Modifier, Supprimer

#### Créer un PDV
**Route:** `GET/POST /admin/pdv/new`

Champs:
- Nom du kiosque*
- Code de référence (unique)*
- Adresse (optionnel)
- Ville*
- Téléphone (+237...)*
- Latitude/Longitude (GPS)*
- Catégorie PDV
- Assignation gérant

Validation:
- ✅ Code unique
- ✅ GPS valide
- ✅ Téléphone format Cameroon

#### Afficher détails PDV
**Route:** `GET /admin/pdv/{id}`

Affiche:
- Informations complètes
- Historique transactions
- Produits livrés
- Gérant assigné
- Carte interactive

#### Modifier PDV
**Route:** `GET/POST /admin/pdv/{id}/edit`

Modifiable:
- Nom, adresse, téléphone
- Catégorie
- Gérant assigné
- Statut (ACTIF/SUSPENDU/FERME)

### Gestion des Utilisateurs

#### Lister utilisateurs
**Route:** `GET /admin/users`

Affiche:
- Tableau: Email, Nom, Prénom, Rôle, Statut
- Recherche par nom/email
- Filtrage par rôle
- Filtrage par statut (ACTIF/INACTIF)

#### Créer utilisateur
**Route:** `GET/POST /admin/users/new`

Champs:
- Email*
- Nom*
- Prénom*
- Téléphone*
- Mot de passe (auto-généré ou manuel)*
- Rôles (ADMIN, AGENT, GERANT)*
- Statut (ACTIF/INACTIF)

#### Modifier utilisateur
**Route:** `GET/POST /admin/users/{id}/edit`

Modifiable:
- Nom, prénom, téléphone
- Rôles
- Statut
- Désactivation de compte

### Validation des Visites

#### Lister visites en attente
**Route:** `GET /admin/validations`

Affiche:
- Tableau des transactions EN_ATTENTE
- Agent, PDV, Date, Montant (si applicable)
- Distance réelle vs tolérance
- Boutons: Valider, Rejeter

#### Valider une visite
**Route:** `POST /admin/validations/{id}/approve`

Processus:
1. Marque comme VALIDEE
2. Flash success
3. Redirection liste

#### Rejeter une visite
**Route:** `POST /admin/validations/{id}/reject`

Champs:
- Raison du rejet (optionnel)

Processus:
1. Marque comme REJETEE
2. Sauvegarde commentaire
3. Notification agent
4. Redirection

### Carte Interactive
**Route:** `GET /admin/map`

Affiche:
- Carte Leaflet Cameroon
- Tous les PDV (26 marqueurs)
- Zoom sur PDV au clic
- Affiche détails popup

---

## 🚶 AGENT - Agent Terrain

### Dashboard Agent
**Route:** `GET /agent/dashboard`

Affiche:
- 📍 Ma position actuelle (géolocalisation)
- 🗺️ Carte interactive:
  - Ma position (marqueur bleu)
  - PDV proches à 1km (marqueurs verts)
  - Mes visites (rouge/orange/vert par statut)
- 📋 10 dernières visites (tableau)
- 👤 Infos profil raccourci

### Enregistrer une Visite

#### Formulaire visite
**Route:** `GET/POST /agent/visite/new`

Champs:
- Sélection PDV*
- Type visite: VISITE (implicite)
- Latitude/Longitude (GPS) - auto ou manuel*
- Commentaire (optionnel)
- Photo preuve (optionnel)
- Montant (optionnel, si vente)

Processus:
1. Calcule distance agent↔PDV
2. Si > 100m: "Attention, hors zone!"
3. Crée Transaction
4. Statut EN_ATTENTE
5. Upload photo si présente
6. Flash: "Visite enregistrée"

**Validation:**
- ✅ GPS valide
- ✅ PDV existe
- ✅ Photo: max 5MB, jpg/png/webp

### Lister mes visites

#### Mes visites
**Route:** `GET /agent/visites`

Affiche:
- Tableau de mes 50 dernières visites
- Date, PDV, Statut, Distance réelle
- Filtrage par statut
- Filtrage par plage dates
- Téléchargement rapport

#### Détails visite
**Route:** `GET /agent/visite/{id}`

Affiche:
- Date/heure, PDV, localisation
- Commentaire, photo
- Distance réelle vs tolérance
- Statut + raison si rejet

### Consultations

#### Mes statistiques
- Total visites
- Visites validées
- Visites rejetées
- Visites en attente
- Moyenne distance zone

---

## 🏪 GERANT - Gérant de Kiosque

### Dashboard Gérant
**Route:** `GET /gerant/dashboard`

Affiche:
- 🏪 Nom du kiosque
- 📊 Métriques:
  - Produits livrés (count)
  - Chiffre d'affaires (FCFA)
  - Transactions totales
  - En attente de validation (toujours 0 pour gérant)
- 📍 Carte du kiosque
- 📦 Produits livrés (derniers 10)
- 💰 Transactions récentes (dernières 10)

### Gestion des Produits

#### Lister & Rechercher produits
**Route:** `GET /gerant/produits`

Affiche:
- Grille des produits livrés
- Chaque carte: nom, catégorie, prix FCFA, quantité
- Code barre

Recherche & Filtres:
- 🔍 Par nom de produit
- 🏷️ Par catégorie
- 💰 Fourchette prix (min/max FCFA)
- 🔄 Réinitialiser

Bouton par produit:
- "Enregistrer une vente" → Pré-remplissage formulaire

---

### Enregistrement de Ventes

#### Nouvelle vente
**Route:** `GET/POST /gerant/vente/new`

Champs:
- Sélection produit*
  - Affiche: nom, prix FCFA, quantité disponible
- Quantité vendue (1-max)*
  - Boutons +/- pour facile ajustement
- Montant total (FCFA)*
  - Calculé auto (prix × quantité)
  - Modifiable manuellement
- Position GPS*
  - Pré-remplie: position kiosque
  - Bouton géolocalisation
- Commentaire (optionnel)

Processus:
1. Sélection produit → Prix chargé
2. Quantité ajustée → Montant calculé
3. GPS capturé ou vérifié
4. "Enregistrer la vente"
5. **Vente VALIDEE immédiatement** ✅
6. Flash: "Vente validée: 50000 FCFA (Transaction #123)"

**Particularité gérant:**
- ✅ **Pas d'attente approbation admin**
- ✅ **Autonomie commerciale complète**
- ✅ **Ventes en statut VALIDEE automatiquement**

---

### Historique des Transactions

#### Lister toutes transactions
**Route:** `GET /gerant/transactions`

Affiche:
- Tableau: Date, Type (VENTE/VISITE), Montant, Statut, Position GPS
- Icônes par type/statut

Filtres:
- 📝 Par type (VENTE, VISITE, AUTRE)
- ✅ Par statut (VALIDEE, EN_ATTENTE, REJETEE, ANNULEE)

Statistiques bas de page:
- Total transactions
- Validées
- En attente
- Rejetées

#### Détails transaction
**Route:** `GET /gerant/transaction/{id}`

Affiche:
- Date/heure complète
- Type et montant
- Statut
- Produit vendu (si vente)
- Quantité
- Commentaire
- Coordonnées GPS

---

## 👤 Fonctionnalités partagées (Tous les rôles)

### Profil Utilisateur

#### Afficher mon profil
**Route:** `GET /profil`

Affiche:
- 📸 Photo d'avatar
- 👤 Nom complet
- 📧 Email
- 📱 Téléphone
- 🔐 Rôle

#### Modifier mon profil
**Route:** `GET/POST /profil/edit`

Modifiable:
- Prénom
- Nom
- Téléphone
- Photo de profil (avatar)

#### Changer mot de passe
**Route:** `GET/POST /profil/change-password`

Processus:
1. Saisie ancien mot de passe
2. Nouveau mot de passe
3. Confirmation
4. Validation longueur (min 8 caractères)
5. Hash bcrypt
6. Sauvegarde
7. Flash: "Mot de passe changé"

---

### Authentification

#### Page Login
**Route:** `GET /login`

Affiche:
- Formulaire: Email, Mot de passe
- Checkbox "Se souvenir de moi"
- Bouton "Se connecter"

Validations:
- ✅ Format email
- ✅ Mot de passe min 6 caractères
- ✅ Compte ACTIF (sinon erreur)

#### Déconnexion
**Route:** `GET /logout`

Processus:
1. Invalidate session
2. Clear cookies
3. Redirection login

---

### Notifications (Système)

#### Ma boîte de notifications
**Route:** `GET /notifications`

Affiche:
- Notifications récentes (max 10 non-lues)
- Types:
  - 📌 Visite validée/rejetée (pour agent)
  - 💬 Nouveau message admin
  - ⚠️ Alerte système

Marquage:
- Bouton "Marquer comme lue"
- Auto-effacement après 30 jours

---

## 🔄 Flux métier complets

### Scénario 1: Agent enregistre visite

```
1. Agent → /agent/visite/new
2. Sélectionne PDV "Kiosque Douala"
3. Capture GPS: 45m de la position
4. Ajoute photo + commentaire
5. POST → EnregistrerVisiteHandler
6. Transaction créée (EN_ATTENTE)
7. Flash: "Visite enregistrée"
8. Agent → /agent/visites (voir sa visite)
9. Admin → /admin/validations
10. Admin clique "Valider"
11. Transaction passe VALIDEE
12. Notification agent
```

### Scénario 2: Gérant enregistre vente

```
1. Gérant → /gerant/dashboard
2. Clique "Nouvelle vente"
3. Sélectionne produit "Carte SIM MTN"
4. Quantité: 5 unités
5. Montant auto: 5 × 10000 = 50000 FCFA
6. GPS auto: Position kiosque
7. POST → EnregistrerVenteHandler
8. Transaction créée + VALIDEE immédiatement ✅
9. Flash: "Vente validée: 50000 FCFA (Transaction #456)"
10. Gérant → /gerant/transactions
11. Voit sa vente en statut VALIDEE
12. Historique complet avec montant
```

---

## 📊 Résumé Accès par Rôle

| Feature | Admin | Agent | Gérant |
|---------|-------|-------|--------|
| Dashboard | ✅ | ✅ | ✅ |
| Gérer PDV | ✅ | ❌ | ❌ |
| Gérer Users | ✅ | ❌ | ❌ |
| Valider visites | ✅ | ❌ | ❌ |
| Enregistrer visite | ❌ | ✅ | ❌ |
| Enregistrer vente | ❌ | ❌ | ✅ |
| Voir transactions | ✅ | ✅ | ✅ |
| Carte interactive | ✅ | ✅ | ✅ |
| Profil personnel | ✅ | ✅ | ✅ |

---

**Version:** 1.0  
**Features count:** 30+
