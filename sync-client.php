<?php

/**
 * WebIT site runtime — sync agent (run from cron, e.g. every 5 minutes).
 *
 *   php sync-client.php down   pull content from the platform into local DB
 *   php sync-client.php up      push new orders/subscribers/submissions up
 *   php sync-client.php         do both
 *
 * Talks to the platform over signed HTTPS only. The local DB stays private.
 */

declare(strict_types=1);

require __DIR__ . '/lib/ApiClient.php';
require __DIR__ . '/lib/config.php';

if (!webit_is_configured()) {
    fwrite(STDERR, 'Site is not paired yet. Open /setup.php in a browser first.' . PHP_EOL);
    exit(1);
}

$config = webit_config();
$pdo = webit_db($config);

$api = new ApiClient($config['api']['base'], $config['api']['key'], $config['api']['secret']);

$mode = $argv[1] ?? 'both';
$contentTables = ['settings', 'pages', 'content_blocks', 'menus', 'menu_items', 'media', 'products'];
$txTables = ['orders', 'subscribers', 'form_submissions'];

/** Replace local content tables with the platform snapshot (read-only copy). */
function pullContent(PDO $pdo, ApiClient $api, array $contentTables): void
{
    $res = $api->get('/api/v1/content');
    $tables = $res['tables'] ?? [];
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($contentTables as $table) {
        $rows = $tables[$table] ?? [];
        $pdo->exec("DELETE FROM `{$table}`");
        foreach ($rows as $row) {
            $cols = array_keys($row);
            $sql = "INSERT INTO `{$table}` (`" . implode('`,`', $cols) . '`) VALUES ('
                . implode(',', array_fill(0, count($cols), '?')) . ')';
            $pdo->prepare($sql)->execute(array_values($row));
        }
        fwrite(STDOUT, "down: {$table} <- " . count($rows) . " rows" . PHP_EOL);
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

/** Push unsynced transactional rows up, then mark them synced locally. */
function pushTransactions(PDO $pdo, ApiClient $api, array $txTables): void
{
    $payload = [];
    $idMap = [];
    foreach ($txTables as $table) {
        $rows = $pdo->query("SELECT * FROM `{$table}` WHERE synced_up = 0")->fetchAll();
        $payload[$table] = $rows;
        $idMap[$table] = array_column($rows, 'id');
    }
    if (array_sum(array_map('count', $idMap)) === 0) {
        fwrite(STDOUT, 'up: nothing to send' . PHP_EOL);
        return;
    }
    $api->post('/api/v1/transactions', $payload);
    foreach ($idMap as $table => $ids) {
        if ($ids === []) {
            continue;
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE `{$table}` SET synced_up = 1 WHERE id IN ({$ph})")->execute($ids);
        fwrite(STDOUT, "up: {$table} -> " . count($ids) . " rows" . PHP_EOL);
    }
}

try {
    if ($mode === 'down' || $mode === 'both') {
        pullContent($pdo, $api, $contentTables);
    }
    if ($mode === 'up' || $mode === 'both') {
        pushTransactions($pdo, $api, $txTables);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Sync failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
