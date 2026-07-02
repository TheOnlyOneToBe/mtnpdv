# 📄 Pages d'Erreur et Redirection - MTNPDV

Ce document décrit les templates d'erreur et de redirection disponibles dans l'application.

## 🚨 Pages d'Erreur HTTP

Tous les templates d'erreur héritent du template de base `error.html.twig` et fournissent une interface cohérente et conviviale pour les erreurs.

### Localisation
Les templates se trouvent dans: `templates/bundles/TwigBundle/Exception/`

### 1. Erreur 400 - Mauvaise Requête
**Fichier:** `error400.html.twig`
**Icône:** ⚠️ Exclamation Triangle
**Cas d'usage:**
- Requête invalide
- Paramètres manquants ou mal formés
- Validation de formulaire échouée

```
Le serveur n'a pas pu comprendre votre requête.
Vérifiez les informations envoyées.
```

### 2. Erreur 403 - Accès Refusé
**Fichier:** `error403.html.twig`
**Icône:** 🔒 Cadenas
**Cas d'usage:**
- Utilisateur non authentifié accédant une ressource protégée
- Permissions insuffisantes
- Rôle utilisateur invalide

```
Vous n'avez pas les permissions pour accéder à cette ressource.
Seuls les utilisateurs autorisés peuvent y accéder.
```

### 3. Erreur 404 - Page Non Trouvée
**Fichier:** `error404.html.twig`
**Icône:** 🔍 Loupe
**Cas d'usage:**
- URL inexistante
- Route non définie
- Ressource supprimée

```
La page que vous recherchez n'existe pas ou a été déplacée.
Vérifiez l'URL et réessayez.
```

### 4. Erreur 405 - Méthode Non Autorisée
**Fichier:** `error405.html.twig`
**Icône:** 🚫 Interdiction
**Cas d'usage:**
- Mauvaise méthode HTTP (GET vs POST)
- Route n'accepte pas cette méthode
- Configuration incorrecte

```
La méthode HTTP utilisée n'est pas autorisée pour cette ressource.
Seules certaines méthodes sont acceptées.
```

### 5. Erreur 500 - Erreur Interne Serveur
**Fichier:** `error500.html.twig`
**Icône:** 🔥 Feu
**Cas d'usage:**
- Exception non gérée
- Erreur base de données
- Bug dans le code

```
Oups! Une erreur interne s'est produite sur le serveur.
Nous travaillons pour résoudre ce problème au plus vite.
```

### 6. Erreur 503 - Service Indisponible
**Fichier:** `error503.html.twig`
**Icône:** 🔧 Outils
**Cas d'usage:**
- Maintenance en cours
- Serveur en panne
- Trop de requêtes

```
Le serveur est actuellement indisponible.
Maintenance en cours. Nous serons bientôt de retour!
```

---

## 🔄 Pages de Redirection

### 1. Page de Chargement (Loading)
**Fichier:** `templates/redirect/loading.html.twig`

Page de transition avec spinner animé et redirection automatique.

**Paramètres Twig:**
```twig
{{ redirect_url }}  # URL vers laquelle rediriger après 2 secondes
```

**Exemple d'usage dans un contrôleur:**
```php
return $this->render('redirect/loading.html.twig', [
    'redirect_url' => $this->generateUrl('app_dashboard'),
]);
```

**Caractéristiques:**
- ✅ Spinner animé
- ✅ Barre de progression
- ✅ Redirection automatique après 2s
- ✅ Design gradient
- ✅ Message personnalisable

---

### 2. Page Maintenance
**Fichier:** `templates/pages/maintenance.html.twig`

Page à afficher quand l'application est en maintenance.

**Paramètres Twig:**
```twig
# Aucun paramètre requis (tout est codé en dur)
```

**Exemple d'usage dans un contrôleur:**
```php
return $this->render('pages/maintenance.html.twig');
```

**Contient:**
- Icône maintenance
- Horaire de maintenance
- Timeline des étapes
- Contact support
- Animation flottante

**Pour activer globalement en maintenance:**
Créer un middleware ou vérifier dans kernel.php:
```php
if (file_exists($this->getProjectDir() . '/MAINTENANCE')) {
    return new Response(
        $this->container->get('twig')->render('pages/maintenance.html.twig'),
        503
    );
}
```

---

### 3. Page Accès Refusé
**Fichier:** `templates/pages/access-denied.html.twig`

Page d'accès refusé avec raison optionnelle.

**Paramètres Twig:**
```twig
{{ reason }}  # Raison de refus (optionnel)
```

**Exemple d'usage dans un contrôleur:**
```php
return $this->render('pages/access-denied.html.twig', [
    'reason' => 'Vous devez être administrateur pour accéder à cette page.'
]);
```

**Boutons:**
- Retour à l'accueil
- Retour précédent

---

### 4. Page Succès
**Fichier:** `templates/pages/success.html.twig`

Page de confirmation avec redirection optionnelle.

**Paramètres Twig:**
```twig
{{ title }}         # Titre de la page (optionnel)
{{ message }}       # Message de succès (optionnel)
{{ details }}       # Array de détails à afficher (optionnel)
{{ redirect_url }}  # URL de redirection après 5s (optionnel)
```

**Exemple d'usage complet:**
```php
return $this->render('pages/success.html.twig', [
    'title' => 'Utilisateur créé avec succès',
    'message' => 'Le nouvel utilisateur a été ajouté à la base de données.',
    'details' => [
        'Email: john@example.com',
        'Rôle: ADMIN',
        'Statut: ACTIF'
    ],
    'redirect_url' => $this->generateUrl('app_admin_utilisateurs')
]);
```

**Caractéristiques:**
- ✅ Icône animée (checkmark)
- ✅ Titre et message personnalisés
- ✅ Liste de détails optionnels
- ✅ Redirection auto après 5s
- ✅ Compte à rebours visible

---

## 🎨 Personnalisation

### Thème Couleur
Les couleurs principales utilisées:
- Primaire: `#667eea` (Indigo)
- Secondaire: `#764ba2` (Purple)
- Texte: `#333333` (Dark Gray)
- Gris moyen: `#666666`
- Gris clair: `#999999`

Modifier dans le template `error.html.twig`:
```css
.error-icon {
    color: #667eea;  /* Changer ici */
}
```

### Animations
Toutes les pages utilisent des animations CSS:
- `slideUp` - Animation d'apparition
- `float` - Animation flottante
- `spin` - Spinner
- `progress` - Barre de progression
- `blink` - Clignotement

### Icônes
Utilise Font Awesome 6.4.0 via CDN:
```html
<i class="fas fa-check"></i>
<i class="fas fa-lock"></i>
<i class="fas fa-exclamation-circle"></i>
```

---

## 📱 Responsive Design

Tous les templates sont entièrement responsifs:
- ✅ Mobile (< 600px)
- ✅ Tablette (600px - 1024px)
- ✅ Desktop (> 1024px)

Media query de base:
```css
@media (max-width: 600px) {
    .error-container {
        padding: 40px 20px;
    }
}
```

---

## 🛠️ Comment Utiliser dans les Contrôleurs

### Exception automatique
Symfony affiche automatiquement les pages d'erreur:
```php
throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
// Affiche error404.html.twig
```

### Page personnalisée
```php
throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Raison');
// Affiche error403.html.twig
```

### Page de succès
```php
return $this->render('pages/success.html.twig', [
    'title' => 'Transaction enregistrée',
    'message' => 'Votre transaction a été créée avec succès.',
    'redirect_url' => $this->generateUrl('app_transactions_show', ['id' => $id])
]);
```

### Redirection avec chargement
```php
return $this->render('redirect/loading.html.twig', [
    'redirect_url' => $this->generateUrl('app_dashboard')
]);
```

---

## 🔗 Routes à Créer

Pour utiliser les pages de redirection, créer les routes:

```php
// src/Controller/PagesController.php

#[Route('/maintenance', name: 'app_maintenance')]
public function maintenance(): Response
{
    return $this->render('pages/maintenance.html.twig');
}

#[Route('/access-denied', name: 'app_access_denied')]
public function accessDenied(Request $request): Response
{
    return $this->render('pages/access-denied.html.twig', [
        'reason' => $request->query->get('reason', 'Permissions insuffisantes')
    ]);
}
```

---

## 🧪 Test en Développement

### Voir une page d'erreur
```bash
# Accéder à une URL inexistante (404)
http://localhost:8000/inexistant

# Accéder sans permissions (403)
http://localhost:8000/admin  # Sans authentication

# Erreur serveur (500)
# Modifier un contrôleur pour lever une exception
```

### Désactiver le mode débogage
Pour voir les templates en prod:
```bash
# Dans .env
APP_DEBUG=0
```

---

## 📋 Checklist Avant Production

- [ ] Vérifier tous les codes d'erreur (400, 403, 404, 405, 500, 503)
- [ ] Tester sur mobile et desktop
- [ ] Vérifier que les liens de contact fonctionnent
- [ ] Personnaliser les emails dans error500.html.twig
- [ ] Mettre à jour les horaires de maintenance si applicable
- [ ] Tester la redirection automatique
- [ ] Vérifier le CSS/images se charge correctement (pas de 404)

---

## 🔗 Ressources

- [Symfony Error Handling](https://symfony.com/doc/current/controller/error_pages.html)
- [Exception Codes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status)
- [Twig Documentation](https://twig.symfony.com/)
