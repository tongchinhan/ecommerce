<?php
/**
 * Inline SVG icon library. Echoes a single <svg> with the requested id.
 *
 * Usage:  echo icon('search', 'icon icon--md');
 *        <button><?= icon('heart') ?> Wishlist</button>
 *
 * Icons inherit color via currentColor and have a 1em width/height baseline.
 */
declare(strict_types=1);

function icon(string $name, string $class = 'icon'): string
{
    $attrs = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"';
    $inner = match ($name) {
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'heart'    => '<path d="M20.4 4.6a5.5 5.5 0 0 0-7.8 0L12 5.2l-.6-.6a5.5 5.5 0 0 0-7.8 7.8l.6.6L12 20.8l7.8-7.8.6-.6a5.5 5.5 0 0 0 0-7.8z"/>',
        'heart-outline' => '<path d="M12 20.8 4.2 13a5.5 5.5 0 0 1 7.8-7.8l.6.6.6-.6a5.5 5.5 0 0 1 7.8 7.8z"/>',
        'bag'      => '<path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'arrow-left'  => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
        'check'    => '<path d="m5 12 5 5 9-11"/>',
        'star'     => '<path d="m12 3 2.7 5.7 6.3.8-4.7 4.4 1.2 6.2L12 17l-5.5 3.1L7.7 13.9 3 9.5l6.3-.8z"/>',
        'leaf'     => '<path d="M20 4c0 9-6 16-16 16 0-9 6-16 16-16z"/><path d="M4 20c4-6 8-10 14-13"/>',
        'shield'   => '<path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/>',
        'truck'    => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'chat'     => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12z"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'phone'    => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8 9.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2z"/>',
        'pin'      => '<path d="M12 22s7-7 7-12a7 7 0 1 0-14 0c0 5 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'minus'    => '<path d="M5 12h14"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'package'  => '<path d="m3 7 9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
        'receipt'  => '<path d="M5 3h14v18l-3-2-3 2-3-2-3 2-2-2V3z"/><path d="M9 8h6M9 12h6M9 16h4"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0"/><circle cx="17" cy="9" r="3"/><path d="M16 14a5 5 0 0 1 6 6"/>',
        'document' => '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/><path d="M9 13h7M9 17h5"/>',
        'logout'   => '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 17l-5-5 5-5"/><path d="M15 12H5"/>',
        'edit'     => '<path d="M4 20h4l10-10-4-4L4 16v4z"/><path d="m14 6 4 4"/>',
        'eye'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'trash'    => '<path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6 7v13h12V7"/><path d="M10 11v6M14 11v6"/>',
        'filter'   => '<path d="M3 5h18l-7 9v6l-4-2v-4z"/>',
        'reset'    => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>',
        default    => '',
    };

    return '<svg class="' . e($class) . '" ' . $attrs . '>' . $inner . '</svg>';
}
