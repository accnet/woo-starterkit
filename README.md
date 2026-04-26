# Phân Tích: Layout Settings Schema Cho `header-1` Và `footer-1`

**Source Of Truth**
- Thêm `settings_schema` vào từng preset trong `LayoutRegistry`.
- Theme Builder chỉ đọc schema của layout đang active trong Master:
  - Active header: `header_layout`, mặc định `header-1`.
  - Active footer: `footer_layout`, mặc định `footer-1`.
- Nếu preset không có `settings_schema`, panel Settings hiển thị “No configurable settings”.

**Header-1 Configurable Parts**
- `header_1_logo_max_height`
  - Điều khiển logo trong `.site-header--preset-1 .site-logo`.
  - Hiện tại đang hard-code `max-height: 45px`.
  - Type: `range`, default `45`, min `24`, max `96`, unit `px`.

- `header_1_header_min_height`
  - Điều khiển chiều cao vùng header shell `.header-shell--preset-1`.
  - Hiện tại chưa có min-height riêng, nên thêm CSS var/style.
  - Type: `range`, default `72`, min `56`, max `128`, unit `px`.

- `header_1_background_color`
  - Điều khiển background của `<header class="site-header--preset-1">`.
  - Type: `color`, default `#ffffff`.
  - Đây là setting riêng layout, không thay thế global theme colors.

- `header_1_main_menu_id`
  - Điều khiển menu render trong `.site-navigation`.
  - Default `0` nghĩa là giữ `theme_location => primary`.
  - Nếu chọn menu cụ thể thì dùng `menu => absint(header_1_main_menu_id)`.

**Footer-1 Configurable Parts**
- `footer_1_column_count`
  - Điều khiển số cột tối đa hiển thị trong `.footer-grid--preset-1`.
  - Type: `select`, options `1`, `2`, `3`, `4`, default `4`.

- `footer_1_show_column_1` đến `footer_1_show_column_4`
  - Toggle từng footer widget column.
  - Default `1`.
  - Render rule: column được hiển thị nếu `index <= column_count` và toggle đang bật.
  - Nếu tất cả bị tắt, fallback hiển thị column 1 để footer không rỗng.

**Schema Shape**
- Mỗi layout preset có dạng:
  - `settings_schema`: danh sách control để UI render.
  - `settings_defaults`: có thể suy ra từ `settings_schema.default`.
  - `settings_group`: `header` hoặc `footer` để panel nhóm field rõ ràng.
- Control nên tái dùng format đang có của element builder:
  - `id`
  - `type`
  - `label`
  - `default`
  - `min/max/step`
  - `options`
  - `help`

**How Settings Knows What Is Configurable**
- API bootstrap trả về `layoutSettingsSchemas` chỉ gồm active Master layouts.
- Inspector Settings render theo schema:
  - Section `Header 1`
  - Section `Footer 1`
- Khi user đổi header/footer layout sau này, schema sẽ đổi theo preset active.
- Không có schema thì không có field, nên UI tự biết preset nào cấu hình được.

**Assumptions**
- Style global như typography, nav font, button style, spacing token vẫn ở Theme Settings.
- Các field trên là layout-level settings, lưu trong `starterkit_global_settings`.
- Preview dùng draft settings trong iframe để thấy thay đổi trước khi `Save & Publish`.
