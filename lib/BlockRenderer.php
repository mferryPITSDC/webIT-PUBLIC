<?php

declare(strict_types=1);

/**
 * Renders typed content blocks to HTML on the live site.
 *
 * This is the runtime counterpart to the platform builder's block schema: it
 * reads the same content_blocks.payload shapes the builder writes (and that the
 * builder preview renders), so what a customer sees in the portal preview is
 * what ships here. Everything is escaped; forms post to /form.php.
 *
 * Block types: hero, text, image, gallery, form, product_grid.
 */
final class BlockRenderer
{
    private static function e(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param array<string,mixed> $block   a content_blocks row (payload JSON or array)
     * @param array<int,array<string,mixed>> $products  active products, for product_grid
     */
    public static function render(array $block, array $products = []): string
    {
        $type = (string) ($block['type'] ?? '');
        $p = $block['payload'] ?? [];
        if (is_string($p)) {
            $p = json_decode($p, true) ?: [];
        }

        return match ($type) {
            'hero'         => self::hero($p),
            'text'         => self::text($p),
            'image'        => self::image($p),
            'gallery'      => self::gallery($p),
            'form'         => self::form($p),
            'product_grid' => self::productGrid($p, $products),
            default        => '',
        };
    }

    private static function hero(array $p): string
    {
        $style = !empty($p['image'])
            ? ' style="background-image:linear-gradient(rgba(0,0,0,.35),rgba(0,0,0,.35)),url(\'' . self::e($p['image']) . '\')"'
            : '';
        $h = self::e($p['heading'] ?? '');
        $sub = !empty($p['subheading']) ? '<p>' . nl2br(self::e($p['subheading'])) . '</p>' : '';
        $cta = !empty($p['cta_label']) && !empty($p['cta_url'])
            ? '<a class="blk-btn" href="' . self::e($p['cta_url']) . '">' . self::e($p['cta_label']) . '</a>'
            : '';
        return "<section class=\"blk blk-hero\"{$style}><div><h2>{$h}</h2>{$sub}{$cta}</div></section>";
    }

    private static function text(array $p): string
    {
        $h = !empty($p['heading']) ? '<h2>' . self::e($p['heading']) . '</h2>' : '';
        // Fall back to the legacy single-text payload shape.
        $body = nl2br(self::e($p['body'] ?? $p['text'] ?? ''));
        return "<div class=\"blk blk-text\">{$h}<p>{$body}</p></div>";
    }

    private static function image(array $p): string
    {
        if (empty($p['url'])) {
            return '';
        }
        $cap = !empty($p['caption']) ? '<figcaption>' . self::e($p['caption']) . '</figcaption>' : '';
        return '<figure class="blk blk-image"><img src="' . self::e($p['url']) . '" alt="' . self::e($p['alt'] ?? '') . '">' . $cap . '</figure>';
    }

    private static function gallery(array $p): string
    {
        $imgs = '';
        foreach ((array) ($p['images'] ?? []) as $src) {
            $imgs .= '<img src="' . self::e($src) . '" alt="">';
        }
        $cap = !empty($p['caption']) ? '<figcaption>' . self::e($p['caption']) . '</figcaption>' : '';
        return $imgs === '' ? '' : "<figure class=\"blk blk-gallery\"><div class=\"blk-grid\">{$imgs}</div>{$cap}</figure>";
    }

    private static function form(array $p): string
    {
        $slug = (string) ($p['form_slug'] ?? 'form');
        // Show a success banner after a redirect from form.php (?sent=<slug>).
        if (($_GET['sent'] ?? '') === $slug && $slug !== '') {
            $msg = $p['success_message'] ?? 'Thanks — your message has been sent.';
            return '<div class="blk blk-form"><p class="blk-ok">' . self::e($msg) . '</p></div>';
        }

        $intro = !empty($p['intro']) ? '<p>' . nl2br(self::e($p['intro'])) . '</p>' : '';
        $controls = '';
        foreach ((array) ($p['fields'] ?? []) as $field) {
            $label = self::e($field['label'] ?? '');
            $name = self::e($field['name'] ?? '');
            $required = !empty($field['required']) ? ' required' : '';
            $control = ($field['type'] ?? 'text') === 'textarea'
                ? '<textarea name="f_' . $name . '" rows="4"' . $required . '></textarea>'
                : '<input type="' . self::e($field['type'] ?? 'text') . '" name="f_' . $name . '"' . $required . '>';
            $controls .= "<label>{$label}</label>{$control}";
        }
        $submit = self::e($p['submit_label'] ?? 'Send');
        return '<div class="blk blk-form">' . $intro
            . '<form method="post" action="/form.php">'
            . '<input type="hidden" name="form_slug" value="' . self::e($slug) . '">'
            . $controls
            . '<button class="blk-btn" type="submit">' . $submit . '</button>'
            . '</form></div>';
    }

    /** @param array<int,array<string,mixed>> $products */
    private static function productGrid(array $p, array $products): string
    {
        $skus = (array) ($p['skus'] ?? []);
        $limit = (int) ($p['limit'] ?? 0);
        $chosen = array_filter(
            $products,
            static fn (array $pr): bool => ($skus === [] || in_array($pr['sku'] ?? '', $skus, true))
                && ($pr['status'] ?? 'active') === 'active'
        );
        if ($limit > 0) {
            $chosen = array_slice($chosen, 0, $limit);
        }
        $heading = !empty($p['heading']) ? '<h2>' . self::e($p['heading']) . '</h2>' : '';
        if ($chosen === []) {
            return "<div class=\"blk blk-products\">{$heading}</div>";
        }
        $cards = '';
        foreach ($chosen as $pr) {
            $desc = !empty($pr['description']) ? '<small>' . self::e($pr['description']) . '</small>' : '';
            $cards .= '<div class="blk-card"><strong>' . self::e($pr['name'] ?? '') . '</strong>'
                . '<span>$' . self::e(number_format((float) ($pr['price'] ?? 0), 2)) . '</span>' . $desc . '</div>';
        }
        return "<div class=\"blk blk-products\">{$heading}<div class=\"blk-grid\">{$cards}</div></div>";
    }
}
