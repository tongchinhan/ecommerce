<?php
/**
 * Admin — Statistics dashboard.
 *
 * All numbers are computed from real SQL aggregates — never hardcoded.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Thống kê';

$period = (string) ($_GET['period'] ?? 'month');
if (!in_array($period, ['today', 'week', 'month', 'year'], true)) {
    $period = 'month';
}

$overview  = admin_statistics_overview();
$periodRow = admin_statistics_revenue_by_period($period);
$best      = admin_statistics_best_sellers(10);
$byCat     = admin_statistics_sales_by_category();
$payment   = admin_statistics_payment_breakdown();

$statusLabels = [
    ORDER_PENDING    => 'Chờ xử lý',
    ORDER_CONFIRMED  => 'Đã xác nhận',
    ORDER_PROCESSING => 'Đang xử lý',
    ORDER_SHIPPED    => 'Đang giao',
    ORDER_DELIVERED  => 'Đã giao',
    ORDER_CANCELLED  => 'Đã hủy',
];

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Thống kê tổng quan</h1>
    <p class="admin-card-sub">Tất cả số liệu đến từ cơ sở dữ liệu MySQL của cửa hàng.</p>

    <div class="stat-row">
        <div class="stat"><span>Doanh thu (chưa hủy)</span><b><?= e(format_vnd($overview['revenue_total'])) ?></b></div>
        <div class="stat"><span>Doanh thu đã thanh toán</span><b><?= e(format_vnd($overview['revenue_paid'])) ?></b></div>
        <div class="stat"><span>Tổng đơn hàng</span><b><?= (int) $overview['orders_total'] ?></b></div>
        <div class="stat"><span>Đơn đã giao</span><b><?= (int) $overview['orders_delivered'] ?></b></div>
    </div>

    <div class="stat-row stat-row--3" style="margin-top:12px;">
        <div class="stat"><span>Khách hàng</span><b><?= (int) $overview['customers_total'] ?></b></div>
        <div class="stat"><span>Sản phẩm (đang hiển thị / tổng)</span><b><?= (int) $overview['products_active'] ?> / <?= (int) $overview['products_total'] ?></b></div>
        <div class="stat"><span>Bài viết (đã xuất bản / tổng)</span><b><?= (int) $overview['posts_published'] ?> / <?= (int) $overview['posts_total'] ?></b></div>
    </div>
</div>

<div class="admin-card">
    <h1>Trạng thái đơn hàng</h1>
    <div class="stat-row stat-row--3">
        <?php foreach ($statusLabels as $st => $label): ?>
            <div class="stat">
                <span><?= e($label) ?></span>
                <b><?= (int) ($overview['orders_' . $st] ?? 0) ?></b>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="admin-card">
    <h1>Doanh thu theo kỳ</h1>
    <div class="admin-toolbar">
        <?php
            $periodLabels = [
                'today' => 'Hôm nay',
                'week'  => '7 ngày qua',
                'month' => '30 ngày qua',
                'year'  => '1 năm qua',
            ];
        ?>
        <?php foreach ($periodLabels as $key => $label): ?>
            <a class="admin-btn <?= $period === $key ? 'admin-btn--primary' : '' ?>" href="<?= e(url('admin/statistics.php?period=' . $key)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="stat-row stat-row--2">
        <div class="stat"><span>Doanh thu (<?= e($periodLabels[$period]) ?>)</span><b><?= e(format_vnd($periodRow['revenue'])) ?></b></div>
        <div class="stat"><span>Số đơn (<?= e($periodLabels[$period]) ?>)</span><b><?= (int) $periodRow['orders'] ?></b></div>
    </div>
</div>

<div class="admin-card">
    <h1>Top 10 sản phẩm bán chạy</h1>
    <p class="admin-card-sub">Tính trên tất cả các đơn không bị hủy.</p>
    <?php if (empty($best)): ?>
        <div class="admin-empty">Chưa có dữ liệu bán hàng.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sản phẩm</th>
                        <th>SKU</th>
                        <th class="col-num">Số lượng bán</th>
                        <th class="col-num">Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($best as $i => $row): ?>
                    <tr>
                        <td class="col-num"><?= $i + 1 ?></td>
                        <td><strong><?= e($row['name']) ?></strong></td>
                        <td><span class="admin-mono"><?= e($row['sku']) ?></span></td>
                        <td class="col-num"><?= (int) $row['sold_qty'] ?></td>
                        <td class="col-num"><strong><?= e(format_vnd($row['revenue'])) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h1>Doanh thu theo danh mục</h1>
    <?php if (empty($byCat)): ?>
        <div class="admin-empty">Chưa có dữ liệu.</div>
    <?php else:
        $maxRev = 0.0;
        foreach ($byCat as $r) { if ((float) $r['revenue'] > $maxRev) { $maxRev = (float) $r['revenue']; } }
    ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Danh mục</th>
                        <th class="col-num">Số lượng bán</th>
                        <th class="col-num">Doanh thu</th>
                        <th style="width:30%;">Tỉ trọng</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($byCat as $r):
                    $pct = $maxRev > 0 ? round(((float) $r['revenue'] / $maxRev) * 100) : 0;
                ?>
                    <tr>
                        <td><strong><?= e($r['name']) ?></strong></td>
                        <td class="col-num"><?= (int) $r['sold_qty'] ?></td>
                        <td class="col-num"><strong><?= e(format_vnd($r['revenue'])) ?></strong></td>
                        <td>
                            <div class="admin-progress">
                                <div class="admin-progress-bar"><span style="width:<?= $pct ?>%;"></span></div>
                                <span class="admin-progress-value"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h1>Thanh toán</h1>
    <?php if (empty($payment)): ?>
        <div class="admin-empty">Chưa có đơn hàng nào.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Trạng thái thanh toán</th>
                        <th>Số đơn</th>
                        <th>Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($payment as $row): ?>
                    <tr>
                        <td><span class="admin-badge admin-badge--<?= admin_payment_badge($row['payment_status']) ?>"><?= e(admin_payment_label($row['payment_status'])) ?></span></td>
                        <td><?= (int) $row['cnt'] ?></td>
                        <td><?= e(format_vnd($row['total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>