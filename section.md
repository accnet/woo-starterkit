# Section Shortcode System

## Summary
Thêm một hệ `section` mới, tách biệt với `element`, để render nội dung trong page/post content bằng shortcode `[section ...]`. Mỗi section type là một module filesystem riêng, có `render.php`, `style.css`, `script.js` tương tự `element`, và được đăng ký qua một registry mở rộng thêm type sau này.

V1 chỉ áp dụng cho nội dung đi qua `the_content()` của page/post, không tích hợp vào Theme Builder.

V1 sẽ hỗ trợ 5 type:
- `banner`
- `sliders`
- `product`
- `trust-badges`
- `reviews`

## Architecture
- Thêm service layer mới trong app:
  - `SectionRegistry`: quét thư mục `sections/*/section.json`, load metadata, `render.php`, asset descriptors, defaults.
  - `SectionRenderer`: nhận `type + attributes + content`, merge defaults, include `render.php`, trả HTML.
  - `SectionShortcodeManager`: đăng ký shortcode `section` trên `init`, parse attributes, render qua `SectionRenderer`.
  - `SectionAssetManager`: chịu trách nhiệm resolve asset của section types thực sự xuất hiện trong nội dung trang hiện tại.
- Quy ước frontend:
  - nếu section có behavior slider/carousel thì chuẩn hóa dùng `Swiper`
  - không thêm thư viện slider thứ hai trong hệ `section`
  - section nào không cần slider thì không enqueue asset `Swiper`
- Cấu trúc module mới:
  - `sections/banner/`
  - `sections/sliders/`
  - `sections/product/`
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
  - nếu không có queried post đơn lẻ, đọc `post_content` từ các post trong main query để hỗ trợ `index.php`.
  - parse tất cả shortcode `[section ...]` bằng API WordPress, ưu tiên `get_shortcode_regex()` + `shortcode_parse_atts()`, không dùng regex tự viết.
  - lấy attribute `type`, normalize theo `sanitize_key`.
- Chỉ enqueue asset của section types thực sự có mặt trong content.
- Với section slider:
  - enqueue `Swiper` chỉ khi nội dung trang thực sự có section slider
  - reuse handle `starterkit-swiper` đang có của theme, không register thêm handle/CDN thứ hai
  - JS khởi tạo slider nằm trong `script.js` của section đó, không đặt bootstrap slider tập trung cho mọi section
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

Ví dụ manifest cho slider:

```json
{
  "id": "sliders",
  "label": "Sliders",
  "assets": {
    "css": "style.css",
    "js": "script.js"
  },
  "defaults": {
    "autoplay": 5000,
    "speed": 600,
    "show_pagination": "1",
    "show_navigation": "1"
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
- Nếu section dùng slider:
  - markup phải theo structure mà `Swiper` yêu cầu
  - section tự sở hữu class hook JS riêng, ví dụ `.js-starterkit-section-slider`
  - không phụ thuộc vào inline script trong `render.php`

## Public Shortcode Interface
- `banner`: `[section type="banner" title="Summer Sale" image="https://cdn.example.com/banner.jpg" link="/summer-sale" overlay="1"]...[/section]`
- `sliders`: `[section type="sliders" autoplay="5000" speed="600" show_pagination="1" show_navigation="1"]https://cdn.example.com/banner-1.jpg|Summer Sale|/summer-sale\nhttps://cdn.example.com/banner-2.jpg|New Arrivals|/collections/new[/section]`
- `product`: `[section type="product" limit="8" source="latest" category="" tag="" featured="0" on_sale="0" ids=""]`
- `trust-badges`: `[section type="trust-badges"]30-day returns\nSecure checkout\nFast delivery[/section]`
- `reviews`: `[section type="reviews" limit="3"]`

Rule xử lý shortcode:
- `type` là bắt buộc. Nếu thiếu hoặc không tồn tại trong registry: trả rỗng.
- Shortcode hỗ trợ cả self-closing và enclosing.
- Với section không dùng body:
  - `product`
  - `reviews`
  nếu user viết enclosing form thì body sẽ bị bỏ qua, không báo lỗi.
- Với section có body:
  - `banner`
  - `sliders`
  - `trust-badges`
  nếu dùng self-closing thì render theo body rỗng.

Không thêm attribute `class`, `id`, `style`, filter by category/product, hoặc mode switch trong v1.

## Type Behaviors

### `banner`
- Attribute:
  - `title`: string, default `''`
  - `image`: background image URL, default `''`
  - `link`: banner link URL, default `''`
  - `overlay`: boolean-like string, default `'1'`
- Body:
  - rich text
  - đi qua `do_shortcode()`
  - sau đó `wpautop()`
- Render:
  - wrapper section
  - background image nếu có `image`
  - overlay tối nếu có `image` và `overlay="1"`
  - toàn banner clickable nếu có `link`
  - heading nếu `title` không rỗng
  - content block nếu body không rỗng

### `product`
- Attribute:
  - `limit`: integer, default `8`
  - `columns`: integer, default `4`
  - `source`: string, default `'latest'`
  - `category`: comma-separated product category slugs, default `''`
  - `tag`: comma-separated product tag slugs, default `''`
  - `featured`: boolean-like string, default `'0'`
  - `on_sale`: boolean-like string, default `'0'`
  - `ids`: comma-separated product IDs, default `''`
- Data source:
  - WooCommerce products theo điều kiện shortcode
- Validation:
  - `limit` ép `absint`
  - nếu `limit <= 0` thì fallback về `8`
  - `columns` ép `absint`, clamp trong khoảng `1..6`, nếu không hợp lệ thì fallback `4`
  - `source` chỉ nhận:
    - `latest`
    - `featured`
    - `sale`
    - `ids`
  - giá trị ngoài danh sách sẽ fallback về `latest`
- Render strategy:
  - query sản phẩm theo điều kiện
  - render bằng `wc_get_template_part( 'content', 'product' )`
  - bao ngoài bằng markup grid section riêng nếu cần heading/container
- Condition contract:
  - `source="latest"`:
    - lấy sản phẩm mới nhất theo `date DESC`
  - `source="featured"`:
    - chỉ lấy featured products
  - `source="sale"`:
    - chỉ lấy products đang on-sale
  - `source="ids"`:
    - chỉ lấy danh sách product IDs từ `ids`
    - giữ thứ tự theo `ids` đã truyền vào
    - vẫn cho phép áp thêm `category`, `tag`, `featured="1"`, `on_sale="1"` như bộ lọc thu hẹp phía sau
  - `category`:
    - nếu có giá trị, áp thêm tax query theo product category slug
  - `tag`:
    - nếu có giá trị, áp thêm tax query theo product tag slug
  - `featured="1"`:
    - thêm điều kiện featured, dùng như bộ lọc bổ sung
    - `featured="0"` không có nghĩa phủ định, chỉ là không thêm filter featured
  - `on_sale="1"`:
    - thêm điều kiện on-sale, dùng như bộ lọc bổ sung
    - `on_sale="0"` không có nghĩa phủ định, chỉ là không thêm filter on-sale
  - `source` là điều kiện chính, còn `category`, `tag`, `featured`, `on_sale` là điều kiện phía sau để thu hẹp tập kết quả
- WooCommerce loop contract:
  - phải set loop props cần thiết trước khi render danh sách, tối thiểu `columns`
  - `columns` lấy từ attribute `columns` sau khi sanitize
  - sau khi render xong phải reset product globals/query globals đúng chuẩn WooCommerce/WordPress
- Fail-safe:
  - nếu WooCommerce không active: trả rỗng

### `sliders`
- Mục tiêu:
  - section slider để chuyển ảnh banner bằng `Swiper`
- Attribute:
  - `autoplay`: integer milliseconds, default `5000`
  - `speed`: integer milliseconds, default `600`
  - `show_pagination`: boolean-like string, default `'1'`
  - `show_navigation`: boolean-like string, default `'1'`
- Data source:
  - inner content
- Parse rule:
  - mỗi dòng là một slide
  - format mỗi dòng: `image_url|heading|link_url`
  - `image_url` là bắt buộc
  - `heading` và `link_url` là optional
  - trim từng phần, bỏ dòng rỗng hoặc dòng không có `image_url`
  - bỏ slide nếu `image_url` không phải URL hợp lệ sau sanitize
- Render contract:
  - markup theo structure chuẩn của `Swiper`
  - mỗi slide render ảnh banner full width
  - nếu có `heading` thì render caption overlay
  - nếu có `link_url` thì bọc slide bằng thẻ `a`, nếu không thì render `div`
  - chỉ render pagination/navigation khi attribute tương ứng bật
- Sanitize contract:
  - `image_url` qua sanitize URL trước khi render và output bằng `esc_url`
  - `link_url` nếu có thì sanitize URL và output bằng `esc_url`
  - `heading` sanitize text và output bằng `esc_html`
- JS contract:
  - init `Swiper` trong `sections/sliders/script.js`
  - mỗi instance slider tự init độc lập theo data attributes render từ shortcode
  - không dùng inline script trong `render.php`
- Fallback:
  - nếu chỉ có 1 slide thì không init slider, render static banner
  - nếu `Swiper` không load được thì markup vẫn hiển thị theo chiều dọc, không hỏng layout
- Validation:
  - `autoplay` ép `absint`, nếu `<= 0` thì coi là tắt autoplay
  - `speed` ép `absint`, nếu `<= 0` thì fallback `600`

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
  - review content rút gọn
  - product title kèm link về product
  - review date
- Content handling:
  - dùng `wp_trim_words` để giới hạn nội dung review, default 30 từ
  - output được escape/kses đúng chuẩn
- Validation:
  - `limit` ép `absint`
  - nếu `limit <= 0` thì fallback về `3`
- Fail-safe:
  - nếu WooCommerce không active: trả rỗng
  - nếu review approved nhưng không có rating hợp lệ: bỏ qua item đó

## Validation and Fallbacks
- Nếu manifest thiếu `id` hoặc `label`: bỏ qua section module đó.
- Nếu `render.php` không tồn tại hoặc không readable: section type đó render rỗng.
- Nếu asset khai báo nhưng file không tồn tại: bỏ qua asset đó, không fatal.
- Nếu `type` shortcode không hợp lệ: render rỗng, không warning.
- Nếu content body có nested shortcode:
  - chỉ `banner` cần chạy `do_shortcode()`
  - `sliders` không xử lý nested shortcode, chỉ coi body là plain text cấu hình slide
  - `trust-badges` không xử lý nested shortcode, chỉ coi body là plain text
- Nếu section slider thiếu `Swiper` asset hoặc init fail:
  - markup vẫn phải usable ở dạng stacked/static content
  - không để JS error làm hỏng các section khác trên trang

## Test Plan
- Registry:
  - module hợp lệ trong `sections/*/section.json` được load đúng
  - module thiếu `id` hoặc `label` bị bỏ qua
- Shortcode:
  - `[section type="banner" title="Summer Sale"]Text[/section]` render đúng heading và content
  - `[section type="sliders"]...[/section]` render đúng số slide hợp lệ và init `Swiper` khi có từ 2 slide trở lên
  - `[section type="product" limit="8"]` render tối đa 8 sản phẩm theo điều kiện
  - `[section type="product" source="featured"]` chỉ render featured products
  - `[section type="product" source="sale"]` chỉ render products đang sale
  - `[section type="product" source="ids" ids="12,34,56"]` render đúng thứ tự ID đã truyền
  - `[section type="product" source="latest" category="hoodies"]` chỉ render products thuộc category đó
  - `[section type="trust-badges"]` với 3 dòng text render đúng 3 item
  - `[section type="reviews" limit="3"]` render đúng 3 review mới nhất
  - `[section type="unknown"]` trả rỗng
- Asset loading:
  - trang chỉ có `banner` thì chỉ enqueue asset của `banner`
  - trang có nhiều section type thì enqueue mỗi type đúng một lần
  - trang không có shortcode `section` thì không enqueue asset section
  - trang không có section slider thì không enqueue `Swiper`
  - trang có `sliders` thì enqueue `starterkit-swiper` + asset riêng của `sliders`
- WooCommerce behavior:
  - `product` dùng lại markup product card hiện tại của theme
  - loop globals được reset sau khi render
  - `reviews` chỉ lấy review approved có rating
  - `product` và `reviews` trả rỗng khi WooCommerce tắt
- Content contexts:
  - hoạt động trong `the_content()` của `front-page.php` và `index.php`
  - không phá `wpautop` mặc định của nội dung trang

## Assumptions
- V1 chỉ áp dụng cho page/post content, không tích hợp vào Theme Builder.
- `section` là shortcode duy nhất; không tạo shortcode riêng như `[banner]`, `[reviews]`.
- `sliders` là module slider chuẩn đầu tiên và dùng `Swiper`.
- `product` không có title ở v1.
- `reviews` là feed review toàn site, không lọc theo product/category trong v1.
- `trust-badges` lấy dữ liệu từ inner content từng dòng.
- Theme sẽ thêm thư mục mới `sections/` song song với `elements/` để giữ kiến trúc nhất quán và dễ mở rộng sau này.
