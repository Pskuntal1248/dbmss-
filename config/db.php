<?php
/**
 * Database Connection — PDO (MySQL)
 * Online Music Shop — DBMS College Project
 *
 * Edit HOST, DBNAME, USER, PASS to match your XAMPP setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'online_music_shop');
define('DB_USER', 'root');        // default XAMPP user
define('DB_PASS', '');            // default XAMPP password (empty)
define('DB_CHAR', 'utf8mb4');

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, never expose raw error messages
            die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
                 <h2>Database Connection Failed</h2>
                 <p>' . htmlspecialchars($e->getMessage()) . '</p>
                 <p>Check your XAMPP MySQL service and <code>config/db.php</code> credentials.</p>
                 </div>');
        }
    }
    return $pdo;
}
