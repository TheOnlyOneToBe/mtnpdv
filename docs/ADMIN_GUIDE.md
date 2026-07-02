# Guide d'Administration - MTNPDV

> **Guide complet pour les administrateurs du système MTNPDV**

## 📋 Table des matières

1. [Accès administrateur](#accès-administrateur)
2. [Tableau de bord](#tableau-de-bord)
3. [Gestion des PDV](#gestion-des-pdv)
4. [Gestion des utilisateurs](#gestion-des-utilisateurs)
5. [Gestion des transactions](#gestion-des-transactions)
6. [Rapports](#rapports)
7. [Rôles et permissions](#rôles-et-permissions)
8. [Bonnes pratiques](#bonnes-pratiques)

## 🔐 Accès administrateur

### Connexion

1. Allez sur `https://your-app.com/login`
2. Entrez votre email et mot de passe
3. Cliquez sur "Connexion"

**⚠️ Sécurité**: Utilisez un mot de passe fort (min 12 caractères avec majuscules, chiffres, symboles)

### Premier accès

Pour créer le premier administrateur:

```bash
php bin/console app:utilisateur:creer \
  --email=admin@example.com \
  --prenom=Admin \
  --nom=Initial \
  --telephone=000000000 \
  --password=VeryStrongPassword123! \
  --role=ADMIN
```

## 📊 Tableau de bord

Le dashboard administrative montre les statistiques clés:

### Indicateurs clés

- **Total PDV**: Nombre total de points de vente dans le réseau
- **Transactions**: Nombre total de transactions enregistrées
- **Utilisateurs**: Nombre total d'utilisateurs du système
- **Visites en attente**: Transactions attendant validation

### Graphiques

1. **Distribution des PDV** (Doughnut)
   - Statuts: ACTIF, FERME, SUSPENDU
   - En pourcentage

2. **Statut des transactions** (Bar)
   - VALIDEE, EN_ATTENTE, REJETEE
   - Vue par statut

3. **Répartition des rôles** (Radar)
   - ADMIN, AGENT, GERANT
   - Nombre d'utilisateurs par rôle

4. **Chiffre d'affaires** (Bar)
   - Top 8 PDV par revenus
   - Montants en devises

5. **Tendances** (Line)
   - Transactions des 7 derniers jours
   - Vue temporelle

### Actions rapides

- **Voir les PDV**: Lien vers la liste complète
- **Gérer les utilisateurs**: Accès à la gestion utilisateur
- **Rapports**: Accès aux rapports détaillés

## 🗺️ Gestion des PDV

### Liste des PDV

Accessible via: **Admin → Points de vente** ou `/admin/pdv`

#### Fonctionnalités

- **Affichage**: Liste ou Carte (Leaflet)
- **Pagination**: 12 PDV par page
- **Recherche**: Par nom ou code (temps réel, 60 req/min)
- **Filtrage**: Par département/région et gérant
- **Statuts**: Badges colorés (vert=ACTIF, rouge=FERME, orange=SUSPENDU)

#### Colonnes du tableau

| Colonne | Description |
|---------|-------------|
| Nom | Nom du point de vente |
| Code | Code de référence unique |
| Ville | Localisation |
| Gérant | Responsable du kiosque |
| Statut | État du PDV |
| Actions | Voir, Modifier, Supprimer |

### Créer un nouveau PDV

1. Cliquez sur **+ Nouveau point de vente**
2. Remplissez le formulaire:
   - **Nom PDV** (requis)
   - **Code de référence** (requis, unique)
   - **Ville** (requis)
   - **Adresse** (optionnel)
   - **Téléphone** (requis, format: +237...)
   - **Coordonnées GPS** (latitude, longitude)
   - **Gérant** (optionnel, utilisateur avec rôle GERANT)
3. Cliquez sur **Créer**

**✅ Succès**: Toast vert "Point de vente créé avec succès"

### Modifier un PDV

1. Dans la liste, cliquez sur ✏️ **Modifier**
2. Éditez les champs
3. Cliquez sur **Enregistrer**

**⚠️ Important**: 
- Le code de référence ne peut pas être modifié
- Les gérants ne peuvent voir que leurs PDV

### Voir les détails d'un PDV

1. Cliquez sur 👁️ **Détails** ou le nom du PDV
2. Consultez les informations complètes:
   - Informations générales
   - Gérant assigné
   - Coordonnées GPS
   - Transactions associées

### Supprimer un PDV

1. Cliquez sur 🗑️ **Supprimer** (attention: irréversible)
2. Confirmez la suppression

**⚠️ Attention**: 
- Suppression irréversible
- Toutes les transactions associées seront supprimées
- Vérifiez avant de supprimer

### Vue Carte

- **Bascule**: Bouton "Liste/Carte" en haut
- **Marqueurs**:
  - 🟢 Vert = PDV ACTIF
  - 🔴 Rouge = PDV FERME
  - 🟠 Orange = PDV SUSPENDU
- **Clic sur marqueur**: Affiche infos du PDV
- **Géolocalisation**: Affiche votre position (bleu)
- **Recherche**: Filtre les marqueurs dynamiquement

## 👥 Gestion des utilisateurs

### Liste des utilisateurs

Accessible via: **Admin → Utilisateurs** ou `/admin/utilisateurs`

#### Colonnes

| Colonne | Description |
|---------|-------------|
| Email | Email de connexion |
| Nom | Prénom + Nom |
| Téléphone | Contact |
| Statut | ACTIF / INACTIF |
| Rôles | Badges des rôles assignés |
| Actions | Voir, Modifier, Supprimer |

### Créer un utilisateur

1. Cliquez sur **+ Nouvel utilisateur**
2. Remplissez:
   - **Email** (requis, unique, format valide)
   - **Prénom** (requis)
   - **Nom** (requis)
   - **Téléphone** (requis, format +237...)
   - **Mot de passe** (requis, min 8 caractères)
   - **Rôles** (requis, au moins un)
3. Cliquez sur **Créer**

**Rôles disponibles**:
- 🔴 **ADMIN**: Accès complet au système
- 🔵 **AGENT**: Enregistrement de visites terrain
- 🟢 **GERANT**: Gestion de son kiosque uniquement

### Modifier un utilisateur

1. Cliquez sur ✏️ **Modifier**
2. Mettez à jour:
   - Informations personnelles (prénom, nom, téléphone)
   - Rôles (sélection multiple possible)
   - Statut (ACTIF/INACTIF)
3. Cliquez sur **Enregistrer**

**⚠️ Note**: Les mots de passe ne peuvent être changés que par l'utilisateur lui-même via son profil.

### Désactiver un utilisateur

1. Éditez l'utilisateur
2. Changez le statut en **INACTIF**
3. Enregistrez

L'utilisateur ne pourra plus se connecter.

### Supprimer un utilisateur

1. Cliquez sur 🗑️ **Supprimer**
2. Confirmez

**⚠️ Attention**: Irréversible, toutes les données associées seront supprimées.

### Voir le profil d'un utilisateur

1. Cliquez sur 👁️ **Détails**
2. Consultez:
   - Informations personnelles
   - Rôles assignés
   - Date de création
   - Statut du compte

## 💰 Gestion des transactions

### Liste des transactions

Accessible via: **Admin → Validations** ou `/admin/validations`

#### Trois onglets

**1. En attente** (Transactions à valider)
- Statut: EN_ATTENTE
- Action requise: Valider ou Rejeter
- Pagination: 12 par page

**2. Validées** (Transactions approuvées)
- Statut: VALIDEE
- Lecture seule
- Historique complet

**3. Rejetées** (Transactions refusées)
- Statut: REJETEE
- Lecture seule
- Motifs de rejet visibles

#### Colonnes du tableau

| Colonne | Description |
|---------|-------------|
| Date | Date/heure de la transaction |
| Agent | Qui a enregistré |
| PDV | Point de vente concerné |
| Type | VENTE ou VISITE |
| Montant | Montant en devise |
| Actions | Valider/Rejeter (EN_ATTENTE uniquement) |

### Valider une transaction

1. Dans l'onglet **En attente**
2. Cliquez sur ✅ **Valider**
3. La transaction devient VALIDEE
4. **Toast**: "Transaction validée avec succès"

**Conséquences**:
- Crédite le chiffre d'affaires du PDV
- Apparaît dans les rapports
- L'agent reçoit notification (optionnel)

### Rejeter une transaction

1. Dans l'onglet **En attente**
2. Cliquez sur ❌ **Rejeter**
3. La transaction devient REJETEE
4. **Toast**: "Transaction rejetée"

**Raisons courantes de rejet**:
- Montant incorrect
- PDV non valide
- Données incomplètes
- Visite hors zone géographique

### Filtrer les transactions

Les trois onglets filtrent automatiquement par statut. Pour paginer:

1. Cliquez sur le numéro de page en bas
2. Ou utilisez "Précédent/Suivant"
3. **Affichage**: "Showing 1-12 of 156"

## 📊 Rapports

Accessible via: **Admin → Rapports** ou `/admin/reports`

### Hub des rapports

Page centrale avec cartes des trois rapports principaux.

### Rapport PDV

**URL**: `/admin/reports/pdv`

#### Contenu

1. **Statistiques clés**
   - Total PDV
   - PDV actifs
   - PDV fermés

2. **Distribution par statut**
   - Table avec counts et pourcentages
   - Barres de progression visuelles

3. **Actions**
   - Lien vers liste PDV
   - Retour au dashboard

#### Interprétation

- ✅ PDV ACTIF: Opérationnel
- ❌ PDV FERME: Temporairement/définitivement fermé
- ⚠️ PDV SUSPENDU: En attente de décision

### Rapport Transactions

**URL**: `/admin/reports/transactions`

#### Contenu

1. **Statistiques clés**
   - Total transactions
   - Validées
   - En attente
   - Rejetées

2. **Distribution par statut**
   - Pourcentages et visualisations
   - Taux de validation

3. **Tendances** (7 derniers jours)
   - Tableau jour par jour
   - Évolution des transactions
   - Détection des anomalies

#### Utilisation

- **Monitoring**: Vérifiez les transactions en attente
- **Performance**: Analysez le volume
- **Anomalies**: Décela les pics/creux

### Rapport Utilisateurs

**URL**: `/admin/reports/users`

#### Contenu

1. **Statistiques clés**
   - Total utilisateurs
   - Admins
   - Agents
   - Gérants

2. **Distribution par rôle**
   - Counts et pourcentages
   - Équilibre des ressources

3. **Description des rôles**
   - Permissions de chaque rôle
   - Responsabilités

#### Utilisation

- **Audit**: Vérifiez les comptes actifs
- **Staffing**: Planifiez le recrutement
- **Permissions**: Vérifiez les rôles assignés

## 🔐 Rôles et permissions

### Matrice des rôles

| Fonction | Admin | Agent | Gérant |
|----------|-------|-------|--------|
| Voir PDV | ✅ Tous | ✅ Tous | ✅ Sien |
| Créer PDV | ✅ | ❌ | ❌ |
| Modifier PDV | ✅ | ❌ | ❌ |
| Supprimer PDV | ✅ | ❌ | ❌ |
| Voir Transactions | ✅ Tous | ✅ Siennes | ✅ Siennes |
| Créer Transaction | ❌ | ✅ | ❌ |
| Valider Transaction | ✅ | ❌ | ❌ |
| Voir Utilisateurs | ✅ Tous | ❌ | ❌ |
| Créer Utilisateur | ✅ | ❌ | ❌ |
| Gérer Rôles | ✅ | ❌ | ❌ |
| Voir Rapports | ✅ | ⚠️ Limités | ❌ |

### Description des rôles

#### 🔴 ADMIN
**Accès**: Complet à tous les modules
- Gestion PDV (CRUD)
- Gestion utilisateurs (CRUD)
- Validation transactions
- Accès à tous les rapports
- Configuration du système

**Responsabilités**:
- Supervision générale
- Approbation des transactions
- Gestion des comptes
- Conformité

#### 🔵 AGENT
**Accès**: Terrain et transactions
- Voir tous les PDV
- Enregistrer transactions
- Consulter ses propres données
- Voir rapports limités

**Responsabilités**:
- Visites terrain
- Enregistrement précis des données
- Collecte d'informations

#### 🟢 GERANT
**Accès**: Son kiosque uniquement
- Voir son PDV
- Consulter historique transactions
- Modifier son profil
- Voir ses données

**Responsabilités**:
- Gestion locale du kiosque
- Suivi des opérations
- Communication avec l'admin

## 💡 Bonnes pratiques

### Sécurité

1. **Mots de passe**
   - Min 12 caractères
   - Majuscules + minuscules + chiffres + symboles
   - Changez régulièrement
   - Ne réutilisez pas les anciens

2. **Accès**
   - Limitez les admins (1-2 max)
   - Désactivez les comptes inactifs
   - Changez les identifiants par défaut
   - Activez les logs d'audit

3. **Données**
   - Sauvegardez régulièrement
   - Chiffrez les données sensibles
   - Limitez l'accès aux infos personnelles

### Maintenance

1. **Contrôle qualité**
   - Vérifiez les PDV régulièrement
   - Validez les transactions sans retard
   - Nettoyez les comptes inactifs

2. **Monitoring**
   - Consultez les rapports hebdomadaires
   - Vérifiez les anomalies
   - Suivez les tendances

3. **Mises à jour**
   - Installez les mises à jour de sécurité
   - Testez avant production
   - Sauvegardez avant mise à jour

### Performance

1. **Pagination**
   - Les listes affichent 12 éléments/page
   - Utilisez la recherche pour filtrer
   - Évitez de charger trop de pages

2. **Recherche**
   - Rate limit: 60 requêtes/minute
   - Soyez spécifiques dans les termes
   - Utilisez les filtres (département, gérant)

3. **Carte**
   - Bascule vers liste si trop de marqueurs
   - Zoomez pour voir les détails
   - Utilisez les couleurs pour identifier les statuts

## 🆘 Dépannage

### Problème: Transaction en attente infinie

**Solution**:
1. Vérifiez les données (montant, PDV)
2. Validez ou rejetez-la explicitement
3. Contactez l'agent si données manquantes

### Problème: Utilisateur ne peut pas se connecter

**Solution**:
1. Vérifiez le statut (doit être ACTIF)
2. Vérifiez l'email
3. Demandez une réinitialisation du mot de passe

### Problème: Carte ne charge pas

**Solution**:
1. Vérifiez la connexion internet
2. Rechargez la page
3. Basculez sur la vue liste
4. Vérifiez les coordonnées GPS des PDV

## 📞 Support

- **Documentation**: Consultez ce guide
- **FAQ**: Voir la section Dépannage
- **Support**: Contactez l'équipe technique

---

**Dernière mise à jour**: 2026-07-02  
**Version du guide**: 1.0.0
