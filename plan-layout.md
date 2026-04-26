# Ke Hoach: Layout Settings Schema Cho `header-1` Va `footer-1`

## Summary
- Trong Master context, inspector ben phai mac dinh mo panel `Settings`.
- Them nut `Settings` truoc `Save & Publish` de mo lai panel cau hinh layout sau khi dang chinh zone/element.
- Chi dua layout-specific settings vao Theme Builder; global style nhu typography, design tokens, spacing token, button style van o Theme Settings.
- Trien khai theo cau truc mo rong duoc cho cac preset sau nay, khong hard-code field trong JavaScript hoac template.
- Frontend runtime phai re: doc settings mot lan, cache trong request, template chi render theo data da sanitize.

## Architecture
- `LayoutRegistry`
  - La source of truth cho layout preset metadata: `id`, `label`, `description`, `template`, `asset_base`, `slots`.
  - Them `settings_schema` vao tung preset co the cau hinh.
  - Schema dung format tuong thich control builder hien co: `id`, `type`, `label`, `default`, `min`, `max`, `step`, `options`, `help`.

- `LayoutSettingsManager` moi
  - Doc schema tu `LayoutRegistry`.
  - Sinh defaults tu `settings_schema.default`.
  - Merge saved settings tu `starterkit_global_settings`.
  - Sanitize value theo type control.
  - Tra ve settings theo layout: `get_layout_settings( 'header-1' )`.
  - Tra ve schemas active trong Master cho Theme Builder: header layout active va footer layout active.
  - Memoize trong request de template khong goi `get_option()`/sanitize lap lai.

- `GlobalSettingsManager`
  - Van quan ly global theme settings hien tai.
  - Them memoization cho `all()`.
  - `sanitize()` nen goi qua `LayoutSettingsManager` hoac shared sanitizer cho cac key layout-specific de khong duplicate logic.

- Templates
  - `header-1/footer-1` chi render theo settings da sanitize.
  - Khong chua logic schema/default phuc tap.
  - Khong query settings lap trong loop.

- Theme Builder App
  - Render inspector Settings tu `layoutSettingsSchemas`.
  - Khong hard-code `header-1/footer-1` controls trong JS.
  - Draft layout settings song song voi builder state va chi publish khi bam `Save & Publish`.

## Header-1 Settings
- `header_1_logo_max_height`
  - Type: `range`
  - Default: `45`
  - Min/max/step: `24 / 96 / 1`
  - Unit render: `px`
  - Target: `.site-header--preset-1 .site-logo`

- `header_1_header_min_height`
  - Type: `range`
  - Default: `72`
  - Min/max/step: `56 / 128 / 1`
  - Unit render: `px`
  - Target: `.site-header--preset-1 .header-shell--preset-1`

- `header_1_background_color`
  - Type: `color`
  - Default: `#ffffff`
  - Target: `.site-header--preset-1`
  - Day la setting rieng cua layout, khong thay the global color tokens.

- `header_1_main_menu_id`
  - Type: `select`
  - Default: `0`
  - Options:
    - `0`: Use Primary Menu
    - Cac WP nav menu hien co.
  - Render rule:
    - `0`: `wp_nav_menu( array( 'theme_location' => 'primary', 'fallback_cb' => false ) )`
    - Khac `0`: `wp_nav_menu( array( 'menu' => absint( $menu_id ), 'fallback_cb' => false ) )`

## Footer-1 Settings
- `footer_1_column_count`
  - Type: `select`
  - Default: `4`
  - Options: `1`, `2`, `3`, `4`
  - Target: `.footer-grid--preset-1`

- `footer_1_show_column_1`
- `footer_1_show_column_2`
- `footer_1_show_column_3`
- `footer_1_show_column_4`
  - Type: `toggle`
  - Default: `1`
  - Render rule:
    - Column hien thi neu `column_index <= footer_1_column_count` va toggle cua column do dang bat.
    - Neu tat het column, fallback hien thi column 1 de footer khong rong.

- Footer render nen tinh `$visible_columns` mot lan o dau template, sau do loop render.
- Grid column count nen dung CSS variable:
  - `--footer-1-columns: {visible_count}`
  - CSS: `grid-template-columns: repeat(var(--footer-1-columns, 4), minmax(0, 1fr));`

## Frontend Rendering
- `header-1`
  - Doc `$settings = starterkit()->layout_settings_manager()->get_layout_settings( 'header-1' );`
  - Render CSS variables tren `<header>`:
    - `--header-1-logo-max-height`
    - `--header-1-min-height`
    - `--header-1-bg`
  - CSS dung fallback de tranh layout vo neu setting thieu.
  - Menu args tinh mot lan truoc khi render nav.

- `footer-1`
  - Doc `$settings = starterkit()->layout_settings_manager()->get_layout_settings( 'footer-1' );`
  - Build mang column definitions mot lan.
  - Filter visible columns mot lan.
  - Render visible columns bang loop.
  - Widget fallback giu nguyen noi dung hien tai cua tung column.

## Theme Builder Flow
- Bootstrap payload them:
  - `layoutSettings`
  - `layoutSettingsVersion`
  - `layoutSettingsSchemas`
  - `navMenus`

- Inspector behavior:
  - Khi load vao Master: mo `Settings`.
  - Khi bam zone/element: mo inspector element hien tai.
  - Nut `Settings` tren navbar clear selection va mo lai layout settings.
  - Nut `Settings` chi hien trong Master context.
  - Product/Archive context giu behavior hien tai.

- Draft behavior:
  - Thay doi layout setting danh dau dirty.
  - `Save & Publish` gui ca builder state va layout settings draft.
  - Neu co unsaved layout settings, Exit/beforeunload phai canh bao nhu builder state.
  - `Undo Last Change` restore ca builder state va layout settings draft.

- Preview behavior:
  - Preview nhan draft layout settings qua postMessage.
  - Header/footer style settings nen apply bang CSS variables de khong reload iframe.
  - `header_1_main_menu_id` can AJAX render menu HTML roi replace `.site-navigation` trong preview.
  - Save moi ghi vao `starterkit_global_settings`.

## Performance Constraints
- Khong de frontend that phu thuoc vao preview/draft logic.
- `GlobalSettingsManager::all()` memoize trong request.
- `BuilderStateRepository::all()` memoize normalized state trong request va clear cache sau `save_state()`.
- `LayoutResolver::resolve()` memoize layout theo type.
- Asset version helper nen cache `filemtime()` theo path.
- `ElementAssetManager` chi enqueue element assets theo context hien tai:
  - Master header/footer elements: enqueue tren moi frontend request vi header/footer render toan site.
  - Product layout elements: chi enqueue tren single product.
  - Archive layout elements: chi enqueue tren shop/product archive.
- Khong quet/toan bo normalize builder state nhieu lan chi de lay asset.
- Khong them query moi trong template neu co the lay tu settings da co.
- Khong load Swiper/CDN neu product gallery khong can slider.

## Testing
- PHP lint:
  - `inc/Layouts/LayoutRegistry.php`
  - `inc/Settings/GlobalSettingsManager.php`
  - class `LayoutSettingsManager` moi
  - templates `header-1/footer-1`
  - Theme Builder API/controller file lien quan

- JS check:
  - `node --check assets/js/theme-builder-app.js`
  - `node --check assets/js/theme-builder-preview.js`

- Manual frontend:
  - Frontend mac dinh van render header-1/footer-1 nhu truoc.
  - Doi logo max height, header min height, background va save: frontend that thay doi dung.
  - Doi main menu: header render dung menu duoc chon.
  - Doi footer column count/toggles: footer hien dung so cot, fallback column 1 khi tat het.
  - Product/archive pages khong mat asset can thiet.

- Manual Theme Builder:
  - Master load mac dinh mo Settings.
  - Nut Settings mo lai panel sau khi da chon element.
  - Draft settings hien trong preview truoc khi save.
  - Save & Publish luu ca builder state va layout settings.
  - Exit/beforeunload canh bao khi co layout settings dirty.

## Assumptions
- `main menu` cua header-1 la chon WP nav menu cu the; default `0` tiep tuc dung Primary Menu location.
- Style global nhu typography, nav font, button style, spacing token van nam trong Theme Settings.
- Footer-1 ban nay chi cau hinh bo cuc cot, khong them mau nen/typography/footer text.
- Uu tien frontend render performance va cau truc mo rong truoc; minify/build pipeline co the lam sau.
