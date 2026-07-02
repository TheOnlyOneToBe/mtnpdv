# Services et Handlers MTNPDV

## 🎯 Vue d'ensemble

Les services (Handlers) implémentent les cas d'usage métier (Use Cases). Chaque handler corresponds à une action complète de l'utilisateur.

---

## 📋 Services de Visite (Terrain)

### EnregistrerVisiteHandler
**Localisation:** `src/Application/Visite/EnregistrerVisiteHandler.php`

**Responsabilité:** Enregistrer une visite terrain par un agent

**Entrée (DTO):**
```php
$cmd = new EnregistrerVisiteCommande(
    pdvId: 1,                      // Point de vente visité
    agentId: 2,                    // Agent effectuant la visite
    latitude: 3.8480,              // Position GPS
    longitude: 11.5021,
    commentaire: 'Stock OK',       // Observation
    photoFile?: File               // Preuve photo (optionnel)
);
```

**Processus:**
1. Récupère le point de vente
2. Valide la distance (rayon tolérance: 100m)
3. Crée une Transaction avec type VISITE
4. Horodatage automatique + statut EN_ATTENTE
5. Upload photo si présente
6. Persiste en base
7. Retourne la transaction + distance réelle + indication zone

**Sortie:**
```php
[
    'transaction' => Transaction,  // Visite enregistrée
    'distanceMetres' => 45,       // Distance agent↔PDV
    'dansLaZone' => true          // Respecte la tolérance
]
```

**Erreurs possibles:**
- `InvalidArgumentException`: PDV inexistant
- `FileException`: Upload photo échouée

---

### ValiderVisiteHandler
**Localisation:** `src/Application/Visite/ValiderVisiteHandler.php`

**Responsabilité:** Valider ou rejeter une visite (Admin uniquement)

**Entrée:**
```php
$cmd = new ValiderVisiteCommande(
    transactionId: 1,
    approuver: true,               // true=VALIDEE, false=REJETEE
    commentaireRejet?: 'Hors zone' // Raison si rejet
);
```

**Processus:**
1. Récupère la transaction
2. Vérifie qu'elle est en statut EN_ATTENTE
3. Change le statut (VALIDEE ou REJETEE)
4. Ajoute le commentaire si rejet
5. Persiste

**Erreurs possibles:**
- `TransactionAlreadyPendingException`: Déjà traitée
- `InvalidArgumentException`: Transaction inexistante

---

## 💰 Services de Vente (Gérant)

### EnregistrerVenteHandler
**Localisation:** `src/Application/Gerant/EnregistrerVenteHandler.php`

**Responsabilité:** Enregistrer une vente de produit (Gérant)

**Entrée (DTO):**
```php
$cmd = new EnregistrerVenteCommande(
    pointVenteId: 1,               // Kiosque du gérant
    produitId: 5,                  // Produit vendu
    quantite: 3,                   // Quantité
    montantCentimes: 50000,        // Prix en centimes (500 FCFA)
    latitude: 3.8480,              // Position GPS
    longitude: 11.5021,
    commentaire?: 'Client satisfait'
);
```

**Processus:**
1. Récupère le point de vente
2. Crée une Transaction avec type VENTE
3. **Valide immédiatement** (pas d'attente admin)
4. Enregistre montant FCFA
5. Capture coordonnées GPS
6. Persiste

**Sortie:**
```php
Transaction (statut: VALIDEE)
```

**Particularité:**
- ✅ Les gérants valident directement leurs ventes
- ✅ Aucune approbation admin requise
- ✅ Autonomie commerciale garantie

---

## 👤 Services Utilisateur

### ChangerMotDePasseHandler
**Localisation:** `src/Application/Utilisateur/ChangerMotDePasseHandler.php`

**Entrée:**
```php
$cmd = new ChangerMotDePasseCommande(
    utilisateurId: 1,
    ancienMotDePasse: 'ancien123',
    nouveauMotDePasse: 'nouveau456'
);
```

**Processus:**
1. Récupère l'utilisateur
2. Vérifie ancien mot de passe (bcrypt verify)
3. Hash nouveau mot de passe (bcrypt coût 4)
4. Sauvegarde

**Erreurs:**
- `InvalidPasswordException`: Ancien mot de passe incorrect

---

### ModifierProfilHandler
**Localisation:** `src/Application/Utilisateur/ModifierProfilHandler.php`

**Entrée:**
```php
$cmd = new ModifierProfilCommande(
    utilisateurId: 1,
    prenom?: 'Jean',
    nom?: 'Dupont',
    telephone?: Telephone::fromString('+237690123456'),
    photoFile?: File               // Avatar (optionnel)
);
```

**Processus:**
1. Récupère l'utilisateur
2. Met à jour les champs
3. Upload photo si présente
4. Persiste

---

## 🏪 Services Point de Vente

### EnregistrerPointVenteHandler
**Localisation:** `src/Application/PointVente/EnregistrerPointVenteHandler.php`

**Entrée:**
```php
$cmd = new EnregistrerPointVenteCommande(
    nomPdv: 'Kiosque Douala',
    codeRef: 'PDV-001',            // Unique
    latitude: 3.8480,              // GPS
    longitude: 11.5021,
    ville: 'Douala',
    telephone: '+237690777777',
    gerantId: 1,                   // Assignation
    categoriePdvId?: 1
);
```

**Processus:**
1. Valide unicité du code_ref
2. Crée PointVente
3. Assigne le gérant
4. Initialise statut ACTIF
5. Persiste

**Erreurs:**
- `DuplicateCodeException`: code_ref déjà utilisé

---

## 📦 Services Repositories

### PointVenteRepository
```php
interface PointVenteRepositoryInterface {
    public function find(int $id): ?PointVente;
    public function findAll(): array;
    public function findOneByCodeRef(string $codeRef): ?PointVente;
    public function findByVille(string $ville): array;
    public function rechercher(string $terme): array;  // Recherche floue
    public function findByStatut(StatutPointVente $statut): array;
    public function findByGerant(Utilisateur $gerant): array;
    public function findProches(Coordonnees $position, float $rayonKm): array;
    public function save(PointVente $pdv, bool $flush = true): void;
}
```

**Méthodes clés:**

#### `rechercher(string $terme)`
Recherche par nom, ville ou code_ref (insensible à la casse)
```php
$pdv = $repo->rechercher('Douala');
// Résultat: Tous les PDV contenant "douala" (nom, ville, code)
```

#### `findProches(Coordonnees $pos, float $rayonKm)`
Utilise formule Haversine (SQL natif MySQL)
```php
$proches = $repo->findProches($maPosition, 1.0);
// PDV à moins de 1km
```

---

### TransactionRepository
```php
interface TransactionRepositoryInterface {
    public function find(int $id): ?Transaction;
    public function findByPointVente(PointVente $pdv): array;
    public function findByUtilisateur(Utilisateur $user): array;
    public function findByType(TypeTransaction $type): array;
    public function findByStatut(StatutTransaction $statut): array;
    public function findEntre(DateTimeImmutable $debut, DateTimeImmutable $fin): array;
    public function chiffreAffaires(
        PointVente $pdv,
        ?DateTimeImmutable $debut = null,
        ?DateTimeImmutable $fin = null
    ): Montant;
    public function save(Transaction $t, bool $flush = true): void;
}
```

**Méthode importante:**

#### `chiffreAffaires()`
Calcule uniquement les **ventes VALIDEES**
```php
$ca = $repo->chiffreAffaires($pdv, $debut, $fin);
// Montant en centimes FCFA (ex: 5000000 = 50000 FCFA)
```

---

### ProduitRepository
```php
interface ProduitRepositoryInterface {
    public function find(int $id): ?Produit;
    public function findAll(): array;
    public function findActifs(): array;
    public function findByCategorie(CategorieProd $cat): array;
    public function findDansFourchettePrix(Montant $min, Montant $max): array;
    public function findLivresAuPointVente(PointVente $pdv): array;
    public function save(Produit $p, bool $flush = true): void;
}
```

**Méthode clé:**

#### `findLivresAuPointVente(PointVente $pdv)`
Retourne les **FluxProduit** avec quantités cumulées
```php
$livraisons = $repo->findLivresAuPointVente($pdv);
// Chaque FluxProduit contient:
//   - produit (Produit)
//   - quantite (int)
//   - flux (FluxRavitaillement)
```

---

### UtilisateurRepository
```php
interface UtilisateurRepositoryInterface {
    public function find(int $id): ?Utilisateur;
    public function findAll(): array;
    public function findActifs(): array;
    public function findOneByEmail(Email $email): ?Utilisateur;
    public function findByRole(string $codeRole): array;
    public function rechercher(string $terme): array;  // Nom, prenom, email
    public function save(Utilisateur $u, bool $flush = true): void;
}
```

---

## 🔐 Services de Sécurité

### UtilisateurChecker
**Localisation:** `src/Infrastructure/Security/UtilisateurChecker.php`

**Rôle:** Post-authentification, vérifier que l'utilisateur est ACTIF

```php
public function postAuthenticationChecks(UserInterface $user): void {
    if (!$user->isActif()) {
        throw new DisabledException('Compte désactivé');
    }
}
```

---

### PointVenteVoter
**Localisation:** `src/Infrastructure/Security/Voter/PointVenteVoter.php`

**Permissions:**

| Rôle | VOIR | MODIFIER | SUPPRIMER | CREER |
|------|------|----------|-----------|-------|
| ADMIN | ✅ Tous | ✅ Tous | ✅ Tous | ✅ |
| AGENT | ✅ Tous | ❌ | ❌ | ✅ |
| GERANT | ✅ Sien | ❌ | ❌ | ❌ |

```php
// Utilisation dans Controller
$this->denyAccessUnlessGranted('MODIFIER', $pointVente);
```

---

### TransactionVoter
**Localisation:** `src/Infrastructure/Security/Voter/TransactionVoter.php`

| Rôle | VOIR | VALIDER | CREER |
|------|------|---------|-------|
| ADMIN | ✅ Toutes | ✅ | ❌ |
| AGENT | ✅ Ses visites | ❌ | ✅ |
| GERANT | ✅ Son kiosque | ❌ | ✅ (auto-valide) |

---

## 📸 Services Upload

### PhotoPreuveUploader
**Localisation:** `src/Infrastructure/Upload/PhotoPreuveUploader.php`

**Responsabilité:** Gérer l'upload des preuves photo de visite

```php
public function upload(
    File $file,
    Transaction $transaction
): string {
    // 1. Valide la photo (type, taille)
    // 2. Génère nom unique
    // 3. Déplace dans public/uploads/preuves/
    // 4. Retourne URL relative
    // 5. Attache à $transaction.photoPreuveUrl
}
```

**Configuration:**
- Dossier: `public/uploads/preuves/`
- Extensions: jpg, jpeg, png, webp
- Taille max: 5 MB
- Nommage: UUID + extension originale

---

### PhotoManager
**Localisation:** `src/Infrastructure/Upload/PhotoManager.php`

**Responsabilité:** Upload générique par type (PROFIL_UTILISATEUR, PREUVE_VISITE, etc.)

```php
public function upload(
    File $file,
    TypePhoto $type,
    string $identifier
): string {
    // Routage vers bon dossier selon $type
}
```

---

## 💻 Services Console

### InitDatabaseCommand
**Localisation:** `src/Infrastructure/Console/InitDatabaseCommand.php`

**Commande:** `php bin/console app:init:database`

**Processus:**
1. Crée le schéma de base
2. Efface les données existantes
3. Crée 3 rôles (ADMIN, AGENT, GERANT)
4. Crée 5 catégories PDV + 7 catégories produits
5. Optionnel: Génère données de test
   - 1 Admin, 15 agents, 8 gérants
   - 26 agences MTN réelles
   - 46 produits MTN réels
   - 400 transactions simulées
   - 204 notifications

**Usage:**
```bash
# Mode interactif
php bin/console app:init:database

# Mode auto-confirm
echo -e "yes\nyes" | php bin/console app:init:database
```

---

### CreerUtilisateurCommand
**Localisation:** `src/Infrastructure/Console/CreerUtilisateurCommand.php`

**Commande:** `php bin/console app:utilisateur:creer`

**Interactive:**
```bash
Name: Jean
Prenom: Dupont
Email: jean@mtnpdv.local
Telephone: +237690123456
Password: ****
Roles: ADMIN,AGENT
```

---

## 🔄 Flux d'une transaction complète

```
1. Controller reçoit requête HTTP
2. Crée DTO (Commande)
3. Appelle Handler
4. Handler valide métier
5. Handler crée/modifie Entité domaine
6. Handler appelle Repository.save()
7. Repository persiste via Doctrine
8. Transaction base de données
9. Controller affiche réponse
```

---

## 📊 Injection de dépendances

```yaml
services:
  # Handlers
  App\Application\Visite\EnregistrerVisiteHandler:
    arguments:
      - '@App\Domain\Repository\PointVenteRepositoryInterface'
      - '@App\Domain\Repository\TransactionRepositoryInterface'
      - '@App\Infrastructure\Upload\PhotoPreuveUploader'
  
  # Voters
  App\Infrastructure\Security\Voter\PointVenteVoter:
    tags: ['security.voter']
```

---

**Version:** 1.0  
**Services count:** 15+
