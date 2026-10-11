<?php
/**
 * NORD HOME — Category landing page.
 *
 * Resolves the category by slug and renders the same shop listing
 * scoped to that category. This reuses the products.php logic so the
 * shop and category pages share search/sort/price/pagination behavior.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/shop-data.php';
require_once __DIR__ . '/includes/components/product-card.php';

$slugRaw = $_GET['slug'] ?? '';

/* If a category id was passed (e.g. from product-detail breadcrumbs),
   accept it too. */
$idRaw = $_GET['id'] ?? '';

$category = shop_resolve_category($slugRaw, $idRaw);

if ($category === null) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy danh mục';
    $bodyClass = 'page-shop';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container shop-empty" role="status" style="padding: 80px 0;">
        <h1 class="shop-empty-heading">Không tìm thấy danh mục</h1>
        <p class="shop-empty-text">Danh mục bạn yêu cầu không tồn tại hoặc đã ngừng hoạt động.</p>
        <div class="shop-empty-actions">
            <a class="btn btn--primary" href="<?= e(url('products.php')) ?>">Xem tất cả sản phẩm</a>
            <a class="btn btn--ghost" href="<?= e(url()) ?>">Về trang chủ</a>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* Resolve other filters (same as products.php) */
$queryRaw = $_GET['q'] ?? '';
$minRaw   = $_GET['min_price'] ?? '';
$maxRaw   = $_GET['max_price'] ?? '';
$sortRaw  = $_GET['sort'] ?? '';
$pageRaw  = $_GET['page'] ?? '';

$query = shop_resolve_query($queryRaw);
$sort  = shop_resolve_sort($sortRaw);
$price = shop_resolve_price_filter($minRaw, $maxRaw);
$page  = shop_resolve_page($pageRaw);
$perPage = shop_resolve_per_page();

$queryArgs = [
    'query'       => $query,
    'category_id' => $category['id'],
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

$pageTitle = $category['name'] . ' — Cửa hàng';
$pageDescription = 'Khám phá ' . $category['name'] . ' tại NORD HOME.';
$bodyClass = 'page-shop';
require __DIR__ . '/includes/header.php';

/* For links/pagination, use products.php + category=slug so URL is canonical */
$_GET['category'] = $category['slug'];
unset($_GET['id'], $_GET['slug']);
?>

<!-- Breadcrumb -->
<nav class="breadcrumb container" aria-label="Đường dẫn">
    <ol class="breadcrumb-list">
        <li><a href="<?= e(url()) ?>">Trang chủ</a></li>
        <li><a href="<?= e(url('products.php')) ?>">Cửa hàng</a></li>
        <li aria-current="page"><?= e($category['name']) ?></li>
    </ol>
</nav>

<section class="shop-header container">
    <header class="shop-header-inner">
        <h1 class="shop-heading"><?= e($category['name']) ?></h1>
        <p class="shop-description">Khám phá các sản phẩm trong danh mục <?= e($category['name']) ?> tại NORD HOME.</p>
    </header>
</section>

<section class="shop-content container">
    <div class="shop-layout">

        <aside class="shop-sidebar" aria-label="Bộ lọc sản phẩm">
            <form method="get" action="<?= e(url('products.php')) ?>" class="shop-filter-form" id="shopFilterForm">
                <input type="hidden" name="category" value="<?= e($category['slug']) ?>">
                <?php if ($query !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($query) ?>">
                <?php endif; ?>

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
                                   placeholder="—" inputmode="numeric">
                        </label>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="btn btn--primary" type="submit">Áp dụng</button>
                    <a class="btn btn--ghost" href="<?= e(url('products.php?category=' . urlencode($category['slug']))) ?>">Đặt lại</a>
                </div>
            </form>
        </aside>

        <div class="shop-main">
            <div class="shop-toolbar">
                <div class="shop-count"><?= e((string) $totalProducts) ?> sản phẩm</div>

                <form method="get" action="<?= e(url('products.php')) ?>" class="shop-sort">
                    <input type="hidden" name="category" value="<?= e($category['slug']) ?>">
                    <?php if ($query !== ''): ?>
                        <input type="hidden" name="q" value="<?= e($query) ?>">
                    <?php endif; ?>
                    <?php if ($price['min'] !== null): ?>
                        <input type="hidden" name="min_price" value="<?= e((string) (int) $price['min']) ?>">
                    <?php endif; ?>
                    <?php if ($price['max'] !== null): ?>
                        <input type="hidden" name="max_price" value="<?= e((string) (int) $price['max']) ?>">
                    <?php endif; ?>
                    <label class="shop-sort-label" for="shopSortSelectCat">
                        <span>Sắp xếp</span>
                        <select id="shopSortSelectCat" name="sort" class="shop-sort-select" onchange="this.form.submit()">
                            <?php foreach (shop_sort_labels() as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            </div>

            <?php if (!empty($products)): ?>
                <div class="product-grid product-grid--shop">
                    <?php foreach ($products as $product): ?>
                        <?php render_product_card($product); ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Phân trang">
                        <ul class="pagination-list">
                            <li class="pagination-item">
                                <?php if ($page > 1): ?>
                                    <a class="pagination-link pagination-link--prev" href="<?= e(shop_pagination_url($page - 1)) ?>">← Trước</a>
                                <?php else: ?>
                                    <span class="pagination-link pagination-link--disabled">← Trước</span>
                                <?php endif; ?>
                            </li>
                            <?php
                            $windowStart = max(1, $page - 2);
                            $windowEnd   = min($totalPages, $page + 2);
                            if ($windowStart > 1): ?>
                                <li class="pagination-item"><a class="pagination-link" href="<?= e(shop_pagination_url(1)) ?>">1</a></li>
                                <?php if ($windowStart > 2): ?>
                                    <li class="pagination-item pagination-item--ellipsis">…</li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
                                <li class="pagination-item">
                                    <?php if ($i === $page): ?>
                                        <span class="pagination-link pagination-link--current"><?= e((string) $i) ?></span>
                                    <?php else: ?>
                                        <a class="pagination-link" href="<?= e(shop_pagination_url($i)) ?>"><?= e((string) $i) ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endfor; ?>
                            <?php if ($windowEnd < $totalPages): ?>
                                <?php if ($windowEnd < $totalPages - 1): ?>
                                    <li class="pagination-item pagination-item--ellipsis">…</li>
                                <?php endif; ?>
                                <li class="pagination-item"><a class="pagination-link" href="<?= e(shop_pagination_url($totalPages)) ?>"><?= e((string) $totalPages) ?></a></li>
                            <?php endif; ?>
                            <li class="pagination-item">
                                <?php if ($page < $totalPages): ?>
                                    <a class="pagination-link pagination-link--next" href="<?= e(shop_pagination_url($page + 1)) ?>">Sau →</a>
                                <?php else: ?>
                                    <span class="pagination-link pagination-link--disabled">Sau →</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="shop-empty">
                    <div class="shop-empty-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    </div>
                    <h2 class="shop-empty-heading">Chưa có sản phẩm trong danh mục này</h2>
                    <p class="shop-empty-text">Danh mục <?= e($category['name']) ?> hiện chưa có sản phẩm. Hãy khám phá các danh mục khác.</p>
                    <div class="shop-empty-actions">
                        <a class="btn btn--primary" href="<?= e(url('products.php')) ?>">Xem tất cả sản phẩm</a>
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

    bucketRadios.forEach(function (r) {
        r.addEventListener('change', function () {
            if (!minInput || !maxInput) return;
            minInput.value = r.getAttribute('data-min') || '';
            maxInput.value = r.getAttribute('data-max') || '';
        });
    });

    function clearBuckets() {
        bucketRadios.forEach(function (r) { r.checked = false; });
    }
    if (minInput) minInput.addEventListener('input', clearBuckets);
    if (maxInput) maxInput.addEventListener('input', clearBuckets);

    /* Auto-submit on any radio with data-autosubmit="true" (e.g. price
       buckets on the category page). Category selection on /products.php
       also uses this mechanism. */
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
