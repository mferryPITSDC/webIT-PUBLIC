<?php
/** @var array $page @var array $blocks @var array $nav @var array $config @var array $products */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($page['title']) ?> · <?= $e($config['site']['name']) ?></title>
    <meta name="description" content="<?= $e($page['meta_desc'] ?? '') ?>">
    <style>
        body{font-family:system-ui,sans-serif;margin:0;color:#1f2937}
        header{background:#111827;color:#fff;padding:1rem 1.5rem;display:flex;gap:1.5rem;align-items:center}
        header .name{font-weight:700}
        header nav a{color:#cbd5e1;text-decoration:none;margin-right:1rem}
        main{max-width:900px;margin:2rem auto;padding:0 1.25rem}
        footer{color:#6b7280;text-align:center;padding:2rem;font-size:.85rem}
        /* Typed content blocks */
        .blk{margin:1.75rem 0}
        .blk-hero{background:#1f2937;color:#fff;background-size:cover;background-position:center;
            border-radius:14px;padding:3rem 2rem;text-align:center}
        .blk-hero h2{font-size:2.1rem;margin:0 0 .5rem}.blk-hero p{font-size:1.1rem;opacity:.95;margin:0 0 1rem}
        .blk-btn{display:inline-block;background:#2563eb;color:#fff;padding:.6rem 1.2rem;border-radius:8px;
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
        .blk-card span{color:#2563eb;font-weight:700}.blk-card small{color:#6b7280}
    </style>
</head>
<body>
    <header>
        <span class="name"><?= $e($config['site']['name']) ?></span>
        <nav>
            <?php foreach ($nav as $n): ?>
                <a href="/<?= $e($n['slug']) ?>"><?= $e($n['title']) ?></a>
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
    <footer>Powered by WebIT · <?= $e($config['site']['name']) ?></footer>
</body>
</html>
