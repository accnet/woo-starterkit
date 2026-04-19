# Theme Builder Context

## 1. Mục tiêu

Xây dựng một **Theme Builder preset-based** cho WordPress/WooCommerce, cho phép admin **edit trực tiếp trên preview theme** và **kéo thả element vào các zone đã được định nghĩa sẵn trong từng layout preset**.

Triết lý sản phẩm:

- Không làm freeform page builder kiểu Elementor
- Không cho kéo thả mọi thứ mọi nơi
- Layout preset là **source of truth**
- Builder chỉ đọc schema của preset rồi cho phép chỉnh trong phạm vi hợp lệ
- Live edit là **guided live editing**: preview trực tiếp nhưng drag-drop có kiểm soát

---

## 2. Phạm vi tính năng hiện tại

Theme Builder có 3 nhóm layout chính:

- **Master Layout**
- **Product Layout**
- **Archive Layout**

### 2.1 Master Layout
Dùng để chỉnh các phần global của theme, đặc biệt là:

- Header
- Footer

Trên preview theme (trang chủ / homepage shell), admin có thể chèn element vào các zone của header và footer nếu layout preset cho phép.

Ví dụ:
- Header: topbar, banner promotion, actions icons...
- Footer: newsletter, trust badge, payment icons...

### 2.2 Product Layout
Dùng để chỉnh bố cục của trang chi tiết sản phẩm.

Cho phép chèn element như:
- review
- related products
- countdown
- trust badge
- shipping info
- faq
- guarantee

### 2.3 Archive Layout
Dùng để chỉnh bố cục listing/shop/category/tag.

Cho phép chèn element như:
- category banner
- promo banner
- intro text
- trust strip
- newsletter
- faq

---

## 3. Kiến trúc sản phẩm

Hệ thống được chia thành 3 lớp:

### 3.1 Builder Context
Là ngữ cảnh đang edit:

- `master`
- `product`
- `archive`

### 3.2 Layout Preset
Là preset định nghĩa cấu trúc.

Ví dụ:
- `master-1`
- `header-1`
- `footer-1`
- `product-1`
- `archive-1`

Preset định nghĩa:
- structure
- zones
- fixed parts
- allowed elements
- constraints
- settings schema của zone

### 3.3 Elements
Là các block kéo vào zone.

Ví dụ:
- `topbar-1`
- `topbar-2`
- `banner-1`
- `countdown`
- `review-summary`
- `newsletter`

---

## 4. Nguyên tắc cốt lõi

### 4.1 Preset là source of truth
Builder không tự sinh zone.

Các zone chèn được phải được định nghĩa **trong từng preset/layout**, ví dụ:
- `header-1`
- `footer-1`
- `product-1`
- `archive-1`

Theme Builder chỉ đọc các zone đó và chỉ bật drag-drop ở những zone có `droppable = true`.

### 4.2 Builder không hardcode zone
Sai:
- Builder tự định nghĩa `banner_zone`, `topbar_zone` cho mọi header

Đúng:
- Builder đọc layout preset đang active
- Layout nào có zone nào thì builder render zone đó
- Zone nào `droppable = true` thì cho thả element

### 4.3 Không làm freeform builder
Không cho phép:
- kéo mọi block vào mọi chỗ
- phá layout chính
- thay đổi cấu trúc cốt lõi ngoài phạm vi preset cho phép

### 4.4 Guided live editing
Admin được preview trực tiếp trên theme/preview shell, nhưng chỉ có thể thao tác ở các zone mà preset cho phép.

---

## 5. UI/UX Builder

## 5.1 Layout tổng thể

### Cột trái
Element Library

Bao gồm:
- search
- filter theo context
- danh sách element có thể kéo

### Cột phải
Preview theme / preview layout

Hiển thị:
- preview trực tiếp
- zone highlight khi hover
- drop area rõ ràng
- card element đã được thêm

### Panel settings
Dạng drawer/panel hiện khi click:
- zone -> mở zone settings
- element -> mở element settings

---

## 5.2 Danh sách lựa chọn cấp cao

Trong Theme Builder, user có 3 lựa chọn chính:

- `Master Layout`
- `Product Layout`
- `Archive Layout`

### Khi chọn Master Layout
Preview bên phải là homepage shell / theme shell, và cho phép edit các zone thuộc header/footer.

### Khi chọn Product Layout
Preview bên phải là single product layout.

### Khi chọn Archive Layout
Preview bên phải là archive/shop layout.

---

## 5.3 Trạng thái UI cần có

### Drag state
- zone hợp lệ: highlight xanh
- zone không hợp lệ: highlight đỏ

### Empty state
Nếu zone chưa có element:
- hiển thị placeholder
- hiển thị allowed elements
- có label rõ ràng

### Element actions
Mỗi element sau khi thêm vào zone nên có:
- Edit
- Duplicate
- Disable/Enable
- Delete
- Reorder nếu zone sortable

### Device preview
Nên có:
- Desktop
- Tablet
- Mobile

---

## 6. Vocabulary thống nhất

Dev nên thống nhất các khái niệm sau:

### Builder Context
- `master`
- `product`
- `archive`

### Layout Preset
- `master-1`
- `header-1`
- `footer-1`
- `product-1`
- `archive-1`

### Zone
Vùng có thể chèn element.
Ví dụ:
- `banner_zone`
- `topbar_zone`
- `summary_zone`
- `after_tabs_zone`

### Fixed Part
Phần cố định, không cho drop.
Ví dụ:
- `logo_slot`
- `menu_slot`
- `gallery_core`

### Element
Block kéo vào zone.
Ví dụ:
- `banner-1`
- `topbar-2`
- `search-icon`
- `countdown`

---

## 7. Master Layout Spec

## 7.1 Vai trò
Master Layout là shell chung của theme, chủ yếu để chỉnh:
- Header
- Footer

### Preview gợi ý

```text
------------------------------------------------
| [ Banner Zone ]                              |
------------------------------------------------
| [ Topbar Zone ]                              |
------------------------------------------------
| Logo | Menu | Actions                        |
------------------------------------------------
| (Main content placeholder / homepage shell)  |
------------------------------------------------
| [ Pre Footer Zone ]                          |
------------------------------------------------
| Footer Columns                               |
------------------------------------------------
| [ Footer Bottom Zone ]                       |
------------------------------------------------
```

---

## 7.2 Header zones

### `banner_zone`
- droppable: true
- max_items: 1
- allowed_elements:
  - `banner-1`
  - `banner-2`

### `topbar_zone`
- droppable: true
- max_items: 1
- allowed_elements:
  - `topbar-1`
  - `topbar-2`

### `actions_slot`
- droppable: true
- sortable: true
- max_items: 5
- allowed_elements:
  - `search-icon`
  - `account-icon`
  - `wishlist-icon`
  - `mini-cart`
  - `hotline`
  - `social`

### Fixed parts của header
- `logo_slot` -> fixed
- `menu_slot` -> fixed

Không cho drop vào các fixed parts trừ khi preset khác định nghĩa khác.

---

## 7.3 Footer zones

### `pre_footer_zone`
- droppable: true
- allowed_elements:
  - `newsletter`
  - `trust-badge`
  - `promo-banner`
  - `brand-strip`

### `footer_columns`
Special zone.
Không cho drag-drop tự do kiểu unlimited.
Nên config theo column layout preset:
- 2 cột
- 3 cột
- 4 cột

Mỗi cột có thể nhận block loại:
- text
- menu
- contact
- social

### `footer_bottom_zone`
- droppable: true
- allowed_elements:
  - `copyright`
  - `payment-icons`
  - `social`
  - `footer-menu`

---

## 8. Product Layout Spec

## 8.1 Vai trò
Dùng để edit single product layout theo preset.

### Preview gợi ý

```text
------------------------------------------------
| Breadcrumb                                   |
------------------------------------------------
| Gallery               | Summary              |
------------------------------------------------
| [ after_summary_zone ]                        |
------------------------------------------------
| Tabs                                          |
------------------------------------------------
| [ after_tabs_zone ]                           |
------------------------------------------------
| Related Products                              |
------------------------------------------------
```

---

## 8.2 Product zones

### `summary_zone`
- droppable: true
- allowed_elements:
  - `countdown`
  - `trust-badge`
  - `shipping-info`
  - `payment-icons`
  - `stock-notice`

### `after_summary_zone`
- droppable: true
- allowed_elements:
  - `trust-badge`
  - `faq`
  - `guarantee`
  - `icon-box`
  - `custom-text`

### `after_gallery_zone`
- droppable: true
- allowed_elements:
  - `trust-badge`
  - `shipping-info`

### `after_tabs_zone`
- droppable: true
- allowed_elements:
  - `faq`
  - `testimonial`
  - `brand-story`
  - `custom-content`

### `related_zone`
- droppable: true
- max_items: 2
- allowed_elements:
  - `related-products`
  - `upsell-products`
  - `recently-viewed`

### Fixed parts của product layout
- main gallery core
- product summary core
- product tabs core

Các phần này do preset quyết định, không cho builder phá vỡ.

---

## 9. Archive Layout Spec

## 9.1 Vai trò
Dùng để edit listing/archive/shop/category layouts.

### Preview gợi ý

```text
------------------------------------------------
| [ before_archive_zone ]                      |
------------------------------------------------
| Toolbar / Filter                             |
------------------------------------------------
| [ after_toolbar_zone ]                       |
------------------------------------------------
| Product Grid                                 |
------------------------------------------------
| [ after_grid_zone ]                          |
------------------------------------------------
| [ archive_bottom_zone ]                      |
------------------------------------------------
```

---

## 9.2 Archive zones

### `before_archive_zone`
- droppable: true
- allowed_elements:
  - `category-banner`
  - `promo-banner`
  - `intro-text`

### `archive_toolbar_zone`
Semi-locked zone.
Có sẵn filter/sort core.
Có thể cho thêm:
- `trust-strip`
- `custom-note`

### `after_toolbar_zone`
- droppable: true
- allowed_elements:
  - `promo-banner`
  - `trust-strip`
  - `icon-box`

### `before_grid_zone`
- droppable: true
- allowed_elements:
  - `filter-bar`
  - `category-list`
  - `quick-nav`

### `after_grid_zone`
- droppable: true
- allowed_elements:
  - `recently-viewed`
  - `promo-banner`
  - `trust-strip`

### `archive_bottom_zone`
- droppable: true
- allowed_elements:
  - `faq`
  - `brand-story`
  - `newsletter`
  - `seo-text`

---

## 10. Element System

## 10.1 Cấu trúc chuẩn của một element

Mỗi element nên có:

- id
- label
- description
- preview thumbnail
- category
- allowed_contexts
- allowed_layouts (optional)
- allowed_zones
- max_instances
- settings_schema
- defaults
- version

Ví dụ logic:

```php
[
  'id' => 'banner-1',
  'label' => 'Banner Promotion 1',
  'allowed_contexts' => ['master'],
  'allowed_zones' => ['banner_zone'],
  'max_instances' => 1,
  'version' => 1,
]
```

---

## 10.2 Gợi ý danh sách elements

### Header elements
- `banner-1`
- `banner-2`
- `topbar-1`
- `topbar-2`
- `search-icon`
- `account-icon`
- `wishlist-icon`
- `mini-cart`
- `hotline`
- `social`

### Footer elements
- `newsletter`
- `trust-badge`
- `promo-banner`
- `brand-strip`
- `payment-icons`
- `footer-menu`
- `copyright`

### Product elements
- `countdown`
- `trust-badge`
- `shipping-info`
- `payment-icons`
- `stock-notice`
- `faq`
- `testimonial`
- `brand-story`
- `guarantee`
- `related-products`
- `upsell-products`
- `recently-viewed`

### Archive elements
- `category-banner`
- `promo-banner`
- `intro-text`
- `trust-strip`
- `filter-bar`
- `category-list`
- `quick-nav`
- `newsletter`
- `faq`
- `seo-text`

---

## 11. Zone Settings vs Element Settings

Đây là điểm cần tách rõ trong code và UI.

## 11.1 Zone Settings
Là setting của container/zone.

Ví dụ field nên có:
- enable/disable zone
- background preset
- padding preset
- visibility desktop/mobile
- align
- gap
- max_items

## 11.2 Element Settings
Là setting của element trong zone.

Ví dụ:
- text
- style variant
- source
- icon set
- color preset
- visibility

---

## 12. Dữ liệu lưu trữ

## 12.1 Lưu ở đâu
Lưu vào `wp_options`.

Không dùng post meta cho cấu hình global.

### Options gợi ý
- `ds_active_layouts`
- `ds_builder_data`
- `ds_theme_tokens` (optional)

Có thể tách sâu hơn nếu cần:
- `ds_master_builder_data`
- `ds_product_builder_data`
- `ds_archive_builder_data`

---

## 12.2 Active layouts

Ví dụ:

```json
{
  "master": "master-1",
  "product": "product-1",
  "archive": "archive-1"
}
```

---

## 12.3 Builder data structure

Cấu trúc khuyến nghị:

```json
{
  "master": {
    "master-1": {
      "general": {},
      "style": {},
      "zones": {
        "banner_zone": {
          "settings": {},
          "elements": []
        },
        "topbar_zone": {
          "settings": {},
          "elements": []
        }
      }
    }
  },
  "product": {
    "product-1": {
      "zones": {
        "summary_zone": {
          "settings": {},
          "elements": []
        }
      }
    }
  },
  "archive": {
    "archive-1": {
      "zones": {
        "before_archive_zone": {
          "settings": {},
          "elements": []
        }
      }
    }
  }
}
```

### Ghi chú
Builder data nên lưu theo `layout preset id`, để khi đổi preset rồi quay lại preset cũ thì config cũ vẫn còn.

---

## 13. Schema & Validation

## 13.1 Schema-driven system
Cần có schema cho:
- layout preset
- zones
- elements
- settings fields

## 13.2 Validation bắt buộc ở backend
Phải validate khi save:
- zone có tồn tại trong preset không
- zone có `droppable = true` không
- element có nằm trong `allowed_elements` không
- số lượng element có vượt `max_items` không
- field type có hợp lệ không

## 13.3 Merge defaults
Khi load builder:
- lấy schema defaults
- merge với saved config
- thiếu field nào thì fallback default

---

## 14. Design Tokens

Để hệ thống nhìn chuyên nghiệp và đồng nhất, không nên cho admin chỉnh quá nhiều style vi mô ở mọi element.

Nên có global design tokens:
- colors
- spacing scale
- radius scale
- shadow presets
- typography scale

Ví dụ:

```json
{
  "colors": {
    "primary": "#cc0000",
    "text": "#222222",
    "muted": "#666666"
  },
  "spacing": ["xs", "sm", "md", "lg", "xl"],
  "radius": ["none", "soft", "rounded"],
  "shadow": ["none", "sm", "md"]
}
```

Element nên ưu tiên dùng token/preset thay vì cho nhập pixel tự do ở mọi field.

---

## 15. Performance Strategy

Nếu làm đúng, builder này sẽ nhanh hơn page builder rất nhiều.

## 15.1 Nguyên tắc
- frontend chỉ render HTML theo preset + data
- không parse layout tree quá nặng
- không query DB lặp
- admin builder logic không ảnh hưởng frontend runtime

## 15.2 Nên có
- cache config
- pre-render partial HTML header/footer nếu cần
- bundle CSS theo context
- lazy load JS cho feature cần thiết

## 15.3 Tránh
- dùng shortcode cho mọi element
- load CSS/JS lẻ cho từng element quá mức
- render qua REST runtime ở frontend
- lưu HTML thô khó migrate

---

## 16. Versioning & Migration

Cần hỗ trợ version từ đầu.

### Layout preset version
Ví dụ:
- `header-1` version 2
- `product-1` version 1

### Element version
Ví dụ:
- `topbar-2` version 3

Lý do:
- sau này đổi schema
- thêm field mới
- đổi zone id

Cần có migration layer để data cũ không vỡ.

---

## 17. Nâng cấp chuyên nghiệp nên có

## Phase 1 / MVP
- Master Layout builder
- Product Layout builder
- Archive Layout builder
- preview + drag-drop constrained
- element settings
- zone settings cơ bản
- save/reset

## Phase 2
- duplicate element
- duplicate layout config
- draft/publish
- autosave
- device preview refine
- visibility desktop/mobile

## Phase 3
- revisions/history
- display conditions
- reusable elements
- import/export config
- preset packs

---

## 18. Những gì nên khóa

Không nên làm trong phase đầu:
- freeform drag-drop toàn trang
- custom CSS quá sâu cho mọi element
- builder tự sinh zone
- cho edit fixed parts vô hạn
- nested section vô hạn

---

## 19. Hướng code đề xuất

## 19.1 Registry
Nên có registry cho:
- Layout presets
- Zones schema
- Elements

## 19.2 Theme/Plugin responsibility

### Theme
- render preset structure ở frontend
- render fixed parts
- expose zone positions

### Builder Plugin
- admin UI
- drag-drop
- settings forms
- save/load config
- validation

## 19.3 Source of truth
- preset/schema nằm trong code
- database chỉ lưu user config

---

## 20. Kết luận

Đây là một **Preset-based Theme Builder with guided live editing**.

Điểm mạnh của kiến trúc này:
- trực quan hơn settings form
- nhẹ hơn Elementor rất nhiều
- dễ kiểm soát layout
- dễ maintain và mở rộng
- phù hợp WooCommerce theme builder

### Chốt nguyên tắc quan trọng nhất
- Zone được định nghĩa trong từng layout preset
- Builder chỉ đọc và bật drag-drop cho zone hợp lệ
- Preset quyết định structure, builder chỉ là UI thao tác

