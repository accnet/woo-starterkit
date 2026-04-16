# StarterKit Theme Builder MVP

Preset-based WordPress and WooCommerce theme scaffold with:

- global design tokens
- layout registries
- section type registries
- `theme_section` CPT
- slot-based rendering
- basic display rule evaluation

## Structure

- `inc/Core`: bootstrap, autoloading, setup, assets
- `inc/Settings`: global settings storage and CSS variable output
- `inc/Layouts`: layout registries and active preset resolution
- `inc/Sections`: section registry, CPT repository, render pipeline
- `inc/Rules`: page context and display rule logic
- `inc/WooCommerce`: slot hook integration for product/archive pages
- `inc/Admin`: settings page and section meta boxes
- `template-parts`: preset and section templates

## Current MVP Assumptions

- This theme owns both rendering and admin/data concerns for now.
- Section content, styles, and rules are stored as JSON in post meta.
- The admin UI is schema-driven, with media pickers, repeatable rows, section duplication, and import/export tools.
- Rule evaluation supports include/exclude relations, schedule windows, device targeting, and a broader context map.
- Additional layout presets and section types are scaffolded, but they are still starter implementations rather than final production designs.

## Next Suggested Steps

1. Add deeper visual QA and cross-template testing in a live WordPress/WooCommerce install.
2. Split admin/data concerns into a companion plugin if the project is moving beyond single-theme ownership.
3. Add richer preview tooling and field-level conditional logic for complex section schemas.
4. Add automated tests for rules, import/export, and rendering contracts.
