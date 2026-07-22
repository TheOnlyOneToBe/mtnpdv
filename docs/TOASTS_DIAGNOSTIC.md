# 🔧 Diagnostic et Configuration des Toasts

## Problème Identifié

Les contrôleurs Stimulus pour les toasts (`toast-controller.js` et `flash-toast-controller.js`) n'étaient **pas enregistrés** dans l'application Stimulus, ce qui empêchait leur fonctionnement.

## Solutions Appliquées

### 1️⃣ Enregistrement des Contrôleurs Stimulus
**Fichier**: `assets/app.js`

- Importation des contrôleurs toast et flash-toast
- Enregistrement auprès de l'application Stimulus
- Les contrôleurs sont maintenant disponibles sur `data-controller="toast"` et `data-controller="flash-toast"`

### 2️⃣ Configuration du Conteneur de Toasts
**Fichier**: `templates/base.html.twig`

```html
<!-- Toast Container avec contrôleur -->
<div data-controller="toast" id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<!-- Flash Messages (convertis en toasts) -->
<div data-controller="flash-toast">
    {% for label, messages in app.flashes %}
        {% for message in messages %}
            <div data-flash-type="{{ label }}" style="display: none;">
                {{ message }}
            </div>
        {% endfor %}
    {% endfor %}
</div>
```

### 3️⃣ Exception Listener pour Accès Refusé
**Fichier**: `src/Infrastructure/EventListener/ExceptionListener.php`

- Écoute les exceptions HTTP `AccessDeniedHttpException`
- Ajoute un message flash "Accès refusé"
- Redirige vers la page précédente avec le message

### 4️⃣ Compilation des Assets
```bash
npm install   # Installer les dépendances
npm run build # Compiler les assets avec Vite
```

## Comment Ça Marche

### Flux des Messages Flash → Toasts

```
Contrôleur
    ↓
$this->addFlash('success', 'Message');
    ↓
Session (stocké en flashes)
    ↓
Template base.html.twig
    ↓
Rendu HTML avec data-flash-type
    ↓
Flash Toast Controller (Stimulus)
    ↓
Convertit en Toast Bootstrap
    ↓
Affiche en haut à droite
```

### Flux des Erreurs d'Accès

```
Utilisateur accède à ressource protégée
    ↓
@IsGranted('ROLE_ADMIN') → Fail
    ↓
AccessDeniedException lancée
    ↓
ExceptionListener capture
    ↓
Ajoute flash message
    ↓
Redirige vers page précédente
    ↓
Flash message → Toast
```

## Types de Toasts Supportés

| Type | Badge | Icône | Couleur |
|------|-------|-------|---------|
| `success` | ✅ | check-circle | Vert |
| `error` / `danger` | ❌ | exclamation-circle | Rouge |
| `warning` | ⚠️ | exclamation-triangle | Orange |
| `info` | ℹ️ | info-circle | Bleu |

## Utilisation dans les Contrôleurs

### Exemple 1: Message de Succès
```php
$this->addFlash('success', 'L\'utilisateur a été créé avec succès.');
return $this->redirectToRoute('app_admin_utilisateurs');
```

### Exemple 2: Message d'Erreur
```php
try {
    // Traiter quelque chose
} catch (Exception $e) {
    $this->addFlash('error', 'Erreur: ' . $e->getMessage());
    return $this->redirectToRoute('app_admin_dashboard');
}
```

### Exemple 3: Accès Refusé (Automatique)
```php
#[IsGranted('ROLE_ADMIN')]
public function adminOnly(): Response
{
    // L'ExceptionListener ajoute automatiquement un toast
}
```

## Test de Fonctionnement

### URL de Test
Accédez à `/test-toast` pour voir les 4 types de toasts:

```
http://localhost:8000/test-toast
```

Devrait afficher:
- ✅ Message de succès (vert)
- ❌ Message d'erreur (rouge)
- ⚠️ Message d'attention (orange)
- ℹ️ Message d'information (bleu)

### Test d'Accès Refusé
```
http://localhost:8000/test-access-denied
```

Devrait:
1. Rejeter l'accès (pas ROLE_SUPER_ADMIN)
2. Ajouter un toast "Accès refusé"
3. Rediriger à la page précédente

### Test de Pagique 404
```
http://localhost:8000/inexistant
```

Devrait afficher la page d'erreur 404 (pas de toast pour les 404)

## Checklist de Vérification

- [ ] Assets compilés avec `npm run build`
- [ ] Contrôleurs Stimulus enregistrés dans `app.js`
- [ ] Toast container dans `base.html.twig`
- [ ] Flash messages convertis en toasts
- [ ] ExceptionListener configuré pour AccessDeniedException
- [ ] Page de test accessible: `/test-toast`
- [ ] Toasts s'affichent en haut à droite
- [ ] Messages flash disparaissent après 5 secondes
- [ ] Accès refusé redirige avec toast

## Dépannage

### Toasts ne s'affichent pas

1. **Vérifier les assets compilés**:
   ```bash
   npm run build
   php bin/console cache:clear
   ```

2. **Vérifier le navigateur (DevTools)**:
   - Console: Erreurs JavaScript?
   - Network: app-*.js chargé?
   - Elements: `#toast-container` présent?

3. **Vérifier la session**:
   ```php
   // Dans le contrôleur
   dd($this->addFlash('test', 'Message'));
   ```

### Messages Flash Vides

1. Vérifier que `addFlash()` est appelé avant la redirection
2. Vérifier que le template inclut les flashes:
   ```twig
   {% for label, messages in app.flashes %}
   ```

### ExceptionListener ne Fonctionne Pas

1. Vérifier l'autoconfiguration:
   ```bash
   php bin/console debug:config services
   ```

2. Vérifier que la classe est dans `src/Infrastructure/EventListener/`
3. Vérifier l'import `EventSubscriberInterface`

## Ressources

- [Symfony Session Flash Messages](https://symfony.com/doc/current/controller/sessions.html#flash-messages)
- [Bootstrap Toasts](https://getbootstrap.com/docs/5.3/components/toasts/)
- [Stimulus Controllers](https://stimulus.hotwired.dev/)
- [Event Listeners & Subscribers](https://symfony.com/doc/current/event_dispatcher.html)
