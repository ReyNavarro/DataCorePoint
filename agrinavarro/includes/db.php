<?php

/**
 * Supabase PostgreSQL connection.
 * Prefer environment variables for deployment. A Git-ignored local config
 * keeps existing XAMPP installations working without committing credentials.
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $localConfigPath = __DIR__ . '/db.local.php';
    $localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
    if (!is_array($localConfig)) {
        $localConfig = [];
    }

    $host = getenv('SUPABASE_DB_HOST') ?: ($localConfig['host'] ?? '');
    $port = getenv('SUPABASE_DB_PORT') ?: ($localConfig['port'] ?? '5432');
    $dbname = getenv('SUPABASE_DB_NAME') ?: ($localConfig['dbname'] ?? 'postgres');
    $user = getenv('SUPABASE_DB_USER') ?: ($localConfig['user'] ?? '');
    $password = getenv('SUPABASE_DB_PASSWORD') ?: ($localConfig['password'] ?? '');
    $sslmode = getenv('SUPABASE_DB_SSLMODE') ?: ($localConfig['sslmode'] ?? 'require');

    if ($host === '' || $user === '' || $password === '') {
        throw new RuntimeException('Database configuration is missing. Set the SUPABASE_DB_* environment variables or create includes/db.local.php.');
    }

    if (!in_array($sslmode, ['require', 'verify-ca', 'verify-full'], true)) {
        throw new RuntimeException('Database SSL mode must require an encrypted connection.');
    }

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $host, $port, $dbname, $sslmode);
    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('Supabase PostgreSQL connection failed: ' . $e->getMessage());
        throw new RuntimeException('Could not connect to the project database. Check the local database configuration.');
    }

    return $pdo;
}

/** Check for legacy columns while supporting a normalized-schema migration. */
function hasDBColumn(string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (!array_key_exists($key, $cache)) {
        $query = getDB()->prepare(
            'SELECT EXISTS (
                SELECT 1 FROM information_schema.columns
                WHERE table_schema = ? AND table_name = ? AND column_name = ?
            )'
        );
        $query->execute(['public', $table, $column]);
        $cache[$key] = (bool)$query->fetchColumn();
    }

    return $cache[$key];
}
