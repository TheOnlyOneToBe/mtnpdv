# Architecture Technique - MTNPDV

> **Documentation de l'architecture technique et des patterns du projet MTNPDV**

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture globale](#architecture-globale)
3. [Domain-Driven Design](#domain-driven-design)
4. [Structure des couches](#structure-des-couches)
5. [Patterns utilisés](#patterns-utilisés)
6. [Sécurité](#sécurité)
7. [Performance](#performance)

## 🎯 Vue d'ensemble

MTNPDV utilise une architecture moderne basée sur les principes du **Domain-Driven Design (DDD)** avec une séparation claire entre les couches métier et technique.

### Principes fondamentaux

- **Domaine au centre**: La logique métier est indépendante du framework
- **Abstraction**: Repositories et interfaces découplez la persistance
- **Testabilité**: Chaque couche est testable indépendamment
- **Maintenabilité**: Code structuré et organisé logiquement

## 🏗️ Architecture globale

```
┌─────────────────────────────────────────────┐
│         COUCHE PRÉSENTATION                 │
│   (Templates Twig, Assets, Controllers)    │
└──────────────┬──────────────────────────────┘
               │
┌──────────────▼──────────────────────────────┐
│      COUCHE APPLICATION                     │
│  (Handlers, Services, Cas d'usage)         │
└──────────────┬──────────────────────────────┘
               │
┌──────────────▼──────────────────────────────┐
│      COUCHE DOMAINE (Métier)                │
│  (Entités, Enums, Value Objects, Logique)  │
└──────────────┬──────────────────────────────┘
               │
┌──────────────▼──────────────────────────────┐
│      COUCHE INFRASTRUCTURE                  │
│  (Doctrine, Cache, Upload, Rate Limiting)  │
└─────────────────────────────────────────────┘
```

## 🎲 Domain-Driven Design

### Entités de domaine

Les entités métier sont définies dans `src/Domain/Entity/`:

```php
// Exemple: Utilisateur (entité aggregate root)
#[ORM\Entity]
class Utilisateur implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private Email $email;

    #[ORM\Column]
    private string $prenomUt;

    #[ORM\Column]
    private Telephone $telephone;

    // Logique métier
    public function aLeRole(string $code): bool { ... }
    public function getRoles(): array { ... }
}
```

**Caractéristiques**:
- Identité unique (ID)
- Logique métier encapsulée
- Value Objects pour propriétés complexes
- Validation au niveau entité

### Value Objects

Objets immuables représentant des concepts métier:

```php
// Email - garantit validité
final class Email implements \Stringable
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }
    }

    public function toString(): string { return $this->value; }
}

// Montant - précision aux centimes
final class Montant implements \Stringable
{
    public function __construct(private int $centimes)
    {
        if ($centimes < 0) {
            throw new \InvalidArgumentException('Montant négatif');
        }
    }

    public function toDecimal(): float { return $this->centimes / 100; }
}

// Coordonnees - position GPS
final class Coordonnees
{
    public function __construct(
        public float $latitude,
        public float $longitude
    ) {
        $this->validate();
    }

    public function distanceVers(Coordonnees $autre): float
    {
        // Haversine formula
    }
}
```

**Avantages**:
- Type-safety
- Validation garantie
- Immuabilité
- Logique métier encapsulée

### Énumérations

Énums définissant les états du domaine:

```php
enum StatutTransaction: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case VALIDEE = 'VALIDEE';
    case REJETEE = 'REJETEE';

    public function estFinal(): bool
    {
        return $this === self::VALIDEE || $this === self::REJETEE;
    }
}

enum StatutUtilisateur: string
{
    case ACTIF = 'ACTIF';
    case INACTIF = 'INACTIF';
}
```

## 📚 Structure des couches

### 1. Couche Domaine (`src/Domain/`)

**Responsabilité**: Logique métier pure, indépendante du framework

```
src/Domain/
├── Entity/              # Entités (Utilisateur, PDV, Transaction, etc.)
├── Enum/                # Énumérations (Statuts, Types)
├── ValueObject/         # Value Objects (Email, Telephone, etc.)
├── Repository/          # Interfaces repository (contrats)
│   ├── UtilisateurRepositoryInterface.php
│   ├── PointVenteRepositoryInterface.php
│   └── TransactionRepositoryInterface.php
└── Exception/           # Exceptions métier
    ├── DomainException.php
    └── EntityNotFoundException.php
```

**Règles**:
- ❌ Pas d'accès à Doctrine, Request, Logger
- ✅ Logique métier pure
- ✅ Validation des données
- ✅ Exceptions métier

### 2. Couche Application (`src/Application/`)

**Responsabilité**: Cas d'utilisation et orchestration

```
src/Application/
├── Dashboard/
│   └── DashboardStatisticsService.php
├── Utilisateur/
│   ├── CreerUtilisateurCommand.php
│   ├── CreerUtilisateurHandler.php
│   └── ...
└── Transaction/
    ├── EnregistrerVisiteCommand.php
    └── EnregistrerVisiteHandler.php
```

**Patterns utilisés**:

```php
// Command Pattern
final class CreerUtilisateurCommand
{
    public function __construct(
        public string $email,
        public string $prenom,
        public string $nom,
        // ...
    ) {}
}

// Handler Pattern
final class CreerUtilisateurHandler
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UtilisateurRepositoryInterface $repo,
    ) {}

    public function handle(CreerUtilisateurCommand $cmd): Utilisateur
    {
        // Validation
        // Création
        // Persistance
        // Événements?
    }
}
```

### 3. Couche Infrastructure (`src/Infrastructure/`)

**Responsabilité**: Implémentations techniques

```
src/Infrastructure/
├── Doctrine/
│   ├── Repository/      # Implémentations repository
│   ├── Type/            # Types DBAL custom
│   └── Mapping/         # Mappings ORM
├── Security/
│   ├── Voter/           # Voters RBAC
│   ├── Provider/        # User provider
│   └── UtilisateurChecker.php
├── Pagination/
│   └── PaginationService.php
├── RateLimit/
│   └── SearchRateLimiter.php
└── Upload/
    └── PhotoPreuveUploader.php
```

**Types DBAL custom**:

```php
// Mappe Email VO à colonne VARCHAR en DB
final class EmailType extends Type
{
    public function convertToPHPValue($value, AbstractPlatform $platform): Email
    {
        return new Email($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof Email ? $value->toString() : $value;
    }
}
```

### 4. Couche Présentation (`src/Controller/`, `templates/`)

**Responsabilité**: HTTP, Twig, Assets

```
src/Controller/
├── Admin/
│   ├── AdminDashboardController.php
│   ├── AdminPointVenteController.php
│   ├── AdminUtilisateurController.php
│   └── ...
├── UtilisateurController.php
└── SecurityController.php

templates/
├── base.html.twig
├── admin/
│   ├── dashboard.html.twig
│   ├── pdv/
│   ├── utilisateur/
│   └── ...
├── utilisateur/
│   └── profil/
└── ...
```

**Responsabilités des Controllers**:

```php
#[Route('/admin/pdv', name: 'app_admin_pdv_')]
class AdminPointVenteController extends AbstractController
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pdvs,
        private readonly PaginationService $pagination,
    ) {}

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        try {
            // 1. Récupérer données via repository (infrastructure)
            $allPdvs = $this->pdvs->findAll();

            // 2. Appliquer logique applicative (pagination)
            $paginated = $this->pagination->paginate($allPdvs, $page);

            // 3. Rendre la vue
            return $this->render('admin/pdv/list.html.twig', [
                'pointVentes' => $paginated['items'],
                'pagination' => $paginated,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur: ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }
}
```

## 🎯 Patterns utilisés

### Repository Pattern

**But**: Abstraction de la persistance

```php
// Interface (Domaine)
interface UtilisateurRepositoryInterface
{
    public function find(int $id): ?Utilisateur;
    public function findByEmail(Email $email): ?Utilisateur;
    public function findByRole(string $role): array;
    public function save(Utilisateur $user): void;
    public function remove(Utilisateur $user): void;
}

// Implémentation (Infrastructure)
#[AsRepository(Utilisateur::class)]
final class DoctrineUtilisateurRepository extends ServiceEntityRepository
    implements UtilisateurRepositoryInterface
{
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.codeRole = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getResult();
    }
}
```

### Dependency Injection

**But**: Découplage et testabilité

```php
// Injection automatique via Symfony
class MonController
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $repo,  // Interface
        private readonly PaginationService $pagination,          // Service
        private readonly LoggerInterface $logger,                # Logger
    ) {}
}
```

### Voters (Authorization)

**But**: Contrôle d'accès granulaire

```php
final class PointVenteVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof PointVente && in_array($attribute, ['VOIR', 'MODIFIER', 'SUPPRIMER']);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Admin: accès total
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Gérant: uniquement son PDV
        if (in_array('ROLE_GERANT', $user->getRoles())) {
            return $subject->getGerant() === $user;
        }

        return false;
    }
}
```

### Value Object Pattern

**But**: Type-safety et validation

```php
// Validation au niveau VO
$email = new Email('invalid-email'); // Lève exception immédiatement

// Immuabilité
$montant = new Montant(1000);
$montant->centimes = 2000; // ❌ Impossible, propriété private

// Logique métier
$distance = $pdv1->coordonnees->distanceVers($pdv2->coordonnees);
```

## 🔐 Sécurité

### Authentification

- **Provider**: `UtilisateurProvider` (recherche par email)
- **Hachage**: Argon2id (Symfony native)
- **Sessions**: PHP session native

```php
// config/packages/security.yaml
security:
    providers:
        utilisateur_provider:
            entity:
                class: App\Domain\Entity\Utilisateur
                property: email

    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
            algorithm: auto
```

### Autorisation (RBAC)

- **Rôles**: ROLE_ADMIN, ROLE_AGENT, ROLE_GERANT
- **Voters**: Implémentent la logique d'accès par ressource
- **Access Control**: Route-level en security.yaml

```php
// Dans les controllers
#[IsGranted('ROLE_ADMIN')]
#[IsGranted('VOIR', subject: 'pointVente')]
public function show(PointVente $pointVente): Response
```

### Protection CSRF

- Tokens CSRF sur tous les formulaires
- Validation automatique par Symfony
- Gestion dans les templates Twig

```twig
{{ form_rest(form) }}
{# Ajoute automatiquement le token CSRF #}
```

### Validation

**Côté serveur** (toujours):
```php
// Constraints sur entités/VO
#[Email]
#[Length(min: 5, max: 255)]
private Email $email;
```

**Côté client** (UX):
```twig
<input type="email" required pattern="...">
```

### Protection des données sensibles

- Mots de passe: Hachés avec Argon2id
- Emails: Uniques au niveau DB
- Données GPS: Accessibles selon rôle

## ⚡ Performance

### Pagination

```php
// Service centralisé
$pagination = $this->pagination->paginate($items, $page, 12);
// Retourne: ['items' => [...], 'currentPage' => 1, 'totalPages' => 10, ...]
```

### Rate Limiting

```php
// Limite 60 req/min pour la recherche
if ($this->rateLimiter->isLimited($userId)) {
    // Rejeter la requête
}
```

### Cache

- Query cache: Doctrine (APCu en prod)
- Router cache: Symfony compilation
- Twig cache: Compilation automatique

### Lazy Loading

- Associations Doctrine: Lazy par défaut
- Évite N+1 queries

```php
// ✅ Bon: Eager loading
$pdvs = $this->repo->createQueryBuilder('p')
    ->leftJoin('p.gerant', 'g')
    ->addSelect('g')
    ->getQuery()
    ->getResult();

// ❌ Mauvais: N+1 queries
foreach ($pdvs as $pdv) {
    echo $pdv->gerant->prenomUt; // Query pour chaque PDV
}
```

## 📊 Diagramme des dépendances

```
┌─────────────────────────────────────────────┐
│         Contrôleur HTTP                     │
│  (reçoit Request, retourne Response)        │
└──────────────┬──────────────────────────────┘
               │ utilise
               ▼
┌──────────────────────────────────────────────┐
│      Handler / Service Applicatif            │
│  (logique du cas d'usage)                    │
└──────────────┬──────────────────────────────┘
               │ utilise
               ▼
┌──────────────────────────────────────────────┐
│      Entités & Value Objects Domaine         │
│  (logique métier pur)                        │
└──────────────┬──────────────────────────────┘
               │ utilise
               ▼
┌──────────────────────────────────────────────┐
│   Interfaces Repository (Domaine)            │
│  (contrats de persistance)                   │
└──────────────┬──────────────────────────────┘
               │ implémentée par
               ▼
┌──────────────────────────────────────────────┐
│   Doctrine Repository (Infrastructure)       │
│  (implémentation DB)                         │
└──────────────────────────────────────────────┘
```

## 🧪 Testabilité

### Tests unitaires (domaine)

```php
// Pas de dépendances externes
public function testEmailValidation()
{
    $this->expectException(\InvalidArgumentException::class);
    new Email('invalid-email');
}

public function testMontantCalculation()
{
    $m = new Montant(1000);
    $this->assertEquals(10.00, $m->toDecimal());
}
```

### Tests intégration (infrastructure)

```php
// Utilise une BD de test (SQLite)
public function testUtilisateurRepository()
{
    $user = new Utilisateur(...);
    $this->repository->save($user);

    $found = $this->repository->findByEmail($user->email);
    $this->assertSame($user->id, $found->id);
}
```

### Tests fonctionnels (contrôleurs)

```php
// Lance le serveur de test
public function testAdminDashboard()
{
    $this->client->loginUser($this->adminUser);
    $this->client->request('GET', '/admin/dashboard');

    $this->assertResponseIsSuccessful();
    $this->assertSelectorExists('[data-controller~="charts"]');
}
```

---

**Dernière mise à jour**: 2026-07-02  
**Version de l'architecture**: 1.0.0
