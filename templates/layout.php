<?php
/**
 * @var array $page @var array $blocks @var array $nav @var array $config
 * @var array $products @var array $settings @var array $menuItems
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$settings = $settings ?? [];
$menuItems = $menuItems ?? [];
$siteName = ($settings['site_title'] ?? '') !== '' ? $settings['site_title'] : $config['site']['name'];
$logo = $settings['logo_url'] ?? '';
$brand = ($settings['primary_color'] ?? '') !== '' ? $settings['primary_color'] : '#2563eb';
$footer = ($settings['footer_text'] ?? '') !== '' ? $settings['footer_text'] : 'Powered by WebIT · ' . $siteName;
// Menu drives the nav when defined; otherwise fall back to published pages.
$navLinks = $menuItems !== []
    ? array_map(static fn ($i) => ['url' => $i['url'], 'label' => $i['label']], $menuItems)
    : array_map(static fn ($n) => ['url' => '/' . $n['slug'], 'label' => $n['title']], $nav);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($page['title']) ?> · <?= $e($siteName) ?></title>
    <meta name="description" content="<?= $e($page['meta_desc'] ?? '') ?>">
    <style>
        :root{--brand:<?= $e($brand) ?>;}
        body{font-family:system-ui,sans-serif;margin:0;color:#1f2937}
        header{background:#111827;color:#fff;padding:1rem 1.5rem;display:flex;gap:1.5rem;align-items:center}
        header .name{font-weight:700;display:flex;align-items:center;gap:.6rem}
        header .name img{height:28px;display:block}
        header nav a{color:#cbd5e1;text-decoration:none;margin-right:1rem}
        main{max-width:900px;margin:2rem auto;padding:0 1.25rem}
        footer{color:#6b7280;text-align:center;padding:2rem;font-size:.85rem}
        /* Typed content blocks */
        .blk{margin:1.75rem 0}
        .blk-hero{background:#1f2937;color:#fff;background-size:cover;background-position:center;
            border-radius:14px;padding:3rem 2rem;text-align:center}
        .blk-hero h2{font-size:2.1rem;margin:0 0 .5rem}.blk-hero p{font-size:1.1rem;opacity:.95;margin:0 0 1rem}
        .blk-btn{display:inline-block;background:var(--brand);color:#fff;padding:.6rem 1.2rem;border-radius:8px;
            border:0;text-decoration:none;cursor:pointer;font:inherit}
        .blk-text{line-height:1.65}.blk-text h2{font-size:1.4rem}
        .blk-image img{max-width:100%;border-radius:10px;display:block}
        .blk-image figcaption,.blk-gallery figcaption{color:#6b7280;font-size:.85rem;margin-top:.4rem}
        .blk-grid{display:grid;gap:.75rem;grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}
        .blk-gallery img{width:100%;height:130px;object-fit:cover;border-radius:8px}
        .blk-form{max-width:480px}
        .blk-form label{display:block;font-size:.85rem;color:#6b7280;margin:.6rem 0 .2rem}
        .blk-form input,.blk-form textarea{width:100%;padding:.55rem .6rem;border:1px solid #e5e7eb;
            border-radius:7px;font:inherit;box-sizing:border-box;margin-bottom:.3rem}
        .blk-ok{background:#dcfce7;color:#166534;padding:.7rem 1rem;border-radius:8px}
        .blk-card{background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:1rem;
            display:flex;flex-direction:column;gap:.35rem}
        .blk-card span{color:var(--brand);font-weight:700}.blk-card small{color:#6b7280}
    </style>
</head>
<body>
    <header>
        <span class="name">
            <?php if ($logo !== ''): ?><img src="<?= $e($logo) ?>" alt="<?= $e($siteName) ?>"><?php endif; ?>
            <?= $e($siteName) ?>
        </span>
        <nav>
            <?php foreach ($navLinks as $link): ?>
                <a href="<?= $e($link['url']) ?>"><?= $e($link['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    </header>
    <main>
        <h1><?= $e($page['title']) ?></h1>
        <?php foreach ($blocks as $b): ?>
            <?= BlockRenderer::render($b, $products ?? []) ?>
        <?php endforeach; ?>
        <?php if ($blocks === []): ?><p>This page has no content yet.</p><?php endif; ?>
    </main>
    <footer><?= $e($footer) ?></footer>
</body>
</html>
