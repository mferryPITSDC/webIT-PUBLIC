<?php
/** @var array $page @var array $blocks @var array $nav @var array $config */
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
        main{max-width:860px;margin:2rem auto;padding:0 1.25rem}
        .block{margin:1.5rem 0}
        .block.hero{background:#f3f4f6;padding:2.5rem;border-radius:12px;font-size:1.4rem}
        footer{color:#6b7280;text-align:center;padding:2rem;font-size:.85rem}
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
            <?php $payload = json_decode((string) $b['payload'], true) ?: []; ?>
            <div class="block <?= $e($b['type']) ?>">
                <?= $e($payload['text'] ?? '') ?>
            </div>
        <?php endforeach; ?>
        <?php if ($blocks === []): ?><p>This page has no content yet.</p><?php endif; ?>
    </main>
    <footer>Powered by WebIT · <?= $e($config['site']['name']) ?></footer>
</body>
</html>
