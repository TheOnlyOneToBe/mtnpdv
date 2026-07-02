# Guide des Toasts (Notifications)

## Vue d'ensemble simple

Tous les messages flash Symfony (`addFlash()`) sont automatiquement affichés sous forme de toasts modernes dans le coin supérieur droit de l'application.

## Utilisation dans les contrôleurs

### Façon simplifiée (Toasts automatiques)

```php
<?php

class AdminPointVenteController extends AbstractController {
    public function create(Request $request): Response
    {
        try {
            // ... votre logique ...
            
            // Flash success - automatiquement affiché en toast vert
            $this->addFlash('success', 'Point de vente créé avec succès');
            
        } catch (\Exception $e) {
            // Flash danger - automatiquement affiché en toast rouge
            $this->addFlash('danger', 'Erreur lors de la création: '.$e->getMessage());
        }
        
        return $this->redirectToRoute('app_admin_pdv_list');
    }
}
```

## Types de messages

### Success (Succès)
```php
$this->addFlash('success', 'Opération réussie');
```
Affiche un **toast vert** avec l'icône ✅ et le titre "Succès"

### Danger (Erreur)
```php
$this->addFlash('danger', 'Une erreur est survenue');
```
Affiche un **toast rouge** avec l'icône ⚠️ et le titre "Erreur"

### Warning (Attention)
```php
$this->addFlash('warning', 'Attention: action irréversible');
```
Affiche un **toast orange** avec l'icône ⚠️ et le titre "Attention"

### Info (Information)
```php
$this->addFlash('info', 'Opération en cours...');
```
Affiche un **toast bleu** avec l'icône ℹ️ et le titre "Information"

## Utilisation directe en JavaScript

Pour afficher des toasts directement sans passer par les flash messages:

```javascript
import ToastController from '/path/to/toast-controller.js';

// Récupérer le contrôleur toast
const toastElement = document.querySelector('[data-controller~="toast"]');
const application = window.Stimulus?.Application?.current;
const toastController = application?.getControllerForElementAndIdentifier(toastElement, 'toast');

// Afficher un toast
toastController?.success('Message de succès', 'Titre');
toastController?.danger('Message d\'erreur', 'Erreur');
toastController?.warning('Message d\'attention', 'Attention');
toastController?.info('Message d\'information', 'Info');
```

## Personnalisation

### Délai d'auto-fermeture

Par défaut, les toasts se ferment après 5 secondes. Pour modifier:

```html
<!-- Dans base.html.twig -->
<div data-controller="toast" 
     data-toast-auto-close-delay-value="10000">
</div>
```

### Désactiver l'auto-fermeture

```html
<div data-controller="toast" 
     data-toast-auto-close-value="false">
</div>
```

## Architecture

### FlashToastController
- Stimulus controller qui écoute les flash messages Symfony
- Les convertit automatiquement en toasts
- Supprime les alertes originales du DOM

### ToastController
- Stimulus controller pour l'affichage des toasts
- Intégration Bootstrap Toast native
- Auto-cleanup et gestion des événements

## Exemple complet

```php
<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminPointVenteController extends AbstractController
{
    public function create(Request $request): Response
    {
        try {
            // Validation et création
            $pointVente = new PointVente(...);
            $this->repository->save($pointVente);
            
            // Toast de succès automatique
            $this->addFlash('success', 'Point de vente créé avec succès');
            return $this->redirectToRoute('app_admin_pdv_show', ['id' => $pointVente->getId()]);
            
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('warning', 'Validation échouée: '.$e->getMessage());
            
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur critique: '.$e->getMessage());
        }
        
        return $this->render('admin/pdv/form.html.twig', ['form' => $form]);
    }
    
    public function edit(Request $request): Response
    {
        try {
            // ... édition ...
            $this->addFlash('success', 'Point de vente modifié');
            return $this->redirectToRoute('app_admin_pdv_show', ['id' => $pointVente->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la modification');
        }
        
        return $this->render('admin/pdv/form.html.twig', [...]);
    }
    
    public function delete(Request $request): Response
    {
        try {
            $this->repository->remove($pointVente);
            $this->addFlash('success', 'Point de vente supprimé');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Impossible de supprimer: '.$e->getMessage());
        }
        
        return $this->redirectToRoute('app_admin_pdv_list');
    }
}
```

## Bonnes pratiques

1. **Messages courts et clairs** - Les toasts sont temporaires
2. **Utiliser le type approprié** - success/danger/warning/info
3. **Pas d'abus** - Limiter le nombre de messages à la fois
4. **Récupération d'erreurs** - Toujours capturer les exceptions
5. **Messages informatifs** - Aide l'utilisateur à comprendre l'action

## Résumé des types

| Type | Couleur | Icône | Cas d'usage |
|------|---------|-------|-----------|
| success | 🟢 Vert | ✅ | Opération réussie |
| danger | 🔴 Rouge | ⚠️ | Erreur/Échec |
| warning | 🟠 Orange | ⚠️ | Attention/Validation |
| info | 🔵 Bleu | ℹ️ | Information/Progression |

C'est tout ce que vous avez besoin de savoir! Les toasts s'affichent automatiquement. 🎉
