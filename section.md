# Section Shortcode System

## Summary
Thêm một hệ `section` mới, tách biệt với `element`, để render nội dung trong page/post content bằng shortcode `[section ...]`. Mỗi section type là một module filesystem riêng, có `render.php`, `style.css`, `script.js` tương tự `element`, và được đăng ký qua một registry mở rộng thêm type sau này.

V1 chỉ áp dụng cho nội dung đi qua `the_content()` của page/post, không tích hợp vào Theme Builder.

V1 sẽ hỗ trợ 4 type:
- `banner`
- `product-new`
- `trust-badges`
- `reviews`

## Architecture
- Thêm service layer mới trong app:
  - `SectionRegistry`: quét thư mục `sections/*/section.json`, load metadata, `render.php`, asset descriptors, defaults.
  - `SectionRenderer`: nhận `type + attributes + content`, merge defaults, include `render.php`, trả HTML.
  - `SectionShortcodeManager`: đăng ký shortcode `section` trên `init`, parse attributes, render qua `SectionRenderer`.
  - `SectionAssetManager`: chịu trách nhiệm resolve asset của section types thực sự xuất hiện trong nội dung trang hiện tại.
- Cấu trúc module mới:
  - `sections/banner/`
  - `sections/product-new/`
  - `sections/trust-badges/`
  - `sections/reviews/`
- Mỗi module có tối thiểu:
  - `section.json`
  - `render.php`
- Optional:
  - `style.css`
  - `script.js`

## Asset Loading Strategy
- Không dùng cơ chế “render xong mới biết type rồi enqueue ở `wp_enqueue_scripts`”, vì shortcode trong `the_content()` được chạy sau thời điểm enqueue chuẩn.
- `SectionAssetManager` sẽ quét trước nội dung của post hiện tại để xác định section types được dùng trên request hiện tại.
- Thời điểm quét:
  - trong `wp_enqueue_scripts`, đọc `post_content` của object hiện tại nếu request là singular page/post có `the_content()`.
  - parse tất cả shortcode `[section ...]`, lấy attribute `type`, normalize theo `sanitize_key`.
- Chỉ enqueue asset của section types thực sự có mặt trong content.
- Nếu không xác định được post hiện tại hoặc không có `post_content`, không enqueue asset section nào.
- V1 chỉ cần hỗ trợ flow nội dung chuẩn của `front-page.php` và `index.php`; không cần cover widget, term description, custom AJAX fragment, hay content lấy từ options.

## Module Contract

### `section.json`
Manifest của mỗi section sẽ dùng contract tối thiểu:

```json
{
  "id": "banner",
  "label": "Banner",
  "assets": {
    "css": "style.css",
    "js": "script.js"
  },
  "defaults": {
    "title": "",
    "limit": 8
  }
}
```

Field bắt buộc:
- `id`
- `label`

Field optional:
- `assets`
- `defaults`

Không có `contexts`, `allowed_zones`, `settings`, `category`, `max_instances` trong v1 vì section không dùng cho builder.

### Render Contract
- `SectionRenderer` truyền các biến sau vào `render.php`:
  - `$section`: definition đã normalize
  - `$attributes`: attributes sau khi merge defaults
  - `$content`: inner shortcode content dạng string
  - `$type`: section type đã sanitize
- `render.php` phải tự fail-safe và trả output rỗng nếu thiếu dữ liệu cần thiết.
- Mỗi section wrapper dùng class chung:
  - `.starterkit-section`
  - `.starterkit-section--{type}`

## Public Shortcode Interface
- `banner`: `[section type="banner" title="Summer Sale"]...[/section]`
- `product-new`: `[section type="product-new" limit="8"]`
- `trust-badges`: `[section type="trust-badges"]30-day returns\nSecure checkout\nFast delivery[/section]`
- `reviews`: `[section type="reviews" limit="3"]`

Rule xử lý shortcode:
- `type` là bắt buộc. Nếu thiếu hoặc không tồn tại trong registry: trả rỗng.
- Shortcode hỗ trợ cả self-closing và enclosing.
- Với section không dùng body:
  - `product-new`
  - `reviews`
  nếu user viết enclosing form thì body sẽ bị bỏ qua, không báo lỗi.
- Với section có body:
  - `banner`
  - `trust-badges`
  nếu dùng self-closing thì render theo body rỗng.

Không thêm attribute `class`, `id`, `style`, filter by category/product, hoặc mode switch trong v1.

## Type Behaviors

### `banner`
- Attribute:
  - `title`: string, default `''`
- Body:
  - rich text
  - đi qua `do_shortcode()`
  - sau đó `wpautop()`
- Render:
  - wrapper section
  - heading nếu `title` không rỗng
  - content block nếu body không rỗng

### `product-new`
- Attribute:
  - `limit`: integer, default `8`
- Data source:
  - WooCommerce latest products theo `date DESC`
- Validation:
  - `limit` ép `absint`
  - nếu `limit <= 0` thì fallback về `8`
- Render strategy:
  - query sản phẩm mới nhất
  - render bằng `wc_get_template_part( 'content', 'product' )`
  - bao ngoài bằng markup grid section riêng nếu cần heading/container
- WooCommerce loop contract:
  - phải set loop props cần thiết trước khi render danh sách, tối thiểu `columns`
  - `columns` trong v1 sẽ bằng `min(4, limit)` để tránh grid quá dày khi không có config riêng
  - sau khi render xong phải reset product globals/query globals đúng chuẩn WooCommerce/WordPress
- Fail-safe:
  - nếu WooCommerce không active: trả rỗng

### `trust-badges`
- Data source:
  - inner content
- Parse rule:
  - tách theo dòng bằng `preg_split( '/\r\n|\r|\n/' )`
  - trim từng dòng
  - bỏ dòng rỗng
- Render:
  - danh sách text badge đơn giản
  - không đọc global settings
  - không dùng mặc định từ theme trong v1

### `reviews`
- Attribute:
  - `limit`: integer, default `3`
- Data source:
  - WooCommerce product reviews thật
  - chỉ lấy review approved
  - newest first
- Query contract:
  - dùng WordPress comments API cho comment của product
  - chỉ lấy item có rating hợp lệ
- Render fields:
  - author name
  - rating
  - review content
  - product title kèm link về product
- Content handling:
  - không cắt excerpt trong v1
  - output được escape/kses đúng chuẩn
- Validation:
  - `limit` ép `absint`
  - nếu `limit <= 0` thì fallback về `3`
- Fail-safe:
  - nếu WooCommerce không active: trả rỗng

## Validation and Fallbacks
- Nếu manifest thiếu `id` hoặc `label`: bỏ qua section module đó.
- Nếu `render.php` không tồn tại hoặc không readable: section type đó render rỗng.
- Nếu asset khai báo nhưng file không tồn tại: bỏ qua asset đó, không fatal.
- Nếu `type` shortcode không hợp lệ: render rỗng, không warning.
- Nếu content body có nested shortcode:
  - chỉ `banner` cần chạy `do_shortcode()`
  - `trust-badges` không xử lý nested shortcode, chỉ coi body là plain text

## Test Plan
- Registry:
  - module hợp lệ trong `sections/*/section.json` được load đúng
  - module thiếu `id` hoặc `label` bị bỏ qua
- Shortcode:
  - `[section type="banner" title="Summer Sale"]Text[/section]` render đúng heading và content
  - `[section type="product-new" limit="8"]` render tối đa 8 sản phẩm mới nhất
  - `[section type="trust-badges"]` với 3 dòng text render đúng 3 item
  - `[section type="reviews" limit="3"]` render đúng 3 review mới nhất
  - `[section type="unknown"]` trả rỗng
- Asset loading:
  - trang chỉ có `banner` thì chỉ enqueue asset của `banner`
  - trang có nhiều section type thì enqueue mỗi type đúng một lần
  - trang không có shortcode `section` thì không enqueue asset section
- WooCommerce behavior:
  - `product-new` dùng lại markup product card hiện tại của theme
  - loop globals được reset sau khi render
  - `reviews` chỉ lấy review approved có rating
  - `product-new` và `reviews` trả rỗng khi WooCommerce tắt
- Content contexts:
  - hoạt động trong `the_content()` của `front-page.php` và `index.php`
  - không phá `wpautop` mặc định của nội dung trang

## Assumptions
- V1 chỉ áp dụng cho page/post content, không tích hợp vào Theme Builder.
- `section` là shortcode duy nhất; không tạo shortcode riêng như `[banner]`, `[reviews]`.
- `product-new` không có title ở v1.
- `reviews` là feed review toàn site, không lọc theo product/category trong v1.
- `trust-badges` lấy dữ liệu từ inner content từng dòng.
- Theme sẽ thêm thư mục mới `sections/` song song với `elements/` để giữ kiến trúc nhất quán và dễ mở rộng sau này.
