<?php

/**
 * WebIT site runtime — portable site front controller.
 * Renders published pages from the local read-only content database.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/lib/config.php';
require dirname(__DIR__) . '/lib/BlockRenderer.php';

// First launch: send the operator to the pairing/setup wizard.
if (!webit_is_configured()) {
    header('Location: /setup.php');
    exit;
}

$config = webit_config();
try {
    $pdo = webit_db($config);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Site database is not reachable. Re-run setup.php.');
}

// Resolve the requested slug (default to the first published page).
$path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

if ($path === '') {
    $page = $pdo->query("SELECT * FROM pages WHERE status = 'published' ORDER BY position, id LIMIT 1")->fetch();
} else {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$path]);
    $page = $stmt->fetch();
}

if ($page === false || $page === null) {
    http_response_code(404);
    $page = ['id' => 0, 'title' => 'Not found', 'meta_desc' => ''];
    $blocks = [];
} else {
    $bs = $pdo->prepare('SELECT * FROM content_blocks WHERE page_id = ? ORDER BY position');
    $bs->execute([$page['id']]);
    $blocks = $bs->fetchAll();
}

$nav = $pdo->query("SELECT slug, title FROM pages WHERE status = 'published' ORDER BY position, id")->fetchAll();

// Active products power any product_grid blocks on the page.
$products = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY name")->fetchAll();

// Branding (settings) and primary navigation menu, if the site defined them.
$settings = [];
foreach ($pdo->query('SELECT `key`, `value` FROM settings')->fetchAll() as $row) {
    $settings[$row['key']] = $row['value'];
}

// Prefer a menu named "main"; fall back to any menu. Empty -> nav from pages.
$menuItems = [];
$menu = $pdo->query("SELECT id FROM menus ORDER BY (slug = 'main') DESC, id LIMIT 1")->fetch();
if ($menu !== false) {
    $mi = $pdo->prepare('SELECT label, url FROM menu_items WHERE menu_id = ? ORDER BY position, id');
    $mi->execute([$menu['id']]);
    $menuItems = $mi->fetchAll();
}

require dirname(__DIR__) . '/templates/layout.php';
