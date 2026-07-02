# Guide des Toasts (Notifications)

## Vue d'ensemble

Le système de toasts fournit un moyen unifié d'afficher des notifications non-intrusive à l'utilisateur. Les toasts sont automatiquement affichés dans le coin supérieur droit et peuvent être fermés manuellement ou automatiquement après un délai.

## Architecture

- **ToastController** (`src/Controller/ToastController.php`) - Contrôleur Symfony pour les routes de toast
- **toast-controller.js** - Stimulus controller pour gérer l'affichage des toasts
- **toast-helper.js** - Utilitaires JavaScript pour faciliter l'utilisation
- **templates/toast/** - Templates Turbo Stream pour chaque type de toast

## Types de toasts

### 1. Success (Succès)
```javascript
// Via Stimulus
import ToastController from 'path/to/toast-controller.js';
const controller = new ToastController(element);
controller.success('Profil mis à jour avec succès', 'Succès');

// Via Helper
import { toastHelper } from 'path/to/toast-helper.js';
toastHelper.success('Opération réussie');

// Via Turbo Stream
POST /toast/success
  message=Opération réussie
  title=Succès
```

### 2. Danger/Error (Erreur)
```javascript
// Via Stimulus
controller.danger('Une erreur est survenue', 'Erreur');

// Via Helper
toastHelper.danger('Impossible de sauvegarder');

// Via Turbo Stream
POST /toast/danger
  message=Impossible de sauvegarder
  title=Erreur
```

### 3. Warning (Attention)
```javascript
// Via Stimulus
controller.warning('Attention: Cette action est irréversible', 'Attention');

// Via Helper
toastHelper.warning('Cette action ne peut pas être annulée');

// Via Turbo Stream
POST /toast/warning
  message=Action irréversible
  title=Attention
```

### 4. Info (Information)
```javascript
// Via Stimulus
controller.info('Chargement en cours...', 'Info');

// Via Helper
toastHelper.info('Opération en cours');

// Via Turbo Stream
POST /toast/info
  message=Opération en cours
  title=Info
```

### 5. Confirmation
```javascript
// Via Stimulus
controller.confirmation(
  'Êtes-vous sûr?',
  () => { console.log('Confirmed'); },
  () => { console.log('Cancelled'); }
);

// Via Helper
toastHelper.confirmation(
  'Êtes-vous sûr de vouloir supprimer?',
  () => { window.location.reload(); },
  () => { console.log('Cancelled'); }
);

// Via Turbo Stream
POST /toast/confirmation
  message=Êtes-vous sûr?
  confirmUrl=/path/to/action
  confirmMethod=POST
```

## Utilisation dans les contrôleurs Symfony

### Via Flash Messages (existant)
```php
$this->addFlash('success', 'Opération réussie');
$this->addFlash('danger', 'Une erreur est survenue');
```

### Via Turbo Stream Response
```php
use Symfony\Component\HttpFoundation\Response;

// Dans un contrôleur
public function someAction(): Response {
    try {
        // Votre logique...
        
        // Retourner une réponse Turbo Stream avec toast
        return $this->render('toast/success.stream.twig', [
            'message' => 'Opération réussie',
            'title' => 'Succès'
        ]);
    } catch (\Exception $e) {
        return $this->render('toast/danger.stream.twig', [
            'message' => $e->getMessage(),
            'title' => 'Erreur'
        ]);
    }
}
```

## Utilisation dans les Stimulus Controllers

```javascript
import { Controller } from '@hotwired/stimulus';
import { toastHelper } from '../../path/to/toast-helper.js';

export default class extends Controller {
    handleSubmit() {
        try {
            // Votre logique...
            toastHelper.success('Données sauvegardées');
        } catch (error) {
            toastHelper.danger(error.message);
        }
    }

    handleDelete() {
        toastHelper.confirmation(
            'Êtes-vous sûr de vouloir supprimer cet élément?',
            () => {
                // Action de confirmation
                fetch('/api/delete/123', { method: 'DELETE' })
                    .then(() => toastHelper.success('Élément supprimé'))
                    .catch(err => toastHelper.danger(err.message));
            },
            () => {
                // Action d'annulation
                toastHelper.info('Suppression annulée');
            }
        );
    }
}
```

## Utilisation dans les formulaires HTML

```html
<!-- Formulaire avec handler Stimulus -->
<form data-action="submit->my-form#handleSubmit">
    <input type="text" name="name">
    <button type="submit">Envoyer</button>
</form>
```

```javascript
// Stimulus Controller
handleSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch(event.target.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastHelper.success(data.message);
        } else {
            toastHelper.danger(data.message);
        }
    })
    .catch(error => toastHelper.danger(error.message));
}
```

## Personnalisation

### Délai d'auto-fermeture

Par défaut, les toasts se ferment automatiquement après 5 secondes. Pour modifier ce comportement:

```html
<!-- Dans le HTML -->
<div data-controller="toast"
     data-toast-auto-close-value="false"
     data-toast-auto-close-delay-value="3000">
</div>
```

### Couleurs et Icônes

Les couleurs et icônes sont définies automatiquement selon le type:
- Success: Vert + Checkmark
- Danger: Rouge + Exclamation Circle
- Warning: Orange + Exclamation Triangle
- Info: Bleu + Info Circle
- Confirmation: Orange + Question Circle

## Bonnes pratiques

1. **Messages courts et clairs** - Gardez les messages concis et informatifs
2. **Titres appropriés** - Utilisez des titres qui résument l'action
3. **Actions rapides** - Les toasts ne doivent durer que quelques secondes
4. **Confirmation pour les actions destructives** - Utilisez les toasts de confirmation
5. **Pas d'abus** - Limitez le nombre de toasts à la fois

## Exemple complet

```javascript
// pdv-form-controller.js
import { Controller } from '@hotwired/stimulus';
import { toastHelper } from '../../path/to/toast-helper.js';

export default class extends Controller {
    static targets = ['form', 'submitBtn'];

    async handleFormSubmit(event) {
        event.preventDefault();

        try {
            this.submitBtnTarget.disabled = true;
            toastHelper.info('Sauvegarde en cours...');

            const response = await fetch(this.formTarget.action, {
                method: this.formTarget.method,
                body: new FormData(this.formTarget),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            toastHelper.success('Formulaire sauvegardé avec succès');
            
            // Optional: Redirect or update page
            setTimeout(() => {
                window.location.href = '/admin/pdv';
            }, 1500);

        } catch (error) {
            console.error('Error:', error);
            toastHelper.danger(error.message || 'Une erreur est survenue');
        } finally {
            this.submitBtnTarget.disabled = false;
        }
    }
}
```

## Dépannage

### Toast ne s'affiche pas
1. Vérifiez que le conteneur `toast-container` existe dans le DOM
2. Vérifiez que le Stimulus controller est bien enregistré
3. Vérifiez que Bootstrap.js est chargé

### Toast n'a pas la bonne couleur
- Les couleurs sont définies par le type (success, danger, warning, info)
- Vérifiez que les classes CSS Bootstrap sont disponibles

### Toast disparaît trop vite
- Utilisez `data-toast-auto-close-delay-value="10000"` pour augmenter le délai à 10 secondes

## API Complète

### ToastController (Stimulus)

```javascript
// Methods
success(message, title)
danger(message, title)
warning(message, title)
info(message, title)
confirmation(message, onConfirm, onCancel)

// Values (Data Attributes)
data-toast-auto-close-value         // Boolean (default: true)
data-toast-auto-close-delay-value   // Number in ms (default: 5000)
```

### toastHelper (Helper Functions)

```javascript
// Direct methods
toastHelper.success(message, title)
toastHelper.danger(message, title)
toastHelper.warning(message, title)
toastHelper.info(message, title)
toastHelper.confirmation(message, onConfirm, onCancel)

// Turbo Stream methods
toastHelper.successStream(message, title)
toastHelper.dangerStream(message, title)
toastHelper.warningStream(message, title)
toastHelper.infoStream(message, title)
```

## Routes disponibles

```
POST /toast/success       - Afficher un toast succès
POST /toast/danger        - Afficher un toast erreur
POST /toast/warning       - Afficher un toast attention
POST /toast/info          - Afficher un toast info
POST /toast/confirmation  - Afficher un toast confirmation
```
