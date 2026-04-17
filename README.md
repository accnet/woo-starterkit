# StarterKit Theme

StarterKit is a custom WooCommerce-focused WordPress theme built around configurable layout presets and theme-level commerce UI overrides.

## Current Architecture

The active theme no longer uses the older section-based MVP described in previous documentation.

Current focus areas:

- global settings and design tokens
- layout registry and layout resolution
- WooCommerce template overrides and hook-based wrappers
- layout-specific assets in `template-parts`
- theme-owned cart, checkout, archive, and product UI behavior

## Boot Flow

1. `functions.php` loads `inc/bootstrap.php`
2. `inc/bootstrap.php` registers the autoloader and boots `StarterKit\Core\App`
3. `inc/Core/App.php` initializes the shared services used by the theme

## Directory Overview

- `inc/Core`
  - app bootstrap, theme setup, asset loading, performance controls, script injection
- `inc/Settings`
  - global settings storage and CSS variable output
- `inc/Layouts`
  - preset registry and active layout resolution
- `inc/WooCommerce`
  - product/archive wrappers, cart drawer behavior, WooCommerce hook integration
- `inc/Admin`
  - theme settings page
- `inc/Rules`
  - page context and rule evaluation helpers used by the asset/layout pipeline
- `template-parts`
  - header, footer, product, and archive layout templates with their own `style.css` and `script.js`
- `woocommerce`
  - WooCommerce template overrides
- `assets/css`, `assets/js`
  - shared theme-level CSS and JS for commerce flows such as cart and checkout

## Layout Presets

Registered layout groups live in `inc/Layouts/LayoutRegistry.php`.

Available presets:

- headers: `header-1`, `header-2`, `header-3`
- footers: `footer-1`, `footer-2`, `footer-3`
- product pages: `product-layout-1`, `product-layout-2`, `product-layout-3`
- archives: `archive-layout-1`, `archive-layout-2`

Each preset typically points to:

- a PHP template
- an `asset_base` directory under `template-parts/...`
- an optional `style.css`
- an optional `script.js`

`inc/Core/AssetManager.php` enqueues only the active layout assets for the current request.

## WooCommerce Behavior

The theme takes strong control over storefront presentation:

- default WooCommerce frontend styles are removed
- product and archive pages are wrapped with custom layout markup
- cart and checkout have dedicated assets
- product layout 1 conditionally loads Swiper for the custom gallery UI
- archive layout 1 forces 5 columns and 25 products per page on shop/category/tag archives

Primary integration files:

- `inc/WooCommerce/HookRegistrar.php`
- `inc/WooCommerce/ProductLayoutManager.php`
- `inc/WooCommerce/ArchiveLayoutManager.php`
- `inc/WooCommerce/CartDrawerManager.php`

## Product Layout 1 Notes

Main files:

- `template-parts/product/product-layout-1/product.php`
- `template-parts/product/product-layout-1/style.css`
- `template-parts/product/product-layout-1/script.js`

Current behavior:

- two-column desktop product layout
- sticky summary column
- custom gallery with main stage and thumbnail rail
- desktop thumbnails behave more like a scroll column
- mobile and desktop gallery behavior intentionally differ

If more gallery work is needed, prefer simplifying the implementation instead of adding more layered fixes.

## Wootify Dependency

The theme integrates with `wootify-core` for some product and variant behavior.

Recommended boundary:

- business/data mapping belongs in `wootify-core`
- visual rendering and interaction details belong in the theme

## Documentation

- Use [`context.md`](./context.md) as the fast operational reference for current project state.
- Treat older docs mentioning the section system as obsolete.
