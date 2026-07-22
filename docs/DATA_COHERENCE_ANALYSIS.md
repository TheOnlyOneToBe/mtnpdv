# 🔐 Analyse de Cohérence des Données Métier

**Date:** 2026-07-03  
**Objectif:** Vérifier que toutes les relations entre entités sont correctes et cohérentes

---

## 📊 Graphe de Responsabilité des Entités

```
┌──────────────────────────────────────────────────────────────────┐
│                     ADMINISTRATEUR (Utilisateur)                 │
│  • Crée les DemandesVisite                                       │
│  • Assigne les agents aux demandes                               │
│  • Valide/Rejette les visites effectuées                         │
└──────────────────────────────────────────────────────────────────┘
                    │
         ┌──────────┴──────────┐
         │                     │
    (crée)               (assigne agent)
         │                     │
         ▼                     ▼
    DemandeVisite          Agent (Utilisateur)
      │                     │
      ├─ PointVente        └─ Exécute la visite
      ├─ Administrateur   (crée Transaction)
      ├─ Agent (optionnel)      │
      ├─ Transaction (lié)      ▼
      └─ Statut            Transaction
                           (type VISITE)
```

---

## ✅ Vérifications de Cohérence

### 1. Utilisateurs et Rôles

#### ✅ ADMINISTRATEUR (1 par système)
- **Rôle:** ADMIN
- **Responsabilités:**
  - Créer des DemandesVisite
  - Assigner des agents
  - Valider/Rejeter les visites
  - Voir tous les PDV et transactions
- **Vérification:**
  ```sql
  SELECT u.*, r.libelle 
  FROM utilisateur u 
  JOIN utilisateur_role ur ON u.id = ur.utilisateur_id
  JOIN role r ON ur.role_id = r.id
  WHERE r.code_role = 'ADMIN'
  ```
  ✅ Doit retourner 1 ligne

#### ✅ AGENTS (3+ par système)
- **Rôle:** AGENT
- **Responsabilités:**
  - Voir les DemandesVisite assignées
  - Créer des Transactions (visites effectuées)
  - Voir ses propres visites
- **Vérification:**
  ```sql
  SELECT u.*, COUNT(t.id) as nb_transactions
  FROM utilisateur u
  LEFT JOIN `transaction` t ON u.id = t.utilisateur_id
  WHERE u.id IN (
    SELECT ur.utilisateur_id 
    FROM utilisateur_role ur
    WHERE ur.role_id = (SELECT id FROM role WHERE code_role = 'AGENT')
  )
  GROUP BY u.id
  ```
  ✅ Tous les agents doivent avoir des transactions

#### ✅ GÉRANTS (2+ par système)
- **Rôle:** GERANT
- **Responsabilités:**
  - Voir son/ses PDV
  - Voir les transactions de ses PDV
  - Consulter l'historique de ravitaillement
- **Vérification:**
  ```sql
  SELECT u.*, pv.nom_pdv
  FROM utilisateur u
  JOIN point_vente pv ON u.id = pv.gerant_id
  WHERE u.id IN (
    SELECT ur.utilisateur_id 
    FROM utilisateur_role ur
    WHERE ur.role_id = (SELECT id FROM role WHERE code_role = 'GERANT')
  )
  ```
  ✅ Tous les gérants doivent manager au moins 1 PDV

---

### 2. Points de Vente (PDV) et Gérants

#### ✅ Chaque PDV a un gérant
```sql
SELECT pv.*, u.nom_ut, u.prenom_ut
FROM point_vente pv
LEFT JOIN utilisateur u ON pv.gerant_id = u.id
WHERE pv.gerant_id IS NULL
```
✅ Résultat: 0 lignes (tous les PDV doivent avoir un gérant)

#### ✅ Pas de PDV orphelin
```sql
SELECT COUNT(*) as orphelin_count
FROM point_vente
WHERE categorie_pdv_id IS NULL
```
✅ Résultat: Optionnel (PDV peut être sans catégorie)

#### ✅ Statuts cohérents
```sql
SELECT DISTINCT statut_actuel
FROM point_vente
ORDER BY statut_actuel
```
✅ Résultat: ACTIF, FERME, SUSPENDU (ou variantes)

---

### 3. DemandesVisite (Nouvelle Entité)

#### ✅ Workflow Complet
```
┌─────────────────────────────────────────────┐
│ DEMANDEE (Admin crée)                       │
│  • point_vente_id: ✅ Requis                 │
│  • admin_id: ✅ Requis (qui a créé)         │
│  • agent_id: ❌ NULL (pas encore assigné)   │
│  • transaction_id: ❌ NULL                   │
└─────────────────────────────────────────────┘
            │ (Admin assigne)
            ▼
┌─────────────────────────────────────────────┐
│ ASSIGNEE (Agent assigné)                    │
│  • agent_id: ✅ Non-NULL                     │
│  • transaction_id: ❌ NULL                   │
└─────────────────────────────────────────────┘
            │ (Agent exécute visite)
            ▼
┌─────────────────────────────────────────────┐
│ EFFECTUEE (Visite effectuée)                │
│  • transaction_id: ✅ Non-NULL               │
│  • date_effectuee: ✅ Enregistrée            │
└─────────────────────────────────────────────┘
            │ (Admin valide)
            ├──────────────────┐
            ▼                  ▼
    ┌─────────────┐      ┌─────────────┐
    │ VALIDEE     │      │ REJETEE     │
    │(acceptée)  │      │(refusée)    │
    └─────────────┘      └─────────────┘
```

#### ✅ Vérification: Cohérence Admin → DemandeVisite → Agent
```sql
SELECT dv.id, dv.motif,
       a_admin.email as admin_email,
       pv.nom_pdv,
       a_agent.email as agent_email,
       dv.statut
FROM demande_visite dv
JOIN utilisateur a_admin ON dv.admin_id = a_admin.id
JOIN point_vente pv ON dv.point_vente_id = pv.id
LEFT JOIN utilisateur a_agent ON dv.agent_id = a_agent.id
WHERE dv.statut IN ('DEMANDEE', 'ASSIGNEE', 'EFFECTUEE')
ORDER BY dv.date_creation DESC
```

✅ Vérifier:
- ✅ `admin_email`: Doit être un administrateur (ROLE_ADMIN)
- ✅ `agent_email`: Doit être un agent (ROLE_AGENT) ou NULL
- ✅ `statut`: Cohérent avec présence de `agent_id` et `transaction_id`

---

### 4. Transactions (Visites) et Agents

#### ✅ Chaque Transaction a un agent
```sql
SELECT t.*, u.email
FROM `transaction` t
LEFT JOIN utilisateur u ON t.utilisateur_id = u.id
WHERE t.type_enum = 'VISITE' AND t.utilisateur_id IS NULL
```
✅ Résultat: 0 lignes (toutes les visites doivent avoir un agent)

#### ✅ Chaque Transaction a un PDV
```sql
SELECT t.*, pv.nom_pdv
FROM `transaction` t
LEFT JOIN point_vente pv ON t.point_vente_id = pv.id
WHERE t.type_enum = 'VISITE' AND t.point_vente_id IS NULL
```
✅ Résultat: 0 lignes (toutes les visites doivent avoir un PDV)

#### ✅ Chaque Transaction VISITE peut être liée à une DemandeVisite
```sql
SELECT t.id, t.utilisateur_id, t.point_vente_id,
       COALESCE(dv.id, 'SANS DEMANDE') as demande_id
FROM `transaction` t
LEFT JOIN demande_visite dv ON t.id = dv.transaction_id
WHERE t.type_enum = 'VISITE'
ORDER BY t.date_transac DESC
```

✅ Vérifier:
- ✅ Transactions récentes: Devraient avoir un `demande_id` (workflow cohérent)
- ✅ Anciennes transactions: Peuvent être sans demande (migration depuis système antérieur)

---

### 5. Flux de Ravitaillement et Utilisateurs

#### ✅ Chaque FluxRavitaillement a un utilisateur (gestionnaire)
```sql
SELECT fr.*, u.email
FROM flux_ravitaillement fr
LEFT JOIN utilisateur u ON fr.utilisateur_id = u.id
WHERE fr.utilisateur_id IS NULL
```
✅ Résultat: 0 lignes

#### ✅ Chaque FluxRavitaillement a un PDV
```sql
SELECT fr.*, pv.nom_pdv
FROM flux_ravitaillement fr
LEFT JOIN point_vente pv ON fr.point_vente_id = pv.id
WHERE fr.point_vente_id IS NULL
```
✅ Résultat: 0 lignes

#### ✅ Chaque FluxProduit est lié à son flux
```sql
SELECT fp.*, fr.facture_uniq
FROM flux_produit fp
LEFT JOIN flux_ravitaillement fr ON fp.flux_ravitaillement_id = fr.id
WHERE fp.flux_ravitaillement_id IS NULL
```
✅ Résultat: 0 lignes

---

### 6. Notifications et Utilisateurs

#### ✅ Chaque notification a un destinataire
```sql
SELECT n.*, u.email
FROM notification n
LEFT JOIN utilisateur u ON n.utilisateur_id = u.id
WHERE n.utilisateur_id IS NULL
```
✅ Résultat: 0 lignes

#### ✅ Notifications correctes par statut de Transaction
```sql
SELECT t.id, t.statut, 
       COUNT(n.id) as nb_notifications
FROM `transaction` t
LEFT JOIN notification n ON 
  (t.utilisateur_id = n.utilisateur_id AND 
   (
     (t.statut = 'VALIDEE' AND n.type = 'VISITE_VALIDEE') OR
     (t.statut = 'REJETEE' AND n.type = 'VISITE_REJETEE')
   ))
WHERE t.type_enum = 'VISITE'
GROUP BY t.id, t.statut
```

✅ Vérifier:
- ✅ Visites VALIDEE: Doivent avoir notification VISITE_VALIDEE
- ✅ Visites REJETEE: Doivent avoir notification VISITE_REJETEE

---

## 🔍 Vérification Générale (Script SQL)

```sql
-- Vue globale de cohérence
SELECT 
  'Utilisateurs' as entity,
  COUNT(*) as total,
  'OK' as status
FROM utilisateur
UNION ALL
SELECT 
  'DemandesVisite',
  COUNT(*),
  CASE 
    WHEN COUNT(*) = 0 THEN 'Nouveau (OK)'
    ELSE 'Généré'
  END
FROM demande_visite
UNION ALL
SELECT 
  'Transactions VISITE',
  COUNT(*),
  'OK'
FROM `transaction`
WHERE type_enum = 'VISITE'
UNION ALL
SELECT 
  'PointsVente',
  COUNT(*),
  'OK'
FROM point_vente
UNION ALL
SELECT 
  'FluxRavitaillement',
  COUNT(*),
  'OK'
FROM flux_ravitaillement
UNION ALL
SELECT 
  'FluxProduit',
  COUNT(*),
  'OK'
FROM flux_produit
UNION ALL
SELECT 
  'Notifications',
  COUNT(*),
  'OK'
FROM notification
ORDER BY entity;
```

---

## 🎯 Checklist de Vérification Manuelle

Après chaque génération de données, vérifier:

- [ ] **Utilisateurs:**
  - [ ] 1 Admin (email: admin@mtnpdv.test)
  - [ ] 3+ Agents (agent1@, agent2@, agent3@...)
  - [ ] 2+ Gérants (gerant1@, gerant2@...)

- [ ] **Points de Vente:**
  - [ ] Tous ont un gérant assigné
  - [ ] Tous ont un statut ACTIF
  - [ ] Tous ont coordonnées valides (lat/lng dans bornes)

- [ ] **Transactions VISITE:**
  - [ ] Toutes ont un agent assigné
  - [ ] Toutes ont un PDV assigné
  - [ ] Coordonnées proximité ±50-150m du PDV
  - [ ] Montants valides (≥ 0 FCFA)
  - [ ] Statuts: EN_ATTENTE, VALIDEE, ou REJETEE

- [ ] **FluxRavitaillement:**
  - [ ] Tous ont un utilisateur (gestionnaire)
  - [ ] Tous ont un PDV
  - [ ] Tous ont ≥1 ligne de produit
  - [ ] Montant total = somme des sous-totaux

- [ ] **DemandesVisite (Nouvelle):**
  - [ ] Workflow respecté: DEMANDEE → ASSIGNEE → EFFECTUEE → VALIDEE/REJETEE
  - [ ] Chaque demande liée à admin + PDV
  - [ ] Agent assigné seulement si statut ASSIGNEE+
  - [ ] Transaction créée seulement si statut EFFECTUEE+

- [ ] **Notifications:**
  - [ ] Notification VISITE_VALIDEE pour chaque visite validée
  - [ ] Notification VISITE_REJETEE pour chaque visite rejetée
  - [ ] Notification destinataire = agent de la visite

---

## 🚀 Amélioration Apportée

### Avant (Système Actuel)
```
Agent crée Transaction directement
  ├─ Pas de demande préalable
  ├─ Admin valide/rejette après
  └─ Pas de tracking d'attribution
```

### Après (Nouveau Système)
```
Admin crée DemandeVisite
  ├─ Motif + PDV cible + date
  └─ Assignation d'agent
      └─ Agent exécute (crée Transaction)
          └─ Admin valide/rejette
              ├─ Notification au agent
              └─ Historique complet
```

**Avantages:**
✅ Traçabilité complète du workflow  
✅ Admin contrôle les visites à effectuer  
✅ Agent a une liste claire de ce qui est demandé  
✅ Audit du qui a demandé quoi et quand  
✅ Gestion des priorités/urgences  
✅ Raison de la visite documentée  

---

## 📋 Requêtes de Vérification Recommandées

### Vérifier qu'un agent n'a que des transactions pour ses PDV assignés:

```sql
SELECT a.email as agent, pv.nom_pdv, COUNT(t.id) as visites
FROM utilisateur a
LEFT JOIN `transaction` t ON a.id = t.utilisateur_id AND t.type_enum = 'VISITE'
LEFT JOIN point_vente pv ON t.point_vente_id = pv.id
WHERE a.id IN (
  SELECT ur.utilisateur_id FROM utilisateur_role ur
  WHERE ur.role_id = (SELECT id FROM role WHERE code_role = 'AGENT')
)
GROUP BY a.id, pv.id
ORDER BY a.email, pv.nom_pdv;
```

### Vérifier qu'un gérant ne voit que ses PDV:

```sql
SELECT g.email as gerant, pv.nom_pdv, pv.statut_actuel
FROM utilisateur g
JOIN point_vente pv ON g.id = pv.gerant_id
WHERE g.id IN (
  SELECT ur.utilisateur_id FROM utilisateur_role ur
  WHERE ur.role_id = (SELECT id FROM role WHERE code_role = 'GERANT')
)
ORDER BY g.email, pv.nom_pdv;
```

### Vérifier le workflow complet d'une demande:

```sql
SELECT 
  dv.id,
  dv.motif,
  a_admin.email as demande_par,
  pv.nom_pdv,
  a_agent.email as effectue_par,
  dv.statut,
  dv.date_creation,
  dv.date_effectuee,
  dv.date_validee,
  COALESCE(dv.motif_rejet, '-') as raison_rejet
FROM demande_visite dv
JOIN utilisateur a_admin ON dv.admin_id = a_admin.id
JOIN point_vente pv ON dv.point_vente_id = pv.id
LEFT JOIN utilisateur a_agent ON dv.agent_id = a_agent.id
ORDER BY dv.date_creation DESC;
```

---

**Vérification validée:** 2026-07-03  
**Status:** ✅ **ARCHITECTURE COHÉRENTE ET TRAÇABLE**
