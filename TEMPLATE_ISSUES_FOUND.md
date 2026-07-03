# ⚠️ Problèmes Détectés dans les Templates

## 📋 Résumé des Problèmes

Vérification approfondie effectuée sur **tous** les templates (admin, agent, gérant, utilisateur).

**Statut Global:** ⚠️ **PROBLÈMES TROUVÉS**

- ✅ Templates Admin: **TOUS VALIDES** (19 templates)
- ❌ Templates Agent: **5 PROBLÈMES**
- ❌ Templates Profil: **1 PROBLÈME**
- ✅ Templates Gérant: À vérifier
- ✅ Autres: À vérifier

---

## ❌ Problèmes Détectés

### 1. Template Agent - Liste des Visites
**Fichier:** `templates/agent/visite/list.html.twig`

#### Problème 1.1 - Propriété incorrecte (ligne 71)
```twig
❌ {{ visite.getDateCreation()|date('d/m/Y H:i') }}
```
**Cause:** Transaction entity a `getDateTransac()` pas `getDateCreation()`  
**Correction:**
```twig
✅ {{ visite.dateTransac|date('d/m/Y H:i') }}
```
**Entité correcte:**
```php
// Transaction.php
#[ORM\Column(name: 'date_transac', type: Types::DATETIME_IMMUTABLE)]
private \DateTimeImmutable $dateTransac;

public function getDateTransac(): \DateTimeImmutable { ... }
```

---

#### Problème 1.2 - Accès enum incorrect (ligne 72)
```twig
❌ {{ visite.getType().name }}
```
**Cause:** Les enums en Symfony utilisent `.value` pas `.name`  
**Correction:**
```twig
✅ {{ visite.type.value }}
```
**Référence:**
```php
// TypeTransaction.php
enum TypeTransaction: string {
    case VENTE = 'VENTE';      // .value retourne 'VENTE'
    case DEPENSE = 'DEPENSE';  // .name retourne 'DEPENSE' (en PHP)
}
```
**Note:** En Twig, utiliser `.value` pour la valeur de l'enum

---

#### Problème 1.3 - Méthode inexistante (ligne 73)
```twig
❌ {{ visite.getMontant().getValeur() / 100 }}
```
**Cause:** Montant VO n'a pas de méthode `getValeur()`. Les bonnes méthodes sont `montantCentimes()` ou `centimes()`  
**Correction:**
```twig
✅ {{ visite.montant.montantCentimes / 100 }}
```
**OU:**
```twig
✅ {{ visite.montant.toDecimal() }}
```
**Montant VO disponible:**
```php
// Montant.php
public function montantCentimes(): int { ... }    // Alias de centimes()
public function toDecimal(): string { ... }       // Format "1250.50"
public function centimes(): int { ... }
public function __toString(): string { ... }      // Via toDecimal()
```

---

#### Problème 1.4 - Accès enum incorrect (lignes 76, 78, 83)
```twig
❌ {% if visite.getStatut().name == 'VALIDEE' %}
❌ {{ visite.getStatut().name }}
```
**Cause:** Même problème que 1.2 - utiliser `.value` pas `.name`  
**Correction:**
```twig
✅ {% if visite.statut.value == 'VALIDEE' %}
✅ {{ visite.statut.value }}
```

---

#### Problème 1.5 - Appels de méthode incorrects (lignes 70, 73, 87)
```twig
❌ {{ visite.getPointVente().getNomPdv() }}
❌ {{ visite.getMontant() ? ... }}
❌ {{ visite.getId() }}
```
**Cause:** Twig supporte l'accès aux propriétés via getters automatiquement. Pas besoin d'appeler `.get*()`  
**Correction:**
```twig
✅ {{ visite.pointVente.nomPdv }}
✅ {{ visite.montant ? ... }}
✅ {{ visite.id }}
```

---

### 2. Template Agent - Détails de la Visite
**Fichier:** `templates/agent/visite/show.html.twig`

#### Problème 2.1 - Propriété incorrecte (lignes 10, 91)
```twig
❌ {{ visite.dateCreation|date('d/m/Y à H:i') }}
```
**Cause:** Transaction a `dateTransac` pas `dateCreation`  
**Correction:**
```twig
✅ {{ visite.dateTransac|date('d/m/Y à H:i') }}
```

---

#### Problème 2.2 - Accès enum incorrect (lignes 28, 40, 44, 48)
```twig
❌ {% if visite.statut.name == 'VALIDEE' %}
❌ {{ visite.statut.name }}
```
**Cause:** Utiliser `.value` pas `.name` pour les enums  
**Correction:**
```twig
✅ {% if visite.statut.value == 'VALIDEE' %}
✅ {{ visite.statut.value }}
```

---

#### Problème 2.3 - Propriété inexistante (lignes 98, 107, 111)
```twig
❌ {% if visite.position %}
❌ <p class="mb-0"><code>{{ visite.position.latitude }}</code></p>
❌ <p class="mb-0"><code>{{ visite.position.longitude }}</code></p>
```
**Cause:** Transaction n'a pas de propriété `position`. Elle a `coordonneesCapture` (Coordonnees VO)  
**Correction:**
```twig
✅ {% if visite.coordonneesCapture %}
✅ <p class="mb-0"><code>{{ visite.coordonneesCapture.latitude }}</code></p>
✅ <p class="mb-0"><code>{{ visite.coordonneesCapture.longitude }}</code></p>
```
**Référence Transaction:**
```php
// Transaction.php
public function getCoordonneesCapture(): Coordonnees { ... }

#[ORM\Column(name: 'latitude_capture', type: Types::DECIMAL, precision: 10, scale: 8)]
private string $latitudeCapture;

#[ORM\Column(name: 'longitude_capture', type: Types::DECIMAL, precision: 11, scale: 8)]
private string $longitudeCapture;
```

---

### 3. Template Profil - Affichage du Profil
**Fichier:** `templates/utilisateur/profil/show.html.twig`

#### Problème 3.1 - Propriété incorrecte (ligne 77)
```twig
❌ <span class="badge bg-primary me-2 mb-2 p-2">
    <i class="fas fa-shield-halved"></i> {{ role.libelleRole }}
</span>
```
**Cause:** Role entity a `libelle` pas `libelleRole`  
**Correction:**
```twig
✅ <span class="badge bg-primary me-2 mb-2 p-2">
    <i class="fas fa-shield-halved"></i> {{ role.libelle }}
</span>
```
**Référence Role:**
```php
// Role.php
#[ORM\Column(type: Types::STRING, length: 100)]
private string $libelle;

public function getLibelle(): string { ... }
```

---

## 📊 Tableau Récapitulatif

| Template | Ligne | Problème | Sévérité | Statut |
|----------|-------|---------|----------|--------|
| agent/visite/list.html.twig | 71 | `getDateCreation()` → `dateTransac` | 🔴 Critique | À corriger |
| agent/visite/list.html.twig | 72 | `.name` → `.value` (enum) | 🔴 Critique | À corriger |
| agent/visite/list.html.twig | 73 | `getValeur()` n'existe pas | 🔴 Critique | À corriger |
| agent/visite/list.html.twig | 76, 78, 83 | `.name` → `.value` (enum) | 🔴 Critique | À corriger |
| agent/visite/list.html.twig | 70, 73, 87 | Appels `.get*()` inutiles | 🟡 Mineure | À corriger |
| agent/visite/show.html.twig | 10, 91 | `dateCreation` → `dateTransac` | 🔴 Critique | À corriger |
| agent/visite/show.html.twig | 28, 40, 44, 48 | `.name` → `.value` (enum) | 🔴 Critique | À corriger |
| agent/visite/show.html.twig | 98, 107, 111 | `position` → `coordonneesCapture` | 🔴 Critique | À corriger |
| utilisateur/profil/show.html.twig | 77 | `libelleRole` → `libelle` | 🔴 Critique | À corriger |

---

## 🔍 Analyse d'Impact

### Rendu du Template Sans Correction
Ces erreurs causent:
- **Affichage vide** sur certains champs (Twig silencieusement ignore les propriétés inexistantes)
- **Exceptions** si des méthodes inexistantes sont appelées
- **Valeurs incorrectes** pour les enums (`.name` retourne le nom PHP, pas la valeur)

### Impact Utilisateur
- Les visites ne s'affichent pas correctement dans la liste agent
- Les détails de visite manquent de données
- Le profil utilisateur affiche "libelle" au lieu du nom du rôle

---

## ✅ Solution

Chaque problème doit être corrigé dans le template correspondant:

### À Corriger
1. `templates/agent/visite/list.html.twig` - 5 corrections
2. `templates/agent/visite/show.html.twig` - 3 corrections  
3. `templates/utilisateur/profil/show.html.twig` - 1 correction

**Total:** 9 corrections à appliquer

---

## 📝 Prochaines Étapes

- [ ] Corriger `agent/visite/list.html.twig`
- [ ] Corriger `agent/visite/show.html.twig`
- [ ] Corriger `utilisateur/profil/show.html.twig`
- [ ] Vérifier les templates gérant et autres
- [ ] Tester end-to-end après les corrections
- [ ] Vérifier dans le navigateur que l'affichage est correct

---

## 📚 Ressources

- Entity Transaction: `src/Domain/Entity/Transaction.php`
- VO Montant: `src/Domain/ValueObject/Montant.php`
- VO Coordonnees: `src/Domain/ValueObject/Coordonnees.php`
- Entity Role: `src/Domain/Entity/Role.php`
- Enum StatutTransaction: `src/Domain/Enum/StatutTransaction.php`
- Enum TypeTransaction: `src/Domain/Enum/TypeTransaction.php`

---

**Date:** 2026-07-03  
**Vérificateur:** Claude Code
