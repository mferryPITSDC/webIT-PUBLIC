<?php

declare(strict_types=1);

/**
 * WebIT site runtime — configuration loader and DB helper.
 *
 * This is the SAME for every site. It merges the shared base configuration
 * (config.sample.php, or an optional host-specific config.php override) with
 * the live credentials written at pairing time (storage/site.local.php, which
 * is gitignored). Every runtime entry point — index.php, setup.php,
 * sync-client.php, subscribe.php — loads its settings through here.
 */

/** Absolute path to the runtime root (the directory that holds /lib, /public). */
function webit_root(): string
{
    return dirname(__DIR__);
}

/** Path to the gitignored, host-specific credentials written at pairing time. */
function webit_local_path(): string
{
    return webit_root() . '/storage/site.local.php';
}

/**
 * Deep-merged runtime configuration. The shared base provides defaults; the
 * paired, host-specific values in storage/site.local.php override them.
 *
 * @return array<string,mixed>
 */
function webit_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $root = webit_root();
    // A host may drop a config.php to override the shared sample defaults;
    // otherwise the committed config.sample.php is the base for every site.
    $base = is_file($root . '/config.php')
        ? require $root . '/config.php'
        : require $root . '/config.sample.php';

    $local = is_file(webit_local_path()) ? require webit_local_path() : [];

    $config = webit_merge(
        is_array($base) ? $base : [],
        is_array($local) ? $local : []
    );

    return $config;
}

/**
 * Recursively merge $override onto $base. Nested arrays are merged; scalar
 * values from $override win.
 *
 * @param array<string,mixed> $base
 * @param array<string,mixed> $override
 * @return array<string,mixed>
 */
function webit_merge(array $base, array $override): array
{
    foreach ($override as $key => $value) {
        if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
            $base[$key] = webit_merge($base[$key], $value);
        } else {
            $base[$key] = $value;
        }
    }

    return $base;
}

/**
 * True once the site has been paired — i.e. storage/site.local.php exists and
 * carries API credentials. setup.php gates on this so it only runs once.
 */
function webit_is_configured(): bool
{
    if (!is_file(webit_local_path())) {
        return false;
    }

    $config = webit_config();

    return ($config['api']['key'] ?? '') !== ''
        && ($config['api']['secret'] ?? '') !== '';
}

/**
 * Persist live, host-specific settings (credentials, DB details, bound site
 * identity) to storage/site.local.php as a returnable PHP array. Never commit
 * this file — it is gitignored.
 *
 * @param array<string,mixed> $data
 */
function webit_write_local(array $data): void
{
    $path = webit_local_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $php = "<?php\n\n"
        . "// WebIT runtime — live credentials written by setup.php at pairing time.\n"
        . "// Host-specific and secret: never commit this file.\n\n"
        . 'return ' . var_export($data, true) . ";\n";

    file_put_contents($path, $php, LOCK_EX);
}

/**
 * Open the local content database. Throws on failure so callers can surface a
 * friendly "re-run setup" message (see public/index.php).
 *
 * @param array<string,mixed> $config
 */
function webit_db(array $config): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = $config['db'] ?? [];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $db['host'] ?? '127.0.0.1',
        (int) ($db['port'] ?? 3306),
        $db['name'] ?? ''
    );

    $pdo = new PDO($dsn, (string) ($db['user'] ?? ''), (string) ($db['pass'] ?? ''), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
