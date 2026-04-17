# StarterKit Theme Context

## Current State

StarterKit is a custom WordPress theme focused on WooCommerce storefront behavior.

The old section-based system has been removed from the active theme implementation.

Do not assume the theme still uses:

- `theme_section` CPT
- slot renderer for section content
- section asset pipeline
- section admin/metabox UI
- `template-parts/sections`

The current architecture is service-based and centered around:

- `inc/Core` for bootstrapping and runtime services
- `inc/Settings` for theme options and CSS variable output
- `inc/Layouts` for header/footer/product/archive layout registration and resolution
- `inc/WooCommerce` for storefront hooks, product layouts, archive layouts, and cart drawer behavior
- `template-parts` for layout-specific PHP, CSS, and JS

## Boot Flow

- `functions.php` loads `inc/bootstrap.php`
- `inc/bootstrap.php` registers the autoloader and boots `StarterKit\Core\App`
- `inc/Core/App.php` wires the main services:
  - theme setup
  - settings manager
  - CSS variable output
  - layout registry and resolver
  - admin settings page
  - context/rule services
  - asset manager
  - performance manager
  - script injection manager
  - product/archive/cart WooCommerce services

## Important Areas

### Settings

- `inc/Settings/GlobalSettingsManager.php`
  - stores defaults and sanitization for theme settings
- `inc/Settings/CssVariableOutput.php`
  - prints CSS variables from saved settings
- `inc/Admin/SettingsPage.php`
  - admin UI for branding, design tokens, layouts, performance, and tools

### Core

- `inc/Core/App.php`
  - service container / bootstrap coordinator
- `inc/Core/AssetManager.php`
  - enqueues global theme assets, active layout assets, and commerce page assets
- `inc/Core/PerformanceManager.php`
  - performance toggles such as lazy loading and resource hints

### Layouts

- `inc/Layouts/LayoutRegistry.php`
  - registers headers, footers, product layouts, and archive layouts
- `inc/Layouts/LayoutResolver.php`
  - resolves the active layout from theme settings

Current registered presets include:

- headers: `header-1`, `header-2`, `header-3`
- footers: `footer-1`, `footer-2`, `footer-3`
- products: `product-layout-1`, `product-layout-2`, `product-layout-3`
- archives: `archive-layout-1`, `archive-layout-2`

### WooCommerce

- `inc/WooCommerce/HookRegistrar.php`
  - removes default WooCommerce styles
  - registers product/archive wrapper hooks
  - sets archive columns/per-page behavior
  - exposes AJAX coupon apply endpoint
- `inc/WooCommerce/ProductLayoutManager.php`
  - product structure hooks and layout wrappers
- `inc/WooCommerce/ArchiveLayoutManager.php`
  - archive wrappers
- `inc/WooCommerce/CartDrawerManager.php`
  - cart drawer behavior

## Product Layout 1

Main files:

- `template-parts/product/product-layout-1/product.php`
- `template-parts/product/product-layout-1/style.css`
- `template-parts/product/product-layout-1/script.js`

Current behavior:

- two-column product shell on desktop
- sticky summary column
- custom gallery with main stage and thumbnail rail
- desktop thumbnails behave more like a scrollable column than a fully interactive vertical slider
- mobile behavior is intentionally different from desktop
- Swiper is loaded specifically for this layout
- main image click is disabled
- stock text is hidden in this layout

Guidance:

- prefer simplifying gallery behavior instead of layering more patches
- safest direction:
  - mobile thumbs can stay optionally Swiper-based
  - desktop thumbs should remain a simple scroll column

## Archive Layout 1

Main files:

- `template-parts/archive/archive-layout-1/archive.php`
- `template-parts/archive/archive-layout-1/style.css`

Current behavior:

- shop/category/tag archives use 5 columns
- shop/category/tag archives use 25 products per page
- mobile archive is tuned to 2 products per row

## Header 1

Main files:

- `template-parts/headers/header-1/header.php`
- `template-parts/headers/header-1/style.css`
- `template-parts/headers/header-1/script.js`

Current behavior:

- mobile layout places menu on the left, logo in the center, cart on the right
- menu opens as an off-canvas left sidebar

## Template Notes

- `front-page.php` is intentionally minimal and mainly renders page content inside the theme shell
- `footer.php` resolves the active footer layout via the layout resolver

## Wootify Integration

The theme depends on `wootify-core` for some product and variant behavior.

Prefer this split of responsibilities:

- `wootify-core` handles business/data mapping where complexity grows
- the theme handles the visual product/gallery UI

## Documentation Guidance

- `README.md` should describe the current service/layout architecture
- use this file as the quick operational handoff reference
- when changing a specific layout, start in its `template-parts/...` directory
- avoid reintroducing the removed section architecture
