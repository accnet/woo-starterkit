# StarterKit Theme Builder

WordPress theme cho WooCommerce, xây theo hướng preset-based theme builder. Theme này tách phần "khung giao diện" (header, footer, product, archive, checkout, cart) ra khỏi nội dung WordPress thông thường, để có thể chọn layout, kéo thả element theo zone, và quản lý global settings trong admin.

## Mục tiêu

- Dùng preset layout thay vì build theme cứng theo một giao diện duy nhất.
- Cho phép cấu hình nhanh trong admin: branding, design tokens, layout, performance.
- Có live Theme Builder để sắp xếp element theo từng zone.
- Hỗ trợ WooCommerce với custom shell cho cart, checkout, product và archive.

## Tính năng chính

- Theme Settings trong admin:
  - Branding: logo, favicon, font, bo góc, button style.
  - Global Settings / Design Tokens.
  - Chọn preset cho header, footer, single product, archive.
  - Performance toggles.
  - Import / export cấu hình.
- Theme Builder:
  - Bootstrap dữ liệu qua AJAX.
  - Lưu builder state và layout settings có version control để tránh ghi đè session khác.
  - Render lại từng zone hoặc từng layout partial trong preview.
- Preset layout:
  - Header: `header-1`, `header-2`, `header-3`
  - Footer: `footer-1`, `footer-2`, `footer-3`
  - Product: `product-layout-1`, `product-layout-2`, `product-layout-3`
  - Archive: `archive-layout-1`, `archive-layout-2`
- Element registry theo thư mục `elements/` cho header, footer, product, archive.
- WooCommerce integration:
  - Override template cho cart, checkout, single product, archive.
  - Cart drawer.
  - Checkout runtime / summary / step registry.
- Compatibility layer cho `wootify-core`.

## Yêu cầu

- WordPress `>= 6.0`
- PHP `>= 7.4`
- WooCommerce để dùng đầy đủ các màn commerce

## Cài đặt

1. Copy theme vào `wp-content/themes/starterkit`
2. Activate theme trong WordPress admin
3. Cài và activate WooCommerce nếu site dùng commerce
4. Vào `Theme Settings` để chọn layout và cấu hình cơ bản
5. Vào `Theme Builder` để chỉnh zone và element theo preset đang active

## Luồng quản trị

### 1. Theme Settings

Menu admin: `Theme Settings`

Các tab hiện có:

- `Branding`
- `Global Settings`
- `Design Tokens`
- `Layouts`
- `Performance`
- `Tools`

Đây là nơi chọn preset chính cho site, ví dụ:

- `header_layout`
- `footer_layout`
- `product_layout`
- `archive_layout`
- bật / tắt custom cart page và custom checkout page

### 2. Theme Builder

Menu admin: `Theme Settings > Theme Builder`

Builder dùng các AJAX endpoint:

- `starterkit_theme_builder_bootstrap`
- `starterkit_theme_builder_save_state`
- `starterkit_theme_builder_render_zone`
- `starterkit_theme_builder_render_layout_partial`

Builder state và layout settings được lưu riêng, có version để phát hiện conflict khi nhiều session cùng sửa.

## Cấu trúc thư mục

```text
assets/
  css/
  js/
  vendor/
compatibility/
  wootify-core/
elements/
  archive/
  footer/
  header/
  product/
inc/
  Admin/
  Core/
  Layouts/
  Rules/
  Settings/
  ThemeBuilder/
  WooCommerce/
template-parts/
  archive/
  footers/
  headers/
  product/
woocommerce/
```

## Kiến trúc chính

### Core

- `inc/bootstrap.php`: boot theme
- `inc/Core/App.php`: service container trung tâm
- `inc/Core/ThemeSetup.php`: khai báo support, menu, image size, hook nền
- `inc/Core/AssetManager.php`: enqueue CSS/JS theo context
- `inc/Core/PerformanceManager.php`: các tối ưu frontend

### Settings

- `inc/Admin/SettingsPage.php`: UI admin cho Theme Settings
- `inc/Settings/GlobalSettingsManager.php`: đọc, ghi, sanitize option global
- `inc/Settings/CssVariableOutput.php`: xuất CSS variables ra frontend

### Layouts

- `inc/Layouts/LayoutRegistry.php`: source of truth cho preset layout
- `inc/Layouts/LayoutResolver.php`: resolve preset đang active
- `inc/Layouts/LayoutSettingsManager.php`: settings riêng cho layout active

### Theme Builder

- `inc/Admin/ThemeBuilderPage.php`: màn builder trong admin
- `inc/ThemeBuilder/ApiController.php`: AJAX controller
- `inc/ThemeBuilder/BuilderStateRepository.php`: lưu builder state
- `inc/ThemeBuilder/ElementRegistry.php`: đăng ký element từ `elements/`
- `inc/ThemeBuilder/ZoneRenderer.php`: render zone theo state

### WooCommerce

- `inc/WooCommerce/CommerceTemplateManager.php`
- `inc/WooCommerce/ProductLayoutManager.php`
- `inc/WooCommerce/ArchiveLayoutManager.php`
- `inc/WooCommerce/CheckoutLayoutManager.php`
- `inc/WooCommerce/CheckoutRuntimeManager.php`
- `inc/WooCommerce/CartDrawerManager.php`

## Preset có settings riêng

Hiện tại đã thấy preset có schema cấu hình riêng:

- `header-1`
  - logo max height
  - header min height
  - background color
  - main menu
- `footer-1`
  - column count
  - toggle hiển thị từng cột

Nếu preset không có `settings_schema`, builder/settings panel sẽ không hiện field cấu hình riêng cho preset đó.

## Elements

Mỗi element thường nằm trong một thư mục riêng, ví dụ:

- `element.json`: metadata và schema
- `render.php`: markup output
- `style.css`: style riêng nếu cần

Ví dụ các nhóm element hiện có:

- Header: topbar, promo-banner, payment-icons, trust-badges
- Footer: newsletter
- Product: countdown, FAQ, guarantee, stock-counter, trust-badge, review-summary
- Archive: intro-text, category-banner, trust-strip

## WooCommerce overrides

Theme đang override một số file trong:

- `woocommerce/cart/`
- `woocommerce/checkout/`
- `woocommerce/single-product/`
- `woocommerce/archive-product.php`

Ngoài ra có các entry page-level:

- `woocommerce/cart-page.php`
- `woocommerce/checkout-page.php`

## Compatibility

Thư mục `compatibility/wootify-core/` chứa lớp tương thích với plugin `wootify-core`.

Phạm vi hiện tại:

- `wootify-core` giữ logic product matrix, variant, pricing, thumbnail, metadata
- theme giữ phần cart drawer UI, upsell interaction, selector sheet nhẹ phía frontend

## Ghi chú phát triển

- Theme dùng autoloader nội bộ từ `inc/Core/Autoloader.php`
- Version asset được resolve qua `inc/Core/AssetVersion.php`
- Không có pipeline build frontend riêng trong repo này; CSS/JS đang được commit trực tiếp trong `assets/`
- Khi thêm layout mới, nên cập nhật ít nhất:
  - `LayoutRegistry`
  - `template-parts/`
  - asset tương ứng
  - logic resolve / render nếu layout có behavior riêng
- Khi thêm element mới, nên tạo thư mục mới trong `elements/` với `element.json` và `render.php`

## File nên đọc đầu tiên

- [style.css](/var/www/site1.local/wp-content/themes/starterkit/style.css)
- [inc/bootstrap.php](/var/www/site1.local/wp-content/themes/starterkit/inc/bootstrap.php)
- [inc/Core/App.php](/var/www/site1.local/wp-content/themes/starterkit/inc/Core/App.php)
- [inc/Layouts/LayoutRegistry.php](/var/www/site1.local/wp-content/themes/starterkit/inc/Layouts/LayoutRegistry.php)
- [inc/Admin/SettingsPage.php](/var/www/site1.local/wp-content/themes/starterkit/inc/Admin/SettingsPage.php)
- [inc/Admin/ThemeBuilderPage.php](/var/www/site1.local/wp-content/themes/starterkit/inc/Admin/ThemeBuilderPage.php)
- [inc/ThemeBuilder/ApiController.php](/var/www/site1.local/wp-content/themes/starterkit/inc/ThemeBuilder/ApiController.php)

## Trạng thái hiện tại

Đây là một theme scaffold đã có phần khung quản trị và WooCommerce integration tương đối rõ, nhưng chưa phải package đóng gói hoàn chỉnh theo kiểu public distribution. README này được viết lại để phản ánh đúng cấu trúc và hành vi hiện có trong codebase.
