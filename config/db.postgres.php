<?php
/**
 * Database Connection — PDO (PostgreSQL for Render)
 */

// Render provides DATABASE_URL automatically
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Parse Render's DATABASE_URL: postgres://user:pass@host:port/dbname
    $db = parse_url($database_url);
    define('DB_HOST', $db['host']);
    define('DB_NAME', ltrim($db['path'], '/'));
    define('DB_USER', $db['user']);
    define('DB_PASS', $db['pass']);
    define('DB_PORT', $db['port'] ?? 5432);
} else {
    // Fallback for local development
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'online_music_shop');
    define('DB_USER', 'postgres');
    define('DB_PASS', 'postgres');
    define('DB_PORT', 5432);
}

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
                 <h2>Database Connection Failed</h2>
                 <p>' . htmlspecialchars($e->getMessage()) . '</p>
                 </div>');
        }
    }
    return $pdo;
}
