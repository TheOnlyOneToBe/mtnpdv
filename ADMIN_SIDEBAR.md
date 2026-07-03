# 📋 Admin Sidebar - Documentation Complète

## 🎯 Vue d'ensemble

La sidebar admin est un composant Twig qui fournit une navigation structurée et intuitive pour tous les administrateurs du système. Elle remplace les "actions rapides" et offre une meilleure accessibilité et expérience utilisateur.

## 🏗️ Architecture

### Fichiers Impliqués

```
templates/
├── components/
│   └── admin-sidebar.html.twig    # Composant sidebar
└── base.html.twig                  # Inclusion sidebar
```

### Structure HTML

```html
<aside class="admin-sidebar">
  <div class="sidebar-brand">...</div>
  <nav class="sidebar-nav">
    <div class="nav-section">...</div>
    <div class="nav-submenu">...</div>
  </nav>
  <div class="sidebar-footer">...</div>
</aside>
```

## 🎨 Design & Styling

### Couleurs Appliquées

| Élément | Couleur | Code |
|---------|---------|------|
| Background | Blanc/Gris clair | `#ffffff` → `#fafafa` |
| Border | Jaune clair | `#ffe082` |
| Icon Primary | Or jaune | `#FFD700` |
| Icon Secondary | Or foncé | `#F39C12` |
| Hover | Jaune pâle | `#fffacd` |
| Active | Or jaune | `#FFD700` |

### Layout

```
┌─────────────────────────────────────┐
│          NAVBAR (60px)              │
├─────────┬─────────────────────────┐
│         │                         │
│ SIDEBAR │   MAIN CONTENT          │
│ (280px) │   (calc(100% - 280px))  │
│         │                         │
│         │                         │
└─────────┴─────────────────────────┘
```

**Responsive:**
- Desktop (> 1024px): Sidebar 280px
- Tablet (768px - 1024px): Sidebar 240px
- Mobile (< 768px): Sidebar collapsible, full width

## 📖 Sections de Navigation

### 1️⃣ Dashboard
- **Route**: `app_admin_dashboard`
- **Accès**: Rapide vers l'aperçu général

### 2️⃣ Points de Vente
- **Lister**: `app_admin_pdv_list`
- **Ajouter**: `app_admin_pdv_new`
- **Sous-menu**: Collapsible

### 3️⃣ Utilisateurs
- **Lister**: `app_admin_utilisateur_list`
- **Ajouter**: `app_admin_utilisateur_new`
- **Sous-menu**: Collapsible

### 4️⃣ Rôles & Permissions
- **Lister**: `app_admin_role_list`
- **Sous-menu**: Collapsible

### 5️⃣ Validations
- **En attente**: `app_admin_validations`
- **Direct link** (pas de sous-menu)

### 6️⃣ Rapports
- **Tableau de bord**: `app_admin_reports`
- **Par PDV**: `app_admin_reports_pdv`
- **Transactions**: `app_admin_reports_transactions`
- **Utilisateurs**: `app_admin_reports_users`
- **Sous-menu**: Collapsible

### 7️⃣ Paramètres
- **Mon profil**: `app_profil_show`
- **Mot de passe**: `app_profil_change_password`
- **Déconnexion**: `app_logout`
- **Sous-menu**: Collapsible

### 8️⃣ Profil Utilisateur (Footer)
- Avatar (photo profil ou icône)
- Nom et Prénom
- Rôle (Administrateur)

## 🎯 Fonctionnalités

### ✨ Caractéristiques Clés

| Fonctionnalité | Description |
|---|---|
| **Fixe** | Reste visible lors du scroll |
| **Responsive** | S'adapte à tous les écrans |
| **Collapsible** | Sur mobile (< 768px) |
| **Actif** | Surligne la page actuelle |
| **Submenu** | Expansion/Réduction fluide |
| **Accessible** | Navigable au clavier |
| **Dark Mode** | Compatible avec le thème sombre |

### 🔄 Animations

```css
/* Transitions */
transition: transform 0.3s ease;
transition: background 0.2s ease;
transition: max-height 0.3s ease;

/* Hover Effects */
.nav-item:hover {
    background: #fffacd;
}

/* Chevron Rotation */
transform: rotate(180deg) /* expanded */
transform: rotate(0deg)   /* collapsed */
```

## 💻 Utilisation

### Inclusion dans base.html.twig

```twig
{% if app.user and app.user.aLeRole('ADMIN') %}
    {% include 'components/admin-sidebar.html.twig' %}
{% endif %}
```

**Condition**: Sidebar affichée uniquement pour les utilisateurs avec rôle ADMIN

### Styles pour Main Content

```css
main {
    margin-left: 280px;  /* Desktop */
    transition: margin-left 0.3s ease;
}

@media (max-width: 768px) {
    main {
        margin-left: 0;  /* Mobile */
    }
}
```

## 🔧 Personnalisation

### Ajouter une Nouvelle Section

```twig
<div class="nav-section">
    <button class="nav-section-title" data-toggle="new-menu">
        <i class="fas fa-icon"></i>
        <span>Label</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div class="nav-submenu" id="new-menu">
        <a href="{{ path('route_name') }}" class="nav-item">
            <i class="fas fa-icon"></i>
            <span>Item</span>
        </a>
    </div>
</div>
```

### Modifier les Couleurs

```css
.sidebar-brand i {
    color: #FFD700;  /* Changer ici */
}

.nav-item.active {
    background: #FFD700;  /* Changer ici */
    border-left: 4px solid #F39C12;  /* Changer ici */
}
```

### Ajuster la Largeur

```css
.admin-sidebar {
    width: 280px;  /* Default */
}

/* Tablet */
@media (max-width: 1024px) {
    .admin-sidebar {
        width: 240px;  /* Plus étroit */
    }
}
```

## 🚀 JavaScript Interactions

### Toggle Submenu

```javascript
// Déclenché au clic sur .nav-section-title
const menuId = button.getAttribute('data-toggle');
const menu = document.getElementById(menuId);
menu.classList.toggle('collapsed');
```

### Toggle Sidebar Mobile

```javascript
// Déclenché au clic sur .sidebar-toggle
sidebar.classList.toggle('collapsed');

// Fermeture auto lors du clic sur nav-item
sidebar.classList.add('collapsed');
```

## 📱 Responsive Design

### Breakpoints

| Taille | Comportement |
|--------|-------------|
| > 1024px | Sidebar fixe 280px |
| 768px - 1024px | Sidebar fixe 240px |
| < 768px | Sidebar collapsible, pleine largeur |

### Mobile Sidebar

- **État fermé**: Translate -100% (hors écran)
- **État ouvert**: Translate 0% (visible)
- **Bouton toggle**: Visible en haut à droite
- **Auto-close**: Au clic sur un lien

## 🐛 Dépannage

### Sidebar ne s'affiche pas

**Solution:**
```twig
{# Vérifier la condition dans base.html.twig #}
{% if app.user and app.user.aLeRole('ADMIN') %}
    {% include 'components/admin-sidebar.html.twig' %}
{% endif %}
```

### Chevrons mal orientés

**Solution:**
```css
/* Vérifier le style du chevron */
.nav-section-title i:last-child {
    transition: transform 0.3s ease;
}

/* Au clic */
icon.style.transform = 'rotate(180deg)'; /* expanded */
```

### Main content décalé sur mobile

**Solution:**
```css
@media (max-width: 768px) {
    main {
        margin-left: 0;  /* Ajouter cette règle */
    }
}
```

## 📊 Accessibilité

### Aria Labels

```twig
<button class="nav-section-title" 
        data-toggle="menu-id"
        aria-expanded="false"
        aria-label="Afficher/Masquer menu">
    ...
</button>
```

### Navigation Clavier

- **Tab**: Navigue entre les éléments
- **Enter/Space**: Expande/Réduit les sous-menus
- **Escape**: Ferme les sous-menus (en cours de dev)

## 🎯 Active Page Highlighting

La classe `active` est appliquée automatiquement via:

```twig
{% if app.request.attributes.get('_route') == 'route_name' %}
    active
{% endif %}
```

**Exemple:**
```twig
<a href="{{ path('app_admin_pdv_list') }}" 
   class="nav-item {% if app.request.attributes.get('_route') == 'app_admin_pdv_list' %}active{% endif %}">
```

## 📈 Performance

### Optimisations

- ✅ CSS Inline dans le composant (pas de requête supplémentaire)
- ✅ JavaScript vanilla (pas de dépendance)
- ✅ Transitions GPU (transform, opacity)
- ✅ Lazy loading disponible via data-controller

### Bundle Size Impact

- CSS: ~2 KB
- JS: ~1 KB
- Total: ~3 KB (gzippé: ~1 KB)

## 🔐 Sécurité

### Contrôles d'Accès

- ✅ Vérification du rôle ADMIN dans base.html.twig
- ✅ Vérification du rôle @IsGranted dans contrôleurs
- ✅ Echappement automatique des noms d'utilisateurs (Twig)

```twig
{# Sûr par défaut #}
<div class="user-name">{{ app.user.prenomUt }}</div>
```

## 🧪 Test Manual

### Checklist de Test

- [ ] Sidebar visible pour admin
- [ ] Sidebar cachée pour non-admin
- [ ] Tous les liens fonctionnent
- [ ] Classe active sur page actuelle
- [ ] Sous-menus se développent/réduisent
- [ ] Sidebar collapsible sur mobile
- [ ] Responsive (testez à 768px)
- [ ] Avatar affiche ou icon par défaut
- [ ] Chevrons pivotent correctement
- [ ] Hover effects fonctionnent
- [ ] Thème jaune appliqué

## 📚 Ressources

- [Bootstrap Sidebar Components](https://getbootstrap.com/docs/5.3/)
- [CSS Grid & Flexbox](https://css-tricks.com/)
- [Twig Documentation](https://twig.symfony.com/)
- [Symfony Routing](https://symfony.com/doc/current/routing.html)

## 📝 Changelog

### v1.0.0 (2026-07-03)
- ✨ Création de la sidebar admin
- ✨ Navigation structurée par section
- ✨ Responsive design mobile
- ✨ Thème jaune doré intégré
- ✨ Profil utilisateur en footer
- ✨ Animations fluides

## 👨‍💻 Développement Futur

### Features à Ajouter

- [ ] Notification badge (ex: validations en attente)
- [ ] Raccourcis clavier (ex: `?` pour aide)
- [ ] Favorites/Bookmarks dans sidebar
- [ ] Historique des pages visitées
- [ ] Search dans la sidebar
- [ ] Thème sidebar (couleur alternative)
- [ ] Collapsible automatique après inactivité

### Améliorations

- [ ] Persister l'état du sidebar (localStorage)
- [ ] Animation d'entrée du sidebar
- [ ] Support RTL (right-to-left)
- [ ] Analytics (tracking clics)
