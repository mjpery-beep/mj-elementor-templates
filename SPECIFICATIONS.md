# Spécifications - Module Elementor Supertool

## 1. Vue d'ensemble

**Nom du module** : Elementor Supertool  
**Version** : 1.0.0  
**Type** : Plugin WordPress  
**Dépendance requise** : Elementor 3.5.0 ou supérieur  
**Auteur** : MJ Pery  
**URI** : https://www.mj-pery.be

### Description
Module de gestion des en-têtes (headers), pieds de page (footers) et blocs personnalisés avec Elementor. Équivalent simplifié et natif du UAE Header Footer Builder, permettant de créer et afficher des composants réutilisables sur l'ensemble du site.

---

## 2. Fonctionnalités principales

### 2.1 Gestion des templates
- **Création de templates** : Nouveau type de post `mjet-template`
- **Types de templates** :
  - Header (en-tête)
  - Before Footer (avant le pied de page)
  - Footer (pied de page)
  - Custom Block (bloc personnalisé)
  - Single Page (page individuelle)
  - Single Post (article individuel)
  - Single Product (produit WooCommerce)
  - Archive (archives, blog, taxonomies)
  - Products Archive (archives WooCommerce)
  - Search Results Page (résultats de recherche)
  - 404 Page

- **Édition Elementor** : Édition visuelle complète avec Elementor Builder
- **Conditions d'affichage** :
  - Site entier
  - Pages singulières
  - Archives
  - Pages spéciales (404, recherche, blog, accueil)
  - Types de posts spécifiques
  - Pages spécifiques
  - Compatibilité WooCommerce (produits et archives)

- **Rôles utilisateur** : Limitation par rôles WordPress

### 2.2 Intégration thème
- **Support automatique** des thèmes populaires :
  - Hello Elementor / Hello Biz
  - Astra
  - GeneratePress
  - OceanWP
  - Kadence
  - Neve
  - Blocksy

- **Injection automatique** des headers/footers dans le front-end

### 2.3 Migration UAE
- **Import des templates UAE** vers le nouveau système
- **Conversion des métadonnées** UAE → MJET
- **Préservation des conditions** d'affichage
- **Régénération des CSS** Elementor

### 2.4 Widgets Elementor
- **Widget Menu Navigation** :
  - Sélection dynamique de menus WordPress
  - Disposition horizontale/verticale
  - Menu mobile avec toggle (hamburger)
  - Indicateurs de survol (soulignement, fond, texte)
  - Sous-menus avec animations
  - Pleinement accessible (ARIA, clavier)

---

## 3. Architecture du code

### 3.1 Structure des répertoires
```
elementor-supertool/
├── elementor-supertool.php       # Fichier principal
├── includes/
│   ├── class-mjet-admin.php         # Gestion admin et post type
│   ├── class-mjet-target-rules.php  # Règles de ciblage
│   ├── class-mjet-widgets-loader.php# Chargement des widgets
│   ├── class-mjet-uae-migration.php # Migration UAE
│   ├── class-mjet-theme-manager.php # Gestion des emplacements front
│   ├── mjet-functions.php           # Fonctions utilitaires
│   ├── themes/
│   │   ├── class-mjet-theme-compat.php
│   │   ├── class-mjet-default-compat.php
│   │   └── templates/
│   │       └── ...
│   └── widgets/
│       └── class-mjet-nav-menu.php  # Widget Menu Navigation
├── templates/
│   ├── canvas.php                   # Template Elementor Canvas
│   └── theme-builder.php            # Template front pour archives/search/404
├── assets/
│   ├── css/
│   │   ├── mjet-frontend.css
│   │   └── mjet-nav-menu.css
│   └── js/
│       └── mjet-nav-menu.js
├── languages/
└── README.md
```

### 3.2 Classe principale : `Elementor_SuperTool` (alias `MJ_Elementor_Templates` pour compatibilité)
**Fichier** : `elementor-supertool.php`

**Responsabilités** :
- Initialisation du plugin
- Inclusion des dépendances
- Enregistrement des hooks
- Gestion du front-end
- Rendu des templates

**Méthodes clés** :
- `instance()` - Instance unique (Singleton)
- `get_header_content()` - Contenu du header
- `get_footer_content()` - Contenu du footer
- `get_before_footer_content()` - Contenu avant footer
- `get_settings($setting)` - Récupère les paramètres
- `get_template_id($type)` - ID du template par type

### 3.3 Classe `MJET_Admin`
**Fichier** : `includes/class-mjet-admin.php`

**Responsabilités** :
- Enregistrement du post type `mjet-template`
- Métaboxes de configuration
- Interface de gestion des templates
- Règles d'affichage (conditions)
- Page "Gestionnaire de thème" (vue tableau des emplacements)
- Filtre admin par type (`mjet_type_filter`)

**Post type arguments** :
```php
[
    'public'              => true,
    'publicly_queryable'  => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'query_var'          => true,
    'rewrite'            => ['slug' => 'mjet-template'],
    'capability_type'    => 'post',
    'has_archive'        => false,
    'supports'           => ['title', 'thumbnail', 'elementor'],
]
```

### 3.4 Classe `MJET_Target_Rules`
**Fichier** : `includes/class-mjet-target-rules.php`

**Responsabilités** :
- Gestion des règles de ciblage
- Vérification des conditions d'affichage
- Cache des templates matching
- Vérification des rôles utilisateur

**Options de conditions** :
```php
[
    'basic'         => ['global', 'singulars', 'archives'],
    'special-pages' => ['404', 'search', 'blog', 'front'],
    'post-types'    => ['post|all', 'post|archive', 'page|all'],
    'custom-types'  => [...],
]
```

### 3.5 Classe `MJET_Widgets_Loader`
**Fichier** : `includes/class-mjet-widgets-loader.php`

**Responsabilités** :
- Enregistrement des widgets Elementor
- Chargement des scripts/styles
- Création de la catégorie "Supertool Templates"

### 3.6 Classe `MJET_Theme_Manager`
**Fichier** : `includes/class-mjet-theme-manager.php`

**Responsabilités** :
- Résolution des templates par contexte (singles, archives, recherche, 404, WooCommerce)
- Enqueue des CSS Elementor nécessaires via `Elementor\Core\Files\CSS\Post`
- Substitution de `the_content` pour les singles et de `template_include` pour les archives
- Exposition des hooks `mjet/theme_manager/before_content` et `mjet/theme_manager/after_content`

**Flux principal** :
1. `wp` détecte le contexte courant et calcule l'ID de template via `Elementor_SuperTool::get_template_id()`
2. Enqueue des CSS immédiatement pour garantir le rendu dans `wp_head`
3. `the_content` injecte le template pour Single Page/Post/Product
4. `template_include` redirige vers `templates/theme-builder.php` pour archives/recherche/404

### 3.7 Widget `MJET_Nav_Menu`
**Fichier** : `includes/widgets/class-mjet-nav-menu.php`

**Héritage** : `Elementor\Widget_Base`

**Contrôles** :
- Menu : sélection du menu
- Layout : horizontal/vertical
- Alignment : alignement des items
- Pointer : style d'indicateur
- Mobile breakpoint : point de rupture
- Toggle icons : icônes mobile

**Styles** :
- Typography du menu
- Couleurs (normal/hover/actif)
- Espacement et padding
- Sous-menus
- Toggle mobile

---

## 4. Flux de données

### 4.1 Affichage d'un header/footer
```
1. Front-end charge
2. Hooks WordPress (hello_elementor_header, wp_footer, etc.)
3. Appel à mjet_render_header/footer()
4. get_settings('type_header') récupère l'ID du template
5. get_template_id() vérifie les conditions d'affichage
6. Elementor génère le contenu HTML
7. Injection dans le DOM
```

### 4.2 Migration UAE vers MJET
```
1. Admin accède à "Importer depuis UAE"
2. Sélection des templates à migrer
3. Pour chaque template UAE :
   - Copie du post
   - Copie des métadonnées (_elementor_data, etc.)
   - Conversion métadonnées (ehf_* → mjet_*)
   - Régénération CSS Elementor
4. Notification de succès
5. Templates MJET apparaissent dans la liste
```

### 4.3 Configuration automatique
```
1. Admin accède à setup-templates.php
2. Vérification des templates MJET existants
3. Configuration des conditions d'affichage
4. "Site entier" défini pour type_header et type_footer
5. Templates actifs détectés et affichés
```

---

## 5. Métadonnées (Post Meta)

### 5.1 Métadonnées MJET
```php
'mjet_template_type'              // Type: type_header, type_footer, type_before_footer
'mjet_target_include_locations'   // Conditions d'inclusion
'mjet_target_exclude_locations'   // Conditions d'exclusion
'mjet_target_user_roles'          // Rôles autorisés
'mjet_display_on_canvas'          // Affichage sur canvas
```

### 5.2 Métadonnées Elementor (copiées)
```php
'_elementor_data'          // Structure du builder
'_elementor_edit_mode'     // Mode édition (builder)
'_elementor_page_settings' // Paramètres de page
'_elementor_version'       // Version Elementor utilisée
'_wp_page_template'        // Template: elementor_canvas
```

---

## 6. Hooks et filtres

### 6.1 Hooks d'action
```php
// Lors de l'activation
'mjet_activation'

// Chargement des textes
'mjet_load_textdomain'

// Enregistrement des widgets
'elementor/widgets/register'
'elementor/elements/categories_registered'

// Front-end
'wp_enqueue_scripts'
'template_redirect'
'wp_body_open'
'wp_footer'
```

### 6.2 Filtres
```php
'mjet_header_enabled'              // Activer/désactiver header
'mjet_footer_enabled'              // Activer/désactiver footer
'mjet_before_footer_enabled'       // Activer/désactiver before footer
'mjet_get_header_id'               // ID du header
'mjet_get_footer_id'               // ID du footer
'mjet_get_before_footer_id'        // ID du before footer
'mjet_enable_render_header'        // Activer rendu header
'mjet_enable_render_footer'        // Activer rendu footer
'mjet_enable_render_before_footer' // Activer rendu before footer
```

---

## 7. Constantes

```php
MJET_VERSION                      // Version du plugin (1.0.0)
MJET_FILE                        // Chemin complet du fichier plugin
MJET_DIR                         // Répertoire du plugin
MJET_URL                         // URL du plugin
MJET_PATH                        // Chemin relatif du plugin
ELEMENTOR_VERSION                // Version d'Elementor
```

---

## 8. Compatibilité

### 8.1 WordPress
- Minimum : 5.8
- Testé jusqu'à : 6.9

### 8.2 PHP
- Minimum : 7.4
- Recommandé : 8.0+

### 8.3 Elementor
- Minimum : 3.5.0
- Testé jusqu'à : 3.33.6

### 8.4 Thèmes
- Hello Elementor
- Hello Biz
- Astra
- GeneratePress
- OceanWP
- Kadence
- Neve
- Blocksy

---

## 9. Sécurité

### 9.1 Validation
- `sanitize_text_field()` pour les inputs texte
- `intval()` pour les IDs
- `wp_kses_post()` pour le contenu HTML
- `esc_url()`, `esc_html()`, `esc_attr()` pour l'affichage

### 9.2 Autorisation
- Vérification des capacités WordPress
- Nonces pour les formulaires
- `current_user_can()` pour les actions

### 9.3 Nettoyage
- `wp_unslash()` pour les données POST
- Validation des enums (type de template, rôles)

---

## 10. Performance

### 10.1 Caching
- Cache des templates par condition
- `self::$templates_cache` dans `MJET_Target_Rules`
- Invalidation du cache lors de mises à jour

### 10.2 CSS Elementor
- Génération automatique via `CSS\Post`
- Régénération lors de migrations
- Support du cache busting via `filemtime()`

### 10.3 Script/Style
- Enregistrement des assets
- Lazy loading des scripts
- Minimisation via Elementor

---

## 11. Fonctionnalités avancées

### 11.1 Scripts de maintenance
- `debug.php` - Diagnostic des templates
- `debug-migration.php` - Vérification migration UAE
- `regenerate-css.php` - Régénération CSS
- `fix-templates.php` - Correction des métadonnées
- `setup-templates.php` - Configuration automatique

### 11.2 Migration UAE
- Détection automatique des templates UAE
- Import sélectif ou complet
- Conversion des conditions
- Réinitialisation possible du statut

---

## 12. Utilisation

### 12.1 Créer un header
1. Admin → MJ Templates → Nouveau
2. Titre : "Header Principal"
3. Type : Header
4. Éditer avec Elementor
5. Ajouter le widget Menu Navigation
6. Conditions d'affichage : Site entier
7. Publier

### 12.2 Créer un footer
1. Admin → MJ Templates → Nouveau
2. Titre : "Footer Principal"
3. Type : Footer
4. Éditer avec Elementor (logo, liens, texte)
5. Conditions d'affichage : Site entier
6. Publier

### 12.3 Utiliser le widget Menu
1. Ouvrir Elementor header
2. Ajouter widget → Supertool Templates → Menu Navigation
3. Sélectionner le menu WordPress
4. Configurer le layout (horizontal/vertical)
5. Personnaliser les styles
6. Configurer le mobile (breakpoint, icônes)
7. Enregistrer

---

## 13. API publique

### 13.1 Fonctions accessibles
```php
// Vérifier si header/footer actif
mjet_header_enabled()
mjet_footer_enabled()
mjet_before_footer_enabled()

// Obtenir l'ID du template
mjet_get_header_id()
mjet_get_footer_id()
mjet_get_before_footer_id()

// Afficher le template
mjet_render_header()
mjet_render_footer()
mjet_render_before_footer()

// Récupérer le contenu
mjet_get_template_content($template_id)

// Vérifier le mode canvas
mjet_is_canvas_template()
mjet_is_canvas_enabled($template_id)
```

### 13.2 Shortcodes
```php
[mjet_template id="123"]  // Afficher un template par ID
[mjet_template type="footer"]  // Afficher un template par type
```

---

## 14. Limitations et restrictions

- Un seul header/footer/before-footer actif à la fois
- Les templates doivent être publiés pour s'afficher
- Elementor doit être activé
- Les conditions d'affichage ne supportent pas les rôles multiples (ET logique)
- Les sous-menus ne s'affichent qu'au survol (desktop)

---

## 15. Feuille de route (futures versions)

- [ ] Support des conditions multiples (OR/AND)
- [ ] Builder custom sans Elementor
- [ ] Templates globales (sections réutilisables)
- [ ] Variantions de templates (dark/light mode)
- [ ] Export/Import de templates
- [ ] Présets de styles prédéfinis
- [ ] Support des blocs personnalisés WordPress (blocks)
- [ ] Widget Slider/Carousel
- [ ] Widget Grille de contenu
- [ ] Conditions géographiques
- [ ] A/B Testing

---

## 16. Support et maintenance

### 16.1 Commandes de maintenance
```bash
# Diagnostic
https://www.mj-pery.be/wp-content/plugins/elementor-supertool/debug.php?key=mjet_debug

# Migration
https://www.mj-pery.be/wp-content/plugins/elementor-supertool/debug-migration.php?key=mjet_debug_mig

# Régénération CSS
https://www.mj-pery.be/wp-content/plugins/elementor-supertool/regenerate-css.php?key=mjet_regen

# Configuration automatique
https://www.mj-pery.be/wp-content/plugins/elementor-supertool/setup-templates.php?key=mjet_setup

# Correction des templates
https://www.mj-pery.be/wp-content/plugins/elementor-supertool/fix-templates.php?key=mjet_fix_2024
```

### 16.2 Fichiers de log
- Aucun fichier de log dédié (utiliser WordPress debug.log)

---

## 17. Exemples de code

### 17.1 Utiliser l'API
```php
// Vérifier si un header est actif
if (mjet_header_enabled()) {
    $header_id = mjet_get_header_id();
    echo mjet_get_template_content($header_id);
}

// Afficher conditionnellement
add_filter('mjet_header_enabled', function() {
    return !is_front_page(); // Pas de header sur la page d'accueil
});

// Personnaliser le rendu
add_filter('mjet_enable_render_header', function() {
    return !is_user_logged_in(); // Pas de header pour les connectés
});
```

### 17.2 Intégration thème
```php
// Dans le template du thème
if (mjet_header_enabled()) {
    mjet_render_header();
} else {
    get_template_part('header'); // Header par défaut
}
```

---

**Date de création** : 20 décembre 2025  
**Statut** : Spécifications finalisées v1.0  
**Auteur** : MJ Pery Development Team
