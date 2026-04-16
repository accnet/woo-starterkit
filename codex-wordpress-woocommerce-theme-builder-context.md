# PROJECT CONTEXT FOR CODEX

## 1. Project Goal

Build a custom WordPress theme + WooCommerce integration with a configurable layout system.

This is NOT a simple static WooCommerce theme.
This project must function like a preset-based theme builder with controlled extensibility.

The system must allow admin users to:

1. Deeply configure:
   - Master layout
   - Header layout
   - Footer layout
   - Product page layout
   - Global branding (logo, typography, colors, spacing, button styles)

2. Add reusable content sections into predefined layout positions (slots), such as:
   - Hero
   - Call To Action
   - Feature Section
   - Benefit Section
   - Testimonials
   - FAQ
   - Product Grid
   - Promo Banner
   - Image + Text
   - Newsletter

3. Configure each section instance with:
   - Content
   - Style overrides
   - Slot / position
   - Display rules
   - Priority / sort order

4. Control where each section appears:
   - Homepage
   - All products
   - Single product
   - Product categories
   - Specific product category
   - Specific page
   - Shop archive
   - Entire site
   - Include / exclude conditions

5. Combine:
   - Selected layout presets
   - Global style tokens
   - Typography
   - Color scheme
   - Assigned sections
   - Display rules

   to generate the final frontend UI.

The architecture must be maintainable, scalable, and production-oriented.

---

## 2. High-Level Product Vision

The final system should behave like this:

- Admin selects a predefined header layout.
- Admin selects a predefined footer layout.
- Admin selects a predefined product layout.
- Admin sets global style options such as logo, fonts, colors, button style, spacing.
- Admin creates reusable section instances.
- Admin assigns each section instance to a predefined slot in a layout.
- Admin chooses display targets and conditions for each section.
- Frontend renderer resolves:
  global style + selected layout preset + matching sections + display rules
  into the final rendered page.

This must feel like a structured theme builder, not a freeform page builder.

Important:
- Layout presets are built in code and controlled.
- Section injection is flexible but only within predefined slot positions.
- Admin must not be allowed to arbitrarily break core layout HTML.

---

## 3. Technical Scope

Build:

1. A custom WordPress theme:
   - WooCommerce compatible
   - Modular architecture
   - Template-part-based rendering
   - Slot-based section rendering
   - CSS variable driven design tokens

2. A companion plugin (preferred):
   - Manage global settings UI
   - Register and manage section instances
   - Handle display rules and assignments
   - Expose data to theme render layer

If needed for initial implementation, the first version can keep both in one codebase, but architecture must clearly separate:
- presentation layer
- configuration/data layer
- rule engine
- section rendering layer

---

## 4. Non-Goals

Do NOT build a full drag-and-drop Elementor-like visual builder.
Do NOT allow unrestricted arbitrary HTML layout editing by admin.
Do NOT create a completely freeform layout editor.

The system is preset-driven and slot-driven.

---

## 5. Core Concepts

### 5.1 Global Theme Settings
A global configuration layer that includes:
- logo
- favicon
- heading font
- body font
- color palette
- button style preset
- container width
- border radius scale
- shadow style
- spacing scale
- default header layout
- default footer layout
- default master layout
- default product layout
- default archive layout

These settings should be exposed as design tokens using CSS variables.

Example:
- --site-font-heading
- --site-font-body
- --color-primary
- --color-accent
- --container-width
- --section-gap
- --radius-md

### 5.2 Layout Presets
Layouts are predefined and versioned in code.

Types:
- Master layout presets
- Header layout presets
- Footer layout presets
- Product layout presets
- Archive/shop/category layout presets

Each layout preset should define:
- id
- label
- template file path
- supported slots
- optional options schema
- preview thumbnail (optional later)

### 5.3 Section Types
A section type is a reusable component definition.

Examples:
- hero
- cta
- features
- benefits
- testimonials
- faq
- category_grid
- product_grid
- image_text
- newsletter

Each section type should define:
- id
- label
- template path
- field schema
- style schema
- supported slots (optional restriction)
- default values

### 5.4 Section Instances
A section instance is a configured content unit created by admin.

Each section instance stores:
- title / internal name
- type
- content data
- style overrides
- assigned slot
- display rules
- priority
- status active/inactive

### 5.5 Slots / Positions
Slots are predefined insertion points in layout templates.

Examples:
- home_before_header
- home_after_header
- home_before_content
- home_after_content
- home_before_footer

- product_before_gallery
- product_after_gallery
- product_before_summary
- product_after_summary
- product_before_tabs
- product_after_tabs
- product_before_related
- product_after_related

- archive_before_title
- archive_after_title
- archive_before_loop
- archive_after_loop
- archive_sidebar_top
- archive_sidebar_bottom

- header_top
- header_bottom
- footer_top
- footer_bottom

Each layout declares which slots it supports.

### 5.6 Display Rules
Each section instance can define display targeting logic.

Supported rule targets:
- entire_site
- homepage
- page_id
- all_products
- single_product
- product_id
- all_product_archives
- product_category
- product_tag
- shop_page

Support:
- include rules
- exclude rules

Priority:
- Exclude rules override include rules.

---

## 6. Recommended Architecture

### 6.1 Preferred Split
Use:
- Theme for rendering and templates
- Companion plugin for data, admin screens, section config, rules

Theme responsibilities:
- register support
- enqueue assets
- define layout registry
- define section templates
- render slots
- WooCommerce integration
- output CSS variables
- frontend rendering

Plugin responsibilities:
- admin UI
- save global settings
- section instance CRUD
- rule evaluation service
- slot assignment management
- expose data retrieval APIs

### 6.2 If starting with one repository
Still structure code in modules:
- Core
- Settings
- Layout Registry
- Section Registry
- Section Renderer
- Rule Engine
- WooCommerce Integration
- Admin UI

---

## 7. Data Model

### 7.1 Global Settings Storage
Use WordPress options for site-wide settings.

Option group examples:
- mytheme_global_settings
- mytheme_layout_settings
- mytheme_style_settings

Suggested fields:
- logo_id
- favicon_id
- header_layout
- footer_layout
- master_layout
- product_layout
- archive_layout
- heading_font
- body_font
- color_primary
- color_secondary
- color_accent
- color_background
- button_style
- container_width
- border_radius_scale
- shadow_preset
- spacing_scale

### 7.2 Layout Registry
Store preset definitions in PHP arrays or classes.

Structure example:
{
  "headers": {
    "header-1": {
      "label": "Header 1",
      "template": "template-parts/headers/header-1.php",
      "slots": ["header_top", "header_bottom"]
    }
  }
}

### 7.3 Section Registry
Store section type definitions in code.

Each section type should include:
- id
- label
- template
- supported fields
- supported style fields
- allowed slots
- default content
- default styles

### 7.4 Section Instance Storage
Preferred: Custom Post Type `theme_section`

Reason:
- manageable in admin
- easy revisions
- metadata friendly
- easy duplication later

CPT name:
- theme_section

Store section instance data using post meta:
- _section_type
- _section_content_json
- _section_style_json
- _section_slot
- _section_display_rules_json
- _section_priority
- _section_status

Alternative acceptable only if simplifying MVP:
- store JSON in option table
But preferred implementation is CPT.

---

## 8. Admin UX Requirements

### 8.1 Theme Settings Screen
Tabs:
- Branding
- Typography
- Colors
- Layout Defaults
- Design Tokens

Allow admin to configure:
- logo
- fonts
- colors
- button style
- spacing
- default layouts

### 8.2 Layout Manager Screen
Allow admin to select active presets for:
- header
- footer
- master
- product
- archive

### 8.3 Section Instances Screen
Allow admin to:
- create section
- edit section
- duplicate section
- activate / deactivate section
- assign slot
- assign display rules
- define priority

### 8.4 Section Editor Fields
When creating/editing a section:
- choose section type
- load fields dynamically for that type
- choose slot from valid available slots
- choose display rules
- set priority
- set content
- set style overrides

### 8.5 Rule UI
Support include/exclude conditions like:
- show on homepage
- show on all products
- show on category X
- hide on product Y

---

## 9. Frontend Rendering Contract

### 9.1 Rendering Order
For every request:
1. Detect page context
2. Resolve active global settings
3. Resolve selected layout presets
4. Resolve all active section instances matching current context
5. Group sections by slot
6. Sort by priority
7. Render templates

### 9.2 Page Context Detection
Need a reusable context service that can identify:
- is_homepage
- is_shop
- is_product
- current_product_id
- current_product_cat_ids
- current_page_id
- is_product_archive
- is_product_category
- current_term_id

### 9.3 Slot Rendering API
Implement a reusable renderer function:

render_slot(slot_name, context)

Behavior:
- fetch matching section instances
- filter by active status
- validate slot
- evaluate display rules
- sort by priority ascending
- render section template with merged data

### 9.4 Section Rendering
Each section should render through a template part.

Data passed to template:
- section instance id
- section type
- content
- style
- global settings
- page context

### 9.5 Merge Logic
When rendering:
- global settings provide base styles
- layout preset provides structure
- section instance content provides text/media
- section style overrides can override section-level appearance only
- section overrides must not break global tokens unless explicitly allowed

---

## 10. WooCommerce Integration Requirements

### 10.1 Product Layout Presets
Implement several predefined product layouts.

At minimum:
- product-layout-1: gallery left / summary right
- product-layout-2: centered / stacked emphasis
- product-layout-3: sticky summary / modern commerce style

Each product layout must define supported slots.

### 10.2 Archive Layout Presets
Implement several archive layouts:
- grid standard
- grid with hero/filter banner
- sidebar layout

### 10.3 WooCommerce Hook Integration
Use WooCommerce hooks to inject slots safely.

Examples:
- before single product summary
- after single product summary
- before related products
- after related products
- archive before loop
- archive after loop

Do not hardcode everything into one giant template if hook-based injection is possible.

### 10.4 Header/Footer Variants
Header/Footer are code-defined presets.
Admin only selects and configures them.
No arbitrary HTML builder.

---

## 11. Global Styling System

Use CSS variables and token-driven styling.

Example output in frontend head:
:root {
  --site-font-heading: "Poppins", sans-serif;
  --site-font-body: "Inter", sans-serif;
  --color-primary: #0f172a;
  --color-secondary: #334155;
  --color-accent: #f59e0b;
  --color-bg: #ffffff;
  --container-width: 1280px;
  --section-gap: 80px;
  --radius-md: 12px;
}

Requirements:
- all layouts and sections should consume shared tokens
- style consistency must remain even when changing layout presets
- typography and colors should propagate globally

---

## 12. MVP Deliverables

Implement first:

### Phase 1 MVP
- global settings page
- layout preset registry
- section type registry
- CPT for section instances
- slot renderer
- display rules engine
- homepage slots
- single product slots
- archive/category slots
- 4 basic section types:
  - hero
  - cta
  - features
  - benefits
- 2 header presets
- 2 footer presets
- 2 product layout presets
- 1 archive layout preset
- CSS variables output

### Phase 2
- duplicate section
- preview support
- testimonials / faq / banner / product-grid sections
- import/export config
- responsive style controls

### Phase 3
- advanced conditions
- scheduling
- device visibility
- template bundles

---

## 13. Folder Structure Expectation

Use a clean modular structure like:

theme-root/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── inc/
│   ├── Core/
│   ├── Settings/
│   ├── Layouts/
│   ├── Sections/
│   ├── Rules/
│   ├── WooCommerce/
│   ├── Admin/
│   └── Helpers/
├── template-parts/
│   ├── headers/
│   ├── footers/
│   ├── sections/
│   ├── product/
│   └── archive/
├── woocommerce/
│   ├── single-product.php
│   ├── archive-product.php
│   └── content-single-product.php
├── functions.php
└── style.css

If companion plugin is created:

plugin-root/
├── includes/
│   ├── Admin/
│   ├── Settings/
│   ├── Sections/
│   ├── Rules/
│   └── API/
├── plugin-main.php
└── assets/

---

## 14. Coding Requirements

- Use object-oriented PHP where useful, but keep WordPress compatibility practical.
- Follow WordPress coding standards as much as possible.
- Escape output properly.
- Sanitize all saved data.
- Use nonce verification in admin forms.
- Separate admin logic from frontend logic.
- Avoid giant procedural files.
- Use clear service classes or registries.

---

## 15. Suggested Internal Modules

Implement these modules/classes or equivalent:

### Core
- ThemeSetup
- AssetManager
- Helpers

### Settings
- GlobalSettingsManager
- CssVariableOutput

### Layouts
- LayoutRegistry
- LayoutResolver

### Sections
- SectionTypeRegistry
- SectionInstanceRepository
- SectionRenderer
- SlotRenderer

### Rules
- PageContextResolver
- DisplayRuleEvaluator

### WooCommerce
- ProductLayoutManager
- ArchiveLayoutManager
- HookRegistrar

### Admin
- SettingsPage
- SectionMetaBoxes or custom admin UI
- LayoutSelectionPage

---

## 16. Display Rule Evaluation Logic

Implement deterministic logic.

Pseudo behavior:
- If section inactive => do not render
- If slot does not match => do not render
- If include rules empty => assume not matched unless section targets entire_site
- If include rules matched and exclude rules not matched => render
- If any exclude rule matched => do not render

Support at least:
- homepage
- page_id
- all_products
- product_id
- product_category
- product_archive
- shop_page

---

## 17. Performance Expectations

- Avoid repeated heavy queries during rendering.
- Cache resolved section instance data where possible.
- Minimize DB lookups per request.
- Use efficient meta queries for section retrieval.
- Prefer resolving all matching sections once and grouping by slot.

---

## 18. Initial Section Type Schemas

### hero
Fields:
- eyebrow
- heading
- subheading
- primary_button_text
- primary_button_url
- secondary_button_text
- secondary_button_url
- background_image_id
- alignment

### cta
Fields:
- heading
- content
- button_text
- button_url
- background_image_id

### features
Fields:
- heading
- intro
- items[]:
  - icon
  - title
  - description

### benefits
Fields:
- heading
- items[]:
  - title
  - description

---

## 19. Initial Layout Presets

### Headers
- header-1: logo left / nav center / actions right
- header-2: topbar + logo/nav/actions row

### Footers
- footer-1: 4 columns standard
- footer-2: newsletter + columns + copyright

### Product Layouts
- product-layout-1: gallery left / summary right
- product-layout-2: stacked modern layout

### Archive Layout
- archive-layout-1: standard grid + optional top banner slot

---

## 20. Example Output Behavior

Example:
Admin selects:
- header-2
- footer-1
- product-layout-2
- heading font = Poppins
- body font = Inter
- primary color = #111827
- accent color = #f59e0b

Admin creates:
1. Hero section for homepage at slot home_after_header
2. Feature section for homepage at home_before_content
3. Benefit section for all single products at product_after_summary
4. CTA section for product category "Shoes" at archive_before_loop

Frontend result:
- homepage uses selected header/footer + homepage sections
- single product pages use selected product layout + benefit section
- category Shoes shows CTA before loop
- all styles follow shared global tokens

---

## 21. Expected Implementation Order

Code in this order:

1. scaffold theme architecture
2. create registries for layouts and section types
3. create global settings storage and admin page
4. create CPT for section instances
5. create page context resolver
6. create display rule evaluator
7. create slot renderer
8. integrate homepage rendering
9. integrate WooCommerce single product rendering
10. integrate archive/category rendering
11. add starter section templates
12. refine styling system

---

## 22. Expected Deliverables from Codex

Generate:
- project file structure
- PHP architecture
- registries
- admin screens or meta box implementations
- section CPT
- rendering pipeline
- WooCommerce integration
- starter CSS and template files
- sample section templates
- sample settings page
- sample layout resolver
- README for project setup

---

## 23. Constraints and Quality Bar

The output must be:
- modular
- maintainable
- extendable
- WooCommerce-safe
- WordPress-friendly
- not overengineered but production-capable

Avoid:
- tightly coupled logic
- giant switch statements everywhere
- putting all rendering into functions.php
- fragile admin field handling
- unstructured template overrides

---

## 24. First Task for Codex

Start by scaffolding the project architecture for the MVP.

Generate:
1. theme folder structure
2. core bootstrap files
3. layout registry
4. section type registry
5. global settings manager
6. theme_section CPT
7. slot renderer
8. display rule evaluator
9. sample header/footer/product templates
10. sample hero/cta/features/benefits templates

Then wire the homepage and WooCommerce single product page to use the slot-based rendering system.

When making implementation choices:
- prefer clarity over cleverness
- keep extensibility in mind
- document key classes
- add TODOs only where necessary

---

# AGENTS.md SUGGESTION

## Project
Custom WordPress + WooCommerce preset-based theme builder.

## Objective
Build a structured, maintainable theme system with:
- layout presets
- slot-based section injection
- display rules
- global design tokens

## Technical Rules
- Follow WordPress coding standards where practical.
- Keep admin logic separate from frontend logic.
- Use registries for layouts and section types.
- Use CPT `theme_section` for section instances.
- Use CSS variables for design tokens.
- Prefer modular PHP classes over large procedural files.
- Escape output and sanitize input everywhere.

## MVP Priority
1. Global settings
2. Layout registry
3. Section type registry
4. Section CPT
5. Rule engine
6. Slot renderer
7. Homepage integration
8. Single product integration
9. Archive/category integration

## Do Not
- Do not build a freeform drag-and-drop page builder.
- Do not allow arbitrary admin HTML to replace layout templates.
- Do not place everything in functions.php.

## Deliverables
- working scaffold
- registries
- settings page
- section renderer
- WooCommerce-compatible templates
- starter CSS
- README

---

# OPTIONAL EXECUTION NOTE FOR CODEX

Implement the MVP in a staged manner.
After each stage, summarize:
- files created
- architecture decisions
- assumptions
- next step

Do not stop at planning only.
Create actual files and code for the scaffold.

When uncertain, choose the simplest architecture that preserves extensibility.
Prefer working code over placeholder abstractions.
