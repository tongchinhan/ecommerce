<?php
/**
 * Admin — Banner CRUD.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Banner';

$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0 ? admin_banner_find($editId) : null;

$errors = [];
$values = [
    'title'      => '',
    'image'      => '',
    'link'       => '',
    'position'   => 'home_hero',
    'sort_order' => 0,
    'is_active'  => 1,
    'starts_at'  => '',
    'ends_at'    => '',
];

if ($editing) {
    $values = [
        'title'      => (string) $editing['title'],
        'image'      => (string) $editing['image'],
        'link'       => (string) ($editing['link'] ?? ''),
        'position'   => (string) $editing['position'],
        'sort_order' => (int) $editing['sort_order'],
        'is_active'  => (int) $editing['is_active'],
        'starts_at'  => $editing['starts_at'] ? date('Y-m-d\TH:i', strtotime((string) $editing['starts_at'])) : '',
        'ends_at'    => $editing['ends_at'] ? date('Y-m-d\TH:i', strtotime((string) $editing['ends_at'])) : '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $result = admin_banner_delete($id);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/admin/banners.php');
    }

    $saveId = (int) ($_POST['id'] ?? 0);
    $currentRecord = $saveId > 0 ? admin_banner_find($saveId) : null;
    $currentImage = $currentRecord ? (string) $currentRecord['image'] : '';
    $imagePath = $currentImage;
    $newImageAbsolute = null;
    $upload = $_FILES['image'] ?? null;
    $hasImageUpload = is_array($upload)
        && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($hasImageUpload) {
        $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        $tmpPath = $upload['tmp_name'] ?? '';
        $fileSize = is_string($tmpPath) && is_file($tmpPath) ? (int) filesize($tmpPath) : 0;
        $mime = '';
        $imageInfo = false;
        if ($uploadError === UPLOAD_ERR_OK && is_string($tmpPath) && $tmpPath !== '' && is_uploaded_file($tmpPath)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpPath) ?: '';
            $imageInfo = @getimagesize($tmpPath);
        }
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors[] = 'Không thể tải ảnh lên. Vui lòng chọn lại tệp.';
        } elseif ($fileSize <= 0 || $fileSize > MAX_UPLOAD_BYTES) {
            $errors[] = 'Ảnh banner phải có dung lượng từ 1 byte đến 5 MB.';
        } elseif (!isset($extensions[$mime]) || $imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
            $errors[] = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WebP hợp lệ.';
        } else {
            try {
                if (!is_dir(UPLOAD_BANNERS) && !mkdir(UPLOAD_BANNERS, 0755, true) && !is_dir(UPLOAD_BANNERS)) {
                    throw new RuntimeException('Upload directory unavailable');
                }
                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                $destination = UPLOAD_BANNERS . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($tmpPath, $destination)) {
                    throw new RuntimeException('Upload move failed');
                }
                $newImageAbsolute = $destination;
                $imagePath = 'banners/' . $filename;
            } catch (Throwable $e) {
                $errors[] = 'Không thể lưu ảnh banner. Vui lòng thử lại.';
            }
        }
    } elseif ($currentImage === '') {
        $errors[] = 'Vui lòng tải lên ảnh banner.';
    }

    $values = [
        'title'      => trim((string) ($_POST['title'] ?? '')),
        'image'      => $imagePath,
        'link'       => trim((string) ($_POST['link'] ?? '')),
        'position'   => trim((string) ($_POST['position'] ?? 'home_hero')),
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'is_active'  => !empty($_POST['is_active']) ? 1 : 0,
        'starts_at'  => (string) ($_POST['starts_at'] ?? ''),
        'ends_at'    => (string) ($_POST['ends_at'] ?? ''),
    ];

    if ($errors === []) {
        $result = admin_banner_save($values, $saveId > 0 ? $saveId : null);
        if ($result['errors'] !== []) {
            $errors = $result['errors'];
            $values['image'] = $currentImage;
            if ($newImageAbsolute !== null && is_file($newImageAbsolute)) {
                @unlink($newImageAbsolute);
            }
            $editId = $saveId;
        } else {
            flash_set('success', $saveId > 0 ? 'Đã cập nhật banner.' : 'Đã tạo banner mới.');
            redirect('/admin/banners.php');
        }
    } else {
        $values['image'] = $currentImage;
        $editId = $saveId;
        if ($newImageAbsolute !== null && is_file($newImageAbsolute)) {
            @unlink($newImageAbsolute);
        }
    }
}

$banners = admin_banners_all();

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Banner</h1>
    <p class="admin-card-sub">Quản lý các banner hiển thị trên trang chủ và các vị trí khác trong cửa hàng.</p>

    <form class="admin-form" method="post" action="" enctype="multipart/form-data" style="margin-bottom:24px;">
        <?= csrf_field() ?>
        <h2><?= $editing ? 'Sửa banner' : 'Tạo banner mới' ?></h2>
        <?php if ($errors !== []): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
        <?php endif; ?>
        <div class="admin-form-row admin-form-row--2">
            <label>
                <strong>Tiêu đề *</strong>
                <input type="text" name="title" value="<?= e($values['title']) ?>" required maxlength="160">
            </label>
            <label>
                <strong>Vị trí</strong>
                <select name="position">
                    <option value="home_hero" <?= $values['position'] === 'home_hero' ? 'selected' : '' ?>>Trang chủ — Hero</option>
                    <option value="home_sub"  <?= $values['position'] === 'home_sub'  ? 'selected' : '' ?>>Trang chủ — phụ</option>
                </select>
            </label>
        </div>
        <div class="admin-form-row admin-form-row--2 admin-banner-upload-row">
            <div class="admin-banner-image-field">
                <label class="admin-banner-upload-dropzone" for="bannerImageInput">
                    <input class="admin-banner-upload-input" type="file" id="bannerImageInput" name="image" accept="image/jpeg,image/png,image/webp" <?= $editing ? '' : 'required' ?> aria-describedby="bannerUploadHelp bannerUploadFilename">
                    
                    <span class="admin-banner-upload-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5"/></svg></span>
                    <strong>Chọn ảnh banner</strong>
                    <span class="admin-banner-upload-help" id="bannerUploadHelp">JPG, PNG hoặc WebP · dung lượng tối đa 5 MB</span>
                    <span class="admin-banner-upload-filename" id="bannerUploadFilename" data-banner-filename aria-live="polite">Chưa chọn tệp</span>
                </label>
                <div class="admin-banner-preview" data-banner-preview>
                    <strong class="admin-banner-preview-label" data-banner-preview-label><?= $values['image'] !== '' ? 'Ảnh hiện tại' : 'Ảnh xem trước' ?></strong>
                    <?php $bannerPreview = storefront_image((string) $values['image']); ?>
                    <?php if ($bannerPreview): ?>
                        <img src="<?= e($bannerPreview) ?>" alt="Ảnh banner hiện tại">
                    <?php else: ?>
                        <span class="admin-muted" data-banner-empty>Chưa có ảnh hiện tại.</span>
                    <?php endif; ?>
                </div>
            </div>
            <label>
                <strong>Liên kết (URL)</strong>
                <input type="text" name="link" value="<?= e($values['link']) ?>" placeholder="vd: products.php?category=phong-khach">
            </label>
        </div>
        <div class="admin-form-row admin-form-row--3">
            <label>
                <strong>Thứ tự</strong>
                <input type="number" name="sort_order" min="0" step="1" value="<?= (int) $values['sort_order'] ?>">
            </label>
            <label>
                <strong>Hiển thị từ</strong>
                <input type="datetime-local" name="starts_at" value="<?= e($values['starts_at']) ?>">
            </label>
            <label>
                <strong>Hiển thị đến</strong>
                <input type="datetime-local" name="ends_at" value="<?= e($values['ends_at']) ?>">
            </label>
        </div>
        <label class="admin-check">
            <input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?>>
            Đang hiển thị
        </label>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn--primary"><?= $editing ? 'Lưu thay đổi' : 'Tạo banner' ?></button>
            <?php if ($editing): ?>
                <a href="<?= e(url('admin/banners.php')) ?>" class="admin-btn admin-btn--ghost">Huỷ</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($banners)): ?>
        <div class="admin-empty">Chưa có banner nào.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Tiêu đề</th>
                        <th>Vị trí</th>
                        <th class="col-num">Thứ tự</th>
                        <th>Hiệu lực</th>
                        <th>Trạng thái</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($banners as $b): ?>
                    <tr>
                        <td class="col-thumb">
                            <?php $img = storefront_image((string) $b['image']); ?>
                            <?php if ($img): ?>
                                <img src="<?= e($img) ?>" alt="">
                            <?php else: ?>
                                <span class="col-thumb-placeholder">—</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= e($b['title']) ?></strong></td>
                        <td><span class="admin-mono"><?= e($b['position']) ?></span></td>
                        <td class="col-num"><?= (int) $b['sort_order'] ?></td>
                        <td>
                            <?php if ($b['starts_at']): ?>
                                <?= e(format_date((string) $b['starts_at'], 'd/m/Y')) ?>
                            <?php endif; ?>
                            <?php if ($b['ends_at']): ?>
                                <div class="secondary admin-mono">→ <?= e(format_date((string) $b['ends_at'], 'd/m/Y')) ?></div>
                            <?php endif; ?>
                            <?php if (!$b['starts_at'] && !$b['ends_at']): ?>
                                <span class="admin-muted">Vô thời hạn</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $b['is_active'] === 1): ?>
                                <span class="admin-badge admin-badge--ok">Hiển thị</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge--mute">Ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <span class="admin-action-row">
                                <a class="admin-btn admin-btn--small admin-btn--secondary" href="<?= e(url('admin/banners.php?edit=' . (int) $b['id'])) ?>">Sửa</a>
                                <form method="post" action="" onsubmit="return confirm('Xóa banner này?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                    <button type="submit" class="admin-btn admin-btn--small admin-btn--danger">Xóa</button>
                                </form>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<script>
(() => {
    const input = document.getElementById('bannerImageInput');
    const preview = document.querySelector('[data-banner-preview]');
    const filename = document.querySelector('[data-banner-filename]');
    const previewLabel = document.querySelector('[data-banner-preview-label]');
    if (!input || !preview || !filename || !previewLabel) return;
    let previewUrl = null;

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) {
            filename.textContent = 'Chưa chọn tệp';
            return;
        }
        filename.textContent = file.name;
        if (!file.type.startsWith('image/')) return;
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);

        let image = preview.querySelector('img');
        if (!image) {
            const emptyState = preview.querySelector('[data-banner-empty]');
            if (emptyState) emptyState.remove();
            image = document.createElement('img');
            image.alt = 'Ảnh banner thay thế đã chọn';
            preview.append(image);
        }
        previewLabel.textContent = 'Ảnh thay thế xem trước';
        image.src = previewUrl;
    });
})();
</script>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>