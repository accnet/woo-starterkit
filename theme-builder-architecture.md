# Theme Builder Implementation Checklist

## 1. Mục tiêu triển khai

- [ ] Xây Theme Builder theo mô hình preset-based
- [ ] Giữ preset là source of truth cho structure và zones
- [ ] Chỉ cho phép edit trong các zone preset khai báo
- [ ] Tham khảo Elementor ở editor UX, không áp dụng freeform layout model
- [ ] Tách rõ backend schema, runtime renderer, preview, editor shell

---

## 2. Guardrails bắt buộc

- [ ] Không sinh zone từ builder UI
- [ ] Không cho user tạo section / container / column tùy ý
- [ ] Không cho kéo mọi block vào mọi nơi
- [ ] Không hỗ trợ nested tree vô hạn ở phase đầu
- [ ] Không để builder phá fixed structure của template preset
- [ ] Không reuse `slots` hiện tại như schema đầy đủ
- [ ] Không nhét builder logic nặng vào `inc/Admin/SettingsPage.php`

---

## 3. Kiến trúc dữ liệu cần chốt trước khi code

### 3.1 Builder contexts

- [ ] Chuẩn hóa 3 context: `master`, `product`, `archive`
- [ ] Chốt preview target cho `master`
- [ ] Chốt preview target cho `product`
- [ ] Chốt preview target cho `archive`

### 3.2 Preset schema contract

- [ ] Định nghĩa contract chuẩn cho preset schema:
  - [ ] `id`
  - [ ] `context`
  - [ ] `template`
  - [ ] `zones`
- [ ] Với mỗi zone, chốt contract:
  - [ ] `id`
  - [ ] `label`
  - [ ] `droppable`
  - [ ] `sortable`
  - [ ] `allowed_elements`
  - [ ] `settings_schema`
  - [ ] `constraints`

### 3.3 Element contract

- [ ] Định nghĩa contract chuẩn cho element definition:
  - [ ] `id`
  - [ ] `label`
  - [ ] `contexts`
  - [ ] `allowed_zones`
  - [ ] `default_settings`
  - [ ] `settings_schema`
  - [ ] `render_callback`

### 3.4 Element instance contract

- [ ] Chốt contract cho element instance:
  - [ ] `id`
  - [ ] `type`
  - [ ] `enabled`
  - [ ] `settings`

### 3.5 Builder state contract

- [ ] Chốt cấu trúc state theo `context -> preset -> zone -> element instances`
- [ ] Tách state builder khỏi `starterkit_global_settings`
- [ ] Chốt option key mới, ví dụ `starterkit_builder_state`

---

## 4. Backend foundation

### 4.1 Tạo namespace và nhóm class mới

- [ ] Tạo thư mục `inc/ThemeBuilder/`
- [ ] Tạo `inc/ThemeBuilder/BuilderContext.php`
- [ ] Tạo `inc/ThemeBuilder/PresetSchemaRegistry.php`
- [ ] Tạo `inc/ThemeBuilder/ElementRegistry.php`
- [ ] Tạo `inc/ThemeBuilder/BuilderStateRepository.php`
- [ ] Tạo `inc/ThemeBuilder/ZoneRenderer.php`
- [ ] Tạo `inc/ThemeBuilder/ElementRenderer.php`
- [ ] Tạo `inc/ThemeBuilder/BuilderMode.php`
- [ ] Tạo `inc/ThemeBuilder/PreviewContextResolver.php`
- [ ] Tạo `inc/ThemeBuilder/ApiController.php`

### 4.2 `BuilderContext`

- [ ] Khai báo constant cho `master`, `product`, `archive`
- [ ] Thêm helper validate context hợp lệ
- [ ] Thêm helper lấy danh sách context hỗ trợ

### 4.3 `PresetSchemaRegistry`

- [ ] Tạo registry preset schema cho builder
- [ ] Tách logic schema khỏi `inc/Layouts/LayoutRegistry.php`
- [ ] Thêm method:
  - [ ] `all()`
  - [ ] `get_preset( $id )`
  - [ ] `get_presets_by_context( $context )`
  - [ ] `get_zone( $preset_id, $zone_id )`
  - [ ] `resolve_active_preset_ids()`
- [ ] Đăng ký schema tối thiểu cho:
  - [ ] `header-1`
  - [ ] `footer-1`
  - [ ] `product-layout-1`
  - [ ] `archive-layout-1`

### 4.4 `ElementRegistry`

- [ ] Tạo registry element
- [ ] Thêm method:
  - [ ] `all()`
  - [ ] `get( $element_id )`
  - [ ] `get_by_context( $context )`
  - [ ] `get_for_zone( $context, $zone_id )`
  - [ ] `supports_zone( $element_id, $zone_id )`
- [ ] Đăng ký nhóm element MVP cho `master`
- [ ] Đăng ký nhóm element MVP cho `product`
- [ ] Đăng ký nhóm element MVP cho `archive`

### 4.5 `BuilderStateRepository`

- [ ] Tạo load/save state từ option riêng
- [ ] Tạo normalize state theo preset schema
- [ ] Tạo validate element compatibility theo zone
- [ ] Tạo sanitize settings của element instance
- [ ] Tự loại bỏ instance invalid nếu preset không còn support
- [ ] Giữ ổn định dữ liệu nếu đổi active preset

### 4.6 `BuilderMode`

- [ ] Xác định builder mode qua query vars
- [ ] Tạo helper `is_builder_mode()`
- [ ] Tạo helper `get_context()`
- [ ] Tạo helper `get_device_mode()`

### 4.7 `PreviewContextResolver`

- [ ] Resolve preview URL cho `master`
- [ ] Resolve preview URL cho `product`
- [ ] Resolve preview URL cho `archive`
- [ ] Có fallback khi không tìm được product/archive mẫu

### 4.8 `ZoneRenderer`

- [ ] Render wrapper chuẩn cho zone
- [ ] Render zone metadata bằng data attributes
- [ ] Render empty state khi zone chưa có element
- [ ] Render placeholder chỉ trong builder mode
- [ ] Tôn trọng `droppable`, `sortable`, `allowed_elements`

### 4.9 `ElementRenderer`

- [ ] Render element instance theo registry
- [ ] Bỏ qua instance bị disable
- [ ] Bỏ qua instance không hợp lệ với zone hiện tại
- [ ] Xuất metadata cho builder overlay

### 4.10 `ApiController`

- [ ] Chọn cơ chế API: REST hoặc `admin-ajax`
- [ ] Tạo endpoint bootstrap builder
- [ ] Tạo endpoint load state
- [ ] Tạo endpoint save state
- [ ] Tạo endpoint update element
- [ ] Tạo endpoint reorder element
- [ ] Tạo endpoint duplicate element
- [ ] Tạo endpoint toggle element
- [ ] Thêm permission check `manage_options`
- [ ] Thêm nonce validation

---

## 5. Wiring vào application container

### 5.1 `inc/Core/App.php`

- [ ] Thêm import cho các class mới
- [ ] Tạo service `preset_schema_registry()`
- [ ] Tạo service `element_registry()`
- [ ] Tạo service `builder_state_repository()`
- [ ] Tạo service `builder_mode()`
- [ ] Tạo service `preview_context_resolver()`
- [ ] Tạo service `element_renderer()`
- [ ] Tạo service `zone_renderer()`
- [ ] Tạo service `theme_builder_page()`
- [ ] Tạo service `theme_builder_api_controller()`
- [ ] Gọi boot cho các service builder trong `boot()`

### 5.2 `inc/Core/Autoloader.php`

- [ ] Xác nhận autoload hoạt động với namespace `StarterKit\\ThemeBuilder\\`
- [ ] Không cần sửa nếu mapping `inc/` đã đủ

---

## 6. Admin builder page

### 6.1 Tạo page riêng

- [ ] Tạo `inc/Admin/ThemeBuilderPage.php`
- [ ] Tạo menu hoặc submenu riêng cho builder
- [ ] Không trộn UI builder vào page settings hiện tại

### 6.2 Page shell

- [ ] Render app shell cho builder
- [ ] Tạo khu vực element library
- [ ] Tạo khu vực preview iframe
- [ ] Tạo khu vực settings drawer hoặc side panel
- [ ] Inject bootstrap config từ PHP sang JS

### 6.3 Bootstrap data cho editor

- [ ] Truyền `contexts`
- [ ] Truyền active preset ids
- [ ] Truyền preset schema
- [ ] Truyền element registry rút gọn cho UI
- [ ] Truyền preview URLs
- [ ] Truyền state hiện tại

---

## 7. Frontend assets cho builder

### 7.1 JS/CSS files

- [ ] Tạo `assets/js/theme-builder-app.js`
- [ ] Tạo `assets/js/theme-builder-preview.js`
- [ ] Tạo `assets/css/theme-builder.css`

### 7.2 Admin enqueue

- [ ] Enqueue asset builder chỉ trên builder page
- [ ] Localize config cần thiết
- [ ] Tách asset editor khỏi frontend assets thường

---

## 8. Editor shell implementation

### 8.1 State client

- [ ] Quản lý `context`
- [ ] Quản lý `activePreset`
- [ ] Quản lý `selectedZone`
- [ ] Quản lý `selectedElement`
- [ ] Quản lý `deviceMode`
- [ ] Quản lý `dirty`
- [ ] Quản lý `builderState`

### 8.2 Element library

- [ ] Hiển thị danh sách element theo context
- [ ] Filter element theo zone đang chọn
- [ ] Thêm search
- [ ] Thêm empty state khi zone không nhận element nào

### 8.3 Selection flow

- [ ] Click zone trong preview để chọn zone
- [ ] Click element trong preview để chọn element
- [ ] Sync selection giữa iframe và editor shell

### 8.4 Settings panel

- [ ] Chọn zone thì mở zone settings
- [ ] Chọn element thì mở element settings
- [ ] Render controls từ settings schema
- [ ] Update state local khi edit controls

### 8.5 Editing actions

- [ ] Add element
- [ ] Update element settings
- [ ] Delete element
- [ ] Reorder element
- [ ] Duplicate element
- [ ] Enable/disable element
- [ ] Save builder state

---

## 9. Preview architecture

### 9.1 Iframe preview

- [ ] Dùng iframe cho preview
- [ ] Tách editor shell khỏi frontend render
- [ ] Load URL theo context đang edit

### 9.2 Preview metadata

- [ ] Render `data-builder-zone`
- [ ] Render `data-builder-zone-label`
- [ ] Render `data-builder-element-id`
- [ ] Render `data-builder-element-type`

### 9.3 Overlay behavior

- [ ] Highlight zone khi hover
- [ ] Highlight element khi hover
- [ ] Hiển thị valid zone khi đang drag hoặc add
- [ ] Hiển thị invalid zone nếu không hợp lệ

### 9.4 Device preview

- [ ] Tạo mode `desktop`
- [ ] Tạo mode `tablet`
- [ ] Tạo mode `mobile`
- [ ] Thay đổi width preview tương ứng

---

## 10. Refactor runtime templates

### 10.1 Header presets

- [ ] Refactor `template-parts/headers/header-1/header.php`
- [ ] Chèn zone renderer tại vị trí `header_top`
- [ ] Chèn zone renderer tại vị trí `header_bottom`
- [ ] Đảm bảo fixed header structure không bị phá

- [ ] Refactor `template-parts/headers/header-2/header.php`
- [ ] Refactor `template-parts/headers/header-3/header.php`

### 10.2 Footer presets

- [ ] Refactor `template-parts/footers/footer-1/footer.php`
- [ ] Refactor `template-parts/footers/footer-2/footer.php`
- [ ] Refactor `template-parts/footers/footer-3/footer.php`

### 10.3 Product presets

- [ ] Refactor `template-parts/product/product-layout-1/product.php`
- [ ] Chèn zone renderer cho:
  - [ ] `product_before_gallery`
  - [ ] `product_after_gallery`
  - [ ] `product_before_summary`
  - [ ] `product_after_summary`
  - [ ] `product_before_related`
  - [ ] `product_after_related`

- [ ] Refactor `template-parts/product/product-layout-2/product.php`
- [ ] Refactor `template-parts/product/product-layout-3/product.php`

### 10.4 Archive presets

- [ ] Refactor `template-parts/archive/archive-layout-1/archive.php`
- [ ] Chèn zone renderer cho:
  - [ ] `archive_before_title`
  - [ ] `archive_after_title`
  - [ ] `archive_before_loop`
  - [ ] `archive_after_loop`
  - [ ] `archive_sidebar_top`
  - [ ] `archive_sidebar_bottom`

- [ ] Refactor preset archive còn lại để đồng bộ schema

---

## 11. Tương thích với layout system hiện tại

### 11.1 `inc/Layouts/LayoutRegistry.php`

- [ ] Giữ nguyên vai trò layout selector cho theme
- [ ] Không nhồi toàn bộ builder schema vào class này
- [ ] Chỉ dùng để resolve preset active ở mức hiện tại

### 11.2 `inc/Layouts/LayoutResolver.php`

- [ ] Tiếp tục dùng để resolve active layout
- [ ] Bổ sung bridge method nếu cần giữa active layout và preset schema

### 11.3 `inc/WooCommerce/HookRegistrar.php`

- [ ] Giữ các wrapper hook hiện tại đang dùng cho product/archive
- [ ] Kiểm tra lại các hook mở/đóng wrapper sau khi chèn zone renderer
- [ ] Đảm bảo markup hợp lệ sau refactor

---

## 12. Element MVP backlog

### 12.1 Master elements

- [ ] `topbar`
- [ ] `promo-banner`
- [ ] `trust-badges`
- [ ] `payment-icons`

### 12.2 Product elements

- [ ] `review-summary`
- [ ] `countdown`
- [ ] `trust-badge`
- [ ] `shipping-info`
- [ ] `faq`
- [ ] `guarantee`

### 12.3 Archive elements

- [ ] `category-banner`
- [ ] `promo-banner`
- [ ] `intro-text`
- [ ] `trust-strip`
- [ ] `newsletter`
- [ ] `faq`

---

## 13. Save flow và integrity rules

- [ ] Không cho save element vào zone không support
- [ ] Không cho save element vượt `max_items`
- [ ] Không cho save settings sai schema
- [ ] Giữ nguyên thứ tự element trong zone sortable
- [ ] Zone không sortable thì chặn reorder
- [ ] Khi preset đổi, state cũ phải được normalize
- [ ] Element không còn support phải bị skip hoặc quarantine an toàn

---

## 14. UX parity tham khảo từ Elementor

- [ ] Có panel trái + preview phải
- [ ] Có selection sync giữa preview và settings
- [ ] Có controls schema cho element
- [ ] Có responsive mode switcher
- [ ] Có duplicate / delete / reorder / toggle

### Không làm theo Elementor

- [ ] Không tạo canvas trống
- [ ] Không tạo freeform section
- [ ] Không nested widget trong widget ở phase đầu
- [ ] Không cho user sở hữu toàn bộ DOM tree

---

## 15. Verification checklist

### 15.1 Backend

- [ ] Không lỗi PHP khi boot app
- [ ] Các service builder được resolve đúng
- [ ] State load/save hoạt động
- [ ] API permission check đúng

### 15.2 Runtime

- [ ] Frontend bình thường không hiện overlay builder
- [ ] Builder mode render zone metadata đúng
- [ ] Template vẫn render được khi zone rỗng
- [ ] Element invalid không làm gãy page

### 15.3 Editor

- [ ] Chọn context đổi đúng preview
- [ ] Chọn zone filter đúng element library
- [ ] Add element phản ánh ngay trên preview
- [ ] Edit settings phản ánh ngay trên preview
- [ ] Save và reload vẫn giữ state

### 15.4 Responsive

- [ ] Desktop preview đúng
- [ ] Tablet preview đúng
- [ ] Mobile preview đúng

---

## 16. Thứ tự triển khai khuyến nghị

### Phase 1: Backend foundation

- [ ] Tạo `inc/ThemeBuilder/*`
- [ ] Wiring services trong `inc/Core/App.php`
- [ ] Chốt preset schema MVP
- [ ] Chốt element registry MVP
- [ ] Chốt builder state repository

### Phase 2: Runtime rendering

- [ ] Tạo `ZoneRenderer`
- [ ] Tạo `ElementRenderer`
- [ ] Refactor `header-1`
- [ ] Refactor `footer-1`
- [ ] Refactor `product-layout-1`
- [ ] Refactor `archive-layout-1`

### Phase 3: Builder admin MVP

- [ ] Tạo `ThemeBuilderPage`
- [ ] Tạo editor shell
- [ ] Tạo bootstrap payload
- [ ] Tạo add / edit / delete / reorder flow

### Phase 4: Live preview

- [ ] Tạo preview iframe
- [ ] Tạo overlay và selection sync
- [ ] Tạo device switcher

### Phase 5: Advanced editing

- [ ] Tạo drag-drop
- [ ] Tạo duplicate / toggle
- [ ] Thêm search / filter library
- [ ] Phủ thêm preset và element còn lại

---

## 17. Definition of done

- [ ] Có thể mở builder page riêng trong admin
- [ ] Có thể chọn `master`, `product`, `archive`
- [ ] Mỗi context render preview thật qua iframe
- [ ] Preset active được đọc từ theme settings hiện tại
- [ ] Zone chỉ xuất hiện nếu preset schema khai báo
- [ ] Có thể add/edit/delete/reorder element trong zone hợp lệ
- [ ] State được save ổn định
- [ ] Frontend thường không bị ảnh hưởng bởi builder UI
- [ ] Không có hành vi freeform trái với triết lý preset-based
