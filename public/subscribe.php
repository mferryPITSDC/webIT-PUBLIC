<?php

/**
 * Example upward-write endpoint: capture a newsletter signup locally.
 * The row is stored with synced_up = 0 and a client-generated client_uid;
 * sync-client.php later pushes it to the platform exactly once.
 *
 * POST email=...&name=...
 */

declare(strict_types=1);

require dirname(__DIR__) . '/lib/config.php';

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if ($email === false || $email === null) {
    http_response_code(422);
    exit('A valid email is required.');
}

$pdo = webit_db(webit_config());
if ($pdo === null) {
    http_response_code(503);
    exit('Site not configured.');
}

$uid = bin2hex(random_bytes(16));
$stmt = $pdo->prepare(
    'INSERT INTO subscribers (client_uid, email, name, source, synced_up)
     VALUES (?, ?, ?, ?, 0)
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);
$stmt->execute([$uid, $email, (string) ($_POST['name'] ?? ''), 'website']);

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'message' => 'Subscribed']);
