<?php

/**
 * WebIT site runtime — generic form-block submission handler.
 *
 * The `form` content block posts here. Each submission is stored locally in
 * form_submissions with synced_up = 0 and a client-generated client_uid;
 * sync-client.php later pushes it to the platform exactly once. After saving we
 * redirect back to the page with ?sent=<form_slug> so the block shows its
 * success message.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/lib/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
if (!webit_is_configured()) {
    http_response_code(503);
    exit('Site not configured.');
}

$slug = trim((string) ($_POST['form_slug'] ?? ''));
if ($slug === '') {
    http_response_code(422);
    exit('Missing form.');
}

// Collect the form fields (named f_<field>) into a clean payload.
$data = [];
foreach ($_POST as $key => $value) {
    if (str_starts_with((string) $key, 'f_')) {
        $data[substr((string) $key, 2)] = is_array($value) ? $value : (string) $value;
    }
}

try {
    $pdo = webit_db(webit_config());
    $stmt = $pdo->prepare(
        'INSERT INTO form_submissions (client_uid, form_slug, payload, synced_up)
         VALUES (?, ?, ?, 0)'
    );
    $stmt->execute([
        bin2hex(random_bytes(16)),
        $slug,
        (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Could not save your submission. Please try again later.');
}

// Redirect back to the originating page (same-origin path only) with a flag.
$ref = (string) ($_SERVER['HTTP_REFERER'] ?? '/');
$path = parse_url($ref, PHP_URL_PATH);
$path = is_string($path) && $path !== '' ? $path : '/';
header('Location: ' . $path . '?sent=' . rawurlencode($slug));
