<?php
/**
 * Section wrapper with eyebrow, heading, optional subtext, and slot.
 *
 * Usage:
 *   hp_section([
 *       'eyebrow'  => 'New arrivals',
 *       'heading'  => 'Thoughtfully chosen for everyday living',
 *       'subtext'  => 'Each piece selected for craft, material, and longevity.',
 *       'link'     => url('products.php'),
 *       'link_text'=> 'Shop all',
 *       'id'       => 'featured',
 *   ], function() {
 *       // echo product cards here
 *   });
 */
declare(strict_types=1);

function hp_section(array $opts, callable $slot): void
{
    $id = !empty($opts['id']) ? ' id="' . e($opts['id']) . '"' : '';
    $eyebrow = !empty($opts['eyebrow']) ? '<p class="section-eyebrow">' . e($opts['eyebrow']) . '</p>' : '';
    $heading = !empty($opts['heading']) ? '<h2 class="section-heading">' . e($opts['heading']) . '</h2>' : '';
    $subtext = !empty($opts['subtext']) ? '<p class="section-subtext">' . e($opts['subtext']) . '</p>' : '';
    $linkText = !empty($opts['link_text']) ? e($opts['link_text']) : 'View all';
    $link = !empty($opts['link']) ? '<a class="section-link" href="' . e($opts['link']) . '">' . $linkText . ' ' . icon('arrow-right', 'icon icon--xs') . '</a>' : '';
    ?>
<section class="home-section"<?= $id ?>>
    <div class="container">
        <?php if ($eyebrow || $heading || $subtext || $link): ?>
        <header class="section-header">
            <div class="section-header-text">
                <?= $eyebrow ?>
                <?= $heading ?>
                <?= $subtext ?>
            </div>
            <?php if ($link): ?>
                <div class="section-header-action"><?= $link ?></div>
            <?php endif; ?>
        </header>
        <?php endif; ?>
        <div class="section-content">
            <?php $slot(); ?>
        </div>
    </div>
</section>
    <?php
}
