<?php
/**
 * NORD HOME — Shop / product listing page.
 *
 * URL: products.php
 * Filters (query string, all optional):
 *   ?q=...             Search text
 *   ?category=slug     Category slug
 *   ?category_id=N     Category id (alternative to slug)
 *   ?min_price=N       Min price (VND)
 *   ?max_price=N       Max price (VND)
 *   ?sort=...          default|newest|price_asc|price_desc|name_asc|name_desc
 *   ?page=N            Page number (default 1)
 *
 * All inputs are validated. ORDER BY is whitelist-mapped. LIMIT/OFFSET are integer-bound.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/shop-data.php';
require_once __DIR__ . '/includes/cart-data.php';
require_once __DIR__ . '/includes/components/product-card.php';

/* ---------- Handle add-to-cart POST from product cards ----------
 *
 * Reuses the exact same cart mechanism as product.php:
 *   1. csrf_verify()        — same CSRF helper, same session token
 *   2. cart_add_item()      — single source of truth for the cart algorithm
 *
 * On success: flash a Vietnamese message and redirect back to the same
 * Shop URL (preserving filters). On failure: flash an error and redirect.
 * No cart code is duplicated — the helper is the only writer.
 */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && (string) $_POST['action'] === 'add_to_cart') {
    if (!csrf_verify()) {
        flash_set('error', 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.');
    } else {
        $productId = isset($_POST['product_id']) && is_string($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $qty       = isset($_POST['quantity'])   && is_string($_POST['quantity'])   ? (int) $_POST['quantity']   : 1;

        $result = cart_add_item($productId, $qty);
        if (!empty($result['ok'])) {
            flash_set('success', 'Đã thêm sản phẩm vào giỏ hàng.');
        } else {
            $reason = (string) ($result['reason'] ?? '');
            $msg = match ($reason) {
                'out_of_stock'      => 'Sản phẩm hiện đang tạm hết hàng.',
                'product_not_found' => 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.',
                'cart_unavailable'  => 'Không thể kết nối tới giỏ hàng. Vui lòng thử lại sau.',
                'invalid_product'   => 'Mã sản phẩm không hợp lệ.',
                default             => 'Đã có lỗi xảy ra. Vui lòng thử lại.',
            };
            flash_set('error', $msg);
        }
    }

    /* Redirect back to the same Shop page the user was on, preserving all
       current filters (category, q, sort, price, page). Falls back to the
       plain Shop URL if the Referer is missing or from a different origin. */
    $back = $_SERVER['HTTP_REFERER'] ?? '';
    if (is_string($back) && $back !== '' && (strpos($back, url('products.php')) === 0 || strpos($back, url('category.php')) === 0)) {
        redirect($back);
    } else {
        redirect('products.php');
    }
}

/* ---------- Read & validate query parameters ---------- */

$queryRaw   = $_GET['q'] ?? '';
$slugRaw    = $_GET['category'] ?? '';
$catIdRaw   = $_GET['category_id'] ?? '';
$minRaw     = $_GET['min_price'] ?? '';
$maxRaw     = $_GET['max_price'] ?? '';
$sortRaw    = $_GET['sort'] ?? '';
$pageRaw    = $_GET['page'] ?? '';

$query   = shop_resolve_query($queryRaw);
$sort    = shop_resolve_sort($sortRaw);
$price   = shop_resolve_price_filter($minRaw, $maxRaw);
$page    = shop_resolve_page($pageRaw);
$perPage = shop_resolve_per_page();

$category = shop_resolve_category($slugRaw, $catIdRaw);

/* If a category slug was provided but invalid, the slug input is dropped so
   the rest of the page renders cleanly. The category filter then becomes empty. */
if ($slugRaw !== '' && $category === null && ctype_digit($catIdRaw) === false) {
    // invalid slug → drop the input so links don't repeat the bad value
    $_GET = array_diff_key($_GET, ['category' => 1]);
}

/* ---------- Build query args and load results ---------- */

$queryArgs = [
    'query'       => $query,
    'category_id' => $category['id'] ?? null,
    'min_price'   => $price['min'],
    'max_price'   => $price['max'],
    'sort'        => $sort,
];

$totalProducts = shop_count_products($queryArgs);

$totalPages = $perPage > 0 ? (int) max(1, ceil($totalProducts / $perPage)) : 1;
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$queryArgs['limit']  = $perPage;
$queryArgs['offset'] = $offset;

$products = shop_query_products($queryArgs);

/* ---------- Sidebar categories (for filter UI) ---------- */

$categories = shop_active_categories();

/* ---------- SEO & header ---------- */

if ($query !== '') {
    $pageTitle = 'Cửa hàng — Kết quả tìm kiếm';
    $pageDescription = 'Kết quả tìm kiếm cho "' . $query . '" trong cửa hàng NORD HOME.';
} elseif ($category !== null) {
    $pageTitle = $category['name'] . ' — Cửa hàng';
    $pageDescription = 'Khám phá ' . $category['name'] . ' tại NORD HOME.';
} else {
    $pageTitle = 'Cửa hàng';
    $pageDescription = 'Khám phá bộ sưu tập sản phẩm được tuyển chọn cho không gian sống hiện đại tại NORD HOME.';
}

$bodyClass = 'page-shop';
$pageCss = 'shop.css';
require __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb container" aria-label="Đường dẫn">
    <ol class="breadcrumb-list">
        <li><a href="<?= e(url()) ?>">Trang chủ</a></li>
        <li aria-current="page">Cửa hàng</li>
    </ol>
</nav>

<!-- Page header -->
<section class="shop-header container">
    <header class="shop-header-inner">
        <h1 class="shop-heading">
            <?php if ($query !== ''): ?>
                Kết quả tìm kiếm cho: &ldquo;<?= e($query) ?>&rdquo;
            <?php elseif ($category !== null): ?>
                <?= e($category['name']) ?>
            <?php else: ?>
                Cửa hàng
            <?php endif; ?>
        </h1>
        <p class="shop-description">
            <?php if ($query !== ''): ?>
                Đang hiển thị các sản phẩm phù hợp với từ khóa của bạn.
            <?php else: ?>
                Khám phá bộ sưu tập sản phẩm được tuyển chọn cho không gian sống hiện đại.
            <?php endif; ?>
        </p>
    </header>
</section>

<!-- Shop content -->
<section class="shop-content container">
    <div class="shop-layout">

        <!-- Sidebar (filters) -->
        <aside class="shop-sidebar" aria-label="Bộ lọc sản phẩm">
            <form method="get" action="<?= e(url('products.php')) ?>" class="shop-filter-form" id="shopFilterForm">
                <!-- Preserve search query across filter form submits -->
                <?php if ($query !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($query) ?>">
                <?php endif; ?>

                <!-- Category filter -->
                <div class="filter-block">
                    <h3 class="filter-heading">Danh mục</h3>
                    <ul class="filter-list filter-list--category">
                        <li>
                            <label class="filter-radio">
                                <input type="radio" name="category" value="" data-autosubmit="true" <?= $category === null ? 'checked' : '' ?>>
                                <span>Tất cả danh mục</span>
                            </label>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <label class="filter-radio">
                                    <input type="radio" name="category" value="<?= e($cat['slug']) ?>"
                                        data-autosubmit="true"
                                        data-category-id="<?= e((string) (int) $cat['id']) ?>"
                                        <?= ($category !== null && (int) $category['id'] === (int) $cat['id']) ? 'checked' : '' ?>>
                                    <span><?= e($cat['name']) ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Price filter -->
                <div class="filter-block">
                    <h3 class="filter-heading">Khoảng giá</h3>
                    <ul class="filter-list filter-list--price">
                        <?php
                        $buckets = shop_price_buckets();
                        $selectedMin = $price['min'] !== null ? (float) $price['min'] : null;
                        $selectedMax = $price['max'] !== null ? (float) $price['max'] : null;
                        foreach ($buckets as $i => $bucket):
                            $bMin = $bucket['min'];
                            $bMax = $bucket['max'];
                            $isAll = ($bMin === null && $bMax === null);
                            $isMatch = $isAll
                                ? ($selectedMin === null && $selectedMax === null)
                                : ($selectedMin === (float) $bMin && $selectedMax === (float) $bMax);
                        ?>
                            <li>
                                <label class="filter-radio">
                                    <input type="radio"
                                           name="price_bucket"
                                           value="<?= e((string) $i) ?>"
                                           data-min="<?= e((string) ($bMin ?? '')) ?>"
                                           data-max="<?= e((string) ($bMax ?? '')) ?>"
                                           data-autosubmit="true"
                                           <?= $isMatch ? 'checked' : '' ?>>
                                    <span><?= e($bucket['label']) ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <!-- Real numeric fields driven by JS / direct submission -->
                    <div class="filter-price-custom">
                        <label class="filter-price-input">
                            <span>Tối thiểu</span>
                            <input type="number" min="0" step="1000" name="min_price"
                                   value="<?= e($price['min'] !== null ? (string) (int) $price['min'] : '') ?>"
                                   placeholder="0" inputmode="numeric">
                        </label>
                        <label class="filter-price-input">
                            <span>Tối đa</span>
                            <input type="number" min="0" step="1000" name="max_price"
                                   value="<?= e($price['max'] !== null ? (string) (int) $price['max'] : '') ?>"
                                   placeholder="—">
                        </label>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="btn btn--primary" type="submit">Áp dụng</button>
                    <a class="btn btn--ghost" href="<?= e(url('products.php')) ?>">Đặt lại</a>
                </div>
            </form>
        </aside>

        <!-- Main column -->
        <div class="shop-main">

            <!-- Toolbar -->
            <div class="shop-toolbar">
                <div class="shop-count">
                    <?php if ($totalProducts > 0): ?>
                        <?= e((string) $totalProducts) ?> sản phẩm
                    <?php else: ?>
                        0 sản phẩm
                    <?php endif; ?>
                </div>

                <form method="get" action="<?= e(url('products.php')) ?>" class="shop-sort">
                    <!-- Preserve filters when changing sort -->
                    <?php if ($query !== ''): ?>
                        <input type="hidden" name="q" value="<?= e($query) ?>">
                    <?php endif; ?>
                    <?php if ($category !== null): ?>
                        <input type="hidden" name="category" value="<?= e($category['slug']) ?>">
                    <?php endif; ?>
                    <?php if ($price['min'] !== null): ?>
                        <input type="hidden" name="min_price" value="<?= e((string) (int) $price['min']) ?>">
                    <?php endif; ?>
                    <?php if ($price['max'] !== null): ?>
                        <input type="hidden" name="max_price" value="<?= e((string) (int) $price['max']) ?>">
                    <?php endif; ?>

                    <label class="shop-sort-label" for="shopSortSelect">
                        <span>Sắp xếp</span>
                        <select id="shopSortSelect" name="sort" class="shop-sort-select" onchange="this.form.submit()">
                            <?php foreach (shop_sort_labels() as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            </div>

            <!-- Results -->
            <?php if (!empty($products)): ?>
                <div class="product-grid product-grid--shop">
                    <?php foreach ($products as $product): ?>
                        <?php render_product_card($product); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Phân trang">
                        <ul class="pagination-list">
                            <li class="pagination-item">
                                <?php if ($page > 1): ?>
                                    <a class="pagination-link pagination-link--prev" href="<?= e(shop_pagination_url($page - 1)) ?>" aria-label="Trang trước">
                                        <span aria-hidden="true">←</span> Trước
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-link pagination-link--disabled" aria-disabled="true">← Trước</span>
                                <?php endif; ?>
                            </li>

                            <?php
                            $windowStart = max(1, $page - 2);
                            $windowEnd   = min($totalPages, $page + 2);
                            if ($windowStart > 1): ?>
                                <li class="pagination-item"><a class="pagination-link" href="<?= e(shop_pagination_url(1)) ?>">1</a></li>
                                <?php if ($windowStart > 2): ?>
                                    <li class="pagination-item pagination-item--ellipsis" aria-hidden="true">…</li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
                                <li class="pagination-item">
                                    <?php if ($i === $page): ?>
                                        <span class="pagination-link pagination-link--current" aria-current="page"><?= e((string) $i) ?></span>
                                    <?php else: ?>
                                        <a class="pagination-link" href="<?= e(shop_pagination_url($i)) ?>"><?= e((string) $i) ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endfor; ?>

                            <?php if ($windowEnd < $totalPages): ?>
                                <?php if ($windowEnd < $totalPages - 1): ?>
                                    <li class="pagination-item pagination-item--ellipsis" aria-hidden="true">…</li>
                                <?php endif; ?>
                                <li class="pagination-item"><a class="pagination-link" href="<?= e(shop_pagination_url($totalPages)) ?>"><?= e((string) $totalPages) ?></a></li>
                            <?php endif; ?>

                            <li class="pagination-item">
                                <?php if ($page < $totalPages): ?>
                                    <a class="pagination-link pagination-link--next" href="<?= e(shop_pagination_url($page + 1)) ?>" aria-label="Trang sau">
                                        Sau <span aria-hidden="true">→</span>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-link pagination-link--disabled" aria-disabled="true">Sau →</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty state -->
                <div class="shop-empty" role="status">
                    <div class="shop-empty-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    </div>
                    <?php if ($query !== ''): ?>
                        <h2 class="shop-empty-heading">Không tìm thấy sản phẩm</h2>
                        <p class="shop-empty-text">Không có kết quả nào phù hợp với từ khóa &ldquo;<?= e($query) ?>&rdquo;. Thử thay đổi từ khóa hoặc bộ lọc để tìm sản phẩm phù hợp.</p>
                    <?php elseif ($category !== null): ?>
                        <h2 class="shop-empty-heading">Chưa có sản phẩm trong danh mục này</h2>
                        <p class="shop-empty-text">Danh mục <?= e($category['name']) ?> hiện chưa có sản phẩm nào. Hãy quay lại sau hoặc khám phá các danh mục khác.</p>
                    <?php else: ?>
                        <h2 class="shop-empty-heading">Hiện chưa có sản phẩm</h2>
                        <p class="shop-empty-text">Chúng tôi đang cập nhật sản phẩm mới. Vui lòng quay lại sau.</p>
                    <?php endif; ?>
                    <div class="shop-empty-actions">
                        <a class="btn btn--primary" href="<?= e(url('products.php')) ?>">Xem tất cả sản phẩm</a>
                        <?php if ($query !== '' || $category !== null || $price['min'] !== null || $price['max'] !== null): ?>
                            <a class="btn btn--ghost" href="<?= e(url()) ?>">Về trang chủ</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
/* ----- Shop sidebar: radio auto-submit + price bucket mirroring ----- */
(function () {
    var form = document.getElementById('shopFilterForm');
    if (!form) return;

    var minInput = form.querySelector('input[name="min_price"]');
    var maxInput = form.querySelector('input[name="max_price"]');
    var bucketRadios = form.querySelectorAll('input[name="price_bucket"]');

    /* When a price bucket is selected, mirror its numeric range into the
       min/max inputs so the form submits the correct min_price / max_price. */
    bucketRadios.forEach(function (r) {
        r.addEventListener('change', function () {
            if (!minInput || !maxInput) return;
            minInput.value = r.getAttribute('data-min') || '';
            maxInput.value = r.getAttribute('data-max') || '';
        });
    });

    /* If the user types a custom numeric price, deselect any price bucket so
       the bucket UI does not contradict the numeric inputs. */
    function clearBuckets() {
        bucketRadios.forEach(function (r) { r.checked = false; });
    }
    if (minInput) minInput.addEventListener('input', clearBuckets);
    if (maxInput) maxInput.addEventListener('input', clearBuckets);

    /* Auto-submit the form whenever any radio with data-autosubmit="true" is
       chosen. This is what makes the filter actually navigate: clicking
       "Phòng ngủ" updates the URL to ?category=phong-ngu without an extra
       click on "Áp dụng". We also reset `page` to 1 so the user lands on the
       first page of the new result. */
    var autoRadios = form.querySelectorAll('input[data-autosubmit="true"]');
    autoRadios.forEach(function (r) {
        r.addEventListener('change', function () {
            var hiddenPage = form.querySelector('input[name="page"]');
            if (!hiddenPage) {
                hiddenPage = document.createElement('input');
                hiddenPage.type = 'hidden';
                hiddenPage.name = 'page';
                form.appendChild(hiddenPage);
            }
            hiddenPage.value = '1';
            form.submit();
        });
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
