# Kế Hoạch: Tối Ưu Hiệu Năng Render Frontend Theme

**Summary**
- Ưu tiên frontend thật cho khách truy cập, không tối ưu riêng Theme Builder preview trong đợt này.
- Mục tiêu: giảm TTFB nhẹ, giảm CSS/JS không cần thiết, giảm request ngoài, và tránh normalize/render dữ liệu builder quá rộng so với page hiện tại.
- Giữ nguyên behavior hiện tại của layout, WooCommerce, cart drawer và Theme Builder.

**Key Changes**
- Cache runtime trong request:
  - `GlobalSettingsManager::all()` memoize option `starterkit_global_settings` để tránh `get_option()` lặp lại.
  - `BuilderStateRepository::all()` memoize normalized builder state trong cùng request.
  - `LayoutResolver::resolve()` memoize layout theo type: `header`, `footer`, `product`, `archive`.
  - Thêm helper asset version có static cache để tránh nhiều `filemtime()` cho cùng path.

- Scope element assets theo page hiện tại:
  - `ElementAssetManager` không quét toàn bộ builder state nữa.
  - Chỉ enqueue element assets cho active preset của current context:
    - Luôn include active Master header/footer zones.
    - Product page chỉ include active product layout zones.
    - Product archive/shop chỉ include active archive layout zones.
  - Bỏ assets của product/archive elements khi đang ở homepage hoặc page thường.

- Tối ưu asset frontend:
  - Giữ enqueue layout active hiện tại, nhưng dùng cached asset version.
  - Cart drawer CSS/JS hiện đang khoảng `51KB` chưa minify; chỉ enqueue khi WooCommerce tồn tại và cart drawer thực sự bật/được dùng.
  - Product layout 1 chỉ load Swiper khi product gallery cần slider; nếu ít ảnh thì dùng static gallery và không load Swiper CDN.
  - Cân nhắc vendored/local Swiper hoặc fallback local để tránh phụ thuộc CDN ở frontend.

- Tối ưu font:
  - Google Fonts hiện là external request; giữ preconnect nhưng thêm option hoặc default để dùng system fonts/local fonts nếu merchant chọn.
  - Không preload font khi chưa có local font file; tránh preload rỗng như hiện tại.

- Giữ render PHP gọn:
  - Header/footer đọc settings một lần ở đầu template, truyền biến xuống markup.
  - Với footer-1 column settings, tính danh sách visible columns một lần rồi loop render, không lặp condition rời rạc.
  - Không thêm query mới trong template nếu có thể lấy từ settings/bootstrap đã có.

**Interfaces**
- Không thay public frontend markup bắt buộc.
- Thêm hoặc điều chỉnh internal methods:
  - `GlobalSettingsManager::all()` cache nội bộ.
  - `BuilderStateRepository::all()` cache nội bộ và clear sau `save_state()`.
  - `LayoutResolver::resolve()` cache nội bộ.
  - `ElementAssetManager` nhận thêm `PresetSchemaRegistry`, `LayoutResolver`, `PageContextResolver` nếu cần để xác định active contexts.
  - `AssetManager::asset_version($path)` hoặc helper tương đương.

**Test Plan**
- PHP lint các class đã sửa.
- Test frontend:
  - Homepage chỉ load theme.css, active header/footer CSS/JS, cart drawer nếu bật; không load product/archive layout assets.
  - Product page load active product layout assets và Swiper chỉ khi cần.
  - Shop/archive load archive layout assets, không load product single gallery script.
  - Header/footer builder elements vẫn có CSS/JS khi được dùng trong Master zones.
  - Cart/checkout vẫn load đúng assets riêng.
- Dùng Query Monitor hoặc Network tab so sánh trước/sau:
  - Số request CSS/JS.
  - Assets không còn bị enqueue sai context.
  - Không có PHP warning do cache stale trong cùng request.

**Assumptions**
- Ưu tiên tối ưu không cần build pipeline/minify ở bước đầu.
- Không thay đổi visual output.
- Không tắt cart drawer mặc định, chỉ làm điều kiện enqueue/render rõ hơn nếu setting hoặc WooCommerce context cho phép.