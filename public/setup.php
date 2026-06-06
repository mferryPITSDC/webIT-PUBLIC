<?php

/**
 * WebIT site runtime — first-launch setup / pairing wizard.
 *
 * Collects the local database details and a one-time registration code, then
 * pairs with the platform (POST /api/v1/claim) to receive API credentials.
 * Everything is written to storage/site.local.php (gitignored). Once paired,
 * this page refuses to run again.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/lib/config.php';

$config = webit_config();
$root = dirname(__DIR__);
$errors = [];
$done = false;

if (webit_is_configured()) {
    http_response_code(409);
    exit('This site is already set up. To re-pair, remove storage/site.local.php and request a new code.');
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $db = [
        'host' => trim((string) ($_POST['db_host'] ?? '127.0.0.1')),
        'port' => (int) ($_POST['db_port'] ?? 3306),
        'name' => trim((string) ($_POST['db_name'] ?? '')),
        'user' => trim((string) ($_POST['db_user'] ?? '')),
        'pass' => (string) ($_POST['db_pass'] ?? ''),
    ];
    $code = trim((string) ($_POST['code'] ?? ''));

    // 1) Validate the local database connection (and load schema if empty).
    $pdo = null;
    if ($db['name'] === '' || $db['user'] === '') {
        $errors[] = 'Database name and user are required.';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
                $db['user'],
                $db['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $has = $pdo->query("SHOW TABLES LIKE 'pages'")->fetchColumn();
            if ($has === false && is_file($root . '/schema.sql')) {
                $pdo->exec((string) file_get_contents($root . '/schema.sql'));
            }
        } catch (Throwable $ex) {
            $errors[] = 'Database connection failed: ' . $ex->getMessage();
        }
    }

    // 2) Redeem the registration code with the platform.
    if ($errors === []) {
        $base = rtrim((string) $config['api']['base'], '/');
        $ch = curl_init($base . '/api/v1/claim');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['code' => $code]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $resp = json_decode((string) $raw, true);
        if ($raw === false) {
            $errors[] = 'Could not reach the platform: ' . $err;
        } elseif ($status !== 200 || !is_array($resp) || ($resp['ok'] ?? false) !== true) {
            $errors[] = 'Pairing failed: ' . ($resp['error'] ?? "HTTP {$status}");
        } else {
            // 3) Persist credentials + DB settings + bound identity, then finish.
            $bound = is_array($resp['site'] ?? null) ? $resp['site'] : [];
            webit_write_local([
                'api' => [
                    'base'   => $resp['api_base'] ?: $config['api']['base'],
                    'key'    => $resp['api_key'],
                    'secret' => $resp['api_secret'],
                ],
                'db' => $db,
                'site' => [
                    'id'            => (int) ($bound['id'] ?? $resp['site_id'] ?? 0),
                    'name'          => (string) ($bound['name'] ?? 'My Website'),
                    'public_domain' => (string) ($bound['public_domain'] ?? ''),
                    'reseller'      => [
                        'id'   => (int) ($bound['reseller_id'] ?? 0),
                        'name' => (string) ($bound['reseller_name'] ?? ''),
                    ],
                ],
            ]);
            $done = true;
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set up <?= $e($config['site']['name']) ?></title>
    <style>
        body{font-family:system-ui,sans-serif;background:#f5f6f8;color:#1f2937;margin:0}
        .card{max-width:520px;margin:6vh auto;background:#fff;padding:2rem;border-radius:12px;
            border:1px solid #e5e7eb;box-shadow:0 10px 30px rgba(0,0,0,.06)}
        h1{font-size:1.4rem;margin:0 0 .25rem} p.sub{color:#6b7280;margin:.25rem 0 1.25rem}
        label{display:block;font-size:.85rem;color:#6b7280;margin:.7rem 0 .2rem}
        input{width:100%;padding:.55rem .6rem;border:1px solid #e5e7eb;border-radius:7px;font:inherit;box-sizing:border-box}
        .code{font-size:1.4rem;letter-spacing:.3em;text-transform:uppercase;text-align:center}
        .btn{margin-top:1.2rem;width:100%;background:#2563eb;color:#fff;border:0;padding:.65rem;border-radius:8px;font:inherit;cursor:pointer}
        .err{background:#fee2e2;color:#991b1b;padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem}
        .ok{background:#dcfce7;color:#166534;padding:.7rem 1rem;border-radius:8px}
        .row{display:flex;gap:.6rem}.row>div{flex:1}
    </style>
</head>
<body>
    <div class="card">
        <h1>Set up <?= $e($config['site']['name']) ?></h1>
        <p class="sub">Connect this site to the platform with your one-time registration code.</p>

        <?php if ($done): ?>
            <div class="ok">
                <strong>Paired!</strong> Credentials saved. Now pull your content:
                <code>php sync-client.php down</code>, then add the cron job.
            </div>
        <?php else: ?>
            <?php foreach ($errors as $msg): ?><div class="err"><?= $e($msg) ?></div><?php endforeach; ?>
            <form method="post">
                <label>Registration code</label>
                <input class="code" name="code" maxlength="8" placeholder="ABC123" required autofocus>
                <div class="row">
                    <div><label>DB host</label><input name="db_host" value="127.0.0.1"></div>
                    <div><label>DB port</label><input name="db_port" value="3306"></div>
                </div>
                <label>Database name</label><input name="db_name" required>
                <div class="row">
                    <div><label>DB user</label><input name="db_user" required></div>
                    <div><label>DB password</label><input name="db_pass" type="password"></div>
                </div>
                <button class="btn" type="submit">Pair &amp; finish setup</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
