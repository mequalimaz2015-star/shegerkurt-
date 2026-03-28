<?php
// Database Config with Environment Variable Support (for Render deployment)
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'sheger_kurt_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

// For Render's direct MySQL URL (if provided as a single string)
$db_url = getenv('DATABASE_URL');
if ($db_url) {
    $parts = parse_url($db_url);
    if ($parts) {
        $host = $parts['host'] ?? $host;
        $db   = ltrim($parts['path'] ?? '', '/') ?: $db;
        $user = $parts['user'] ?? $user;
        $pass = $parts['pass'] ?? $pass;
    }
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
     $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET $charset COLLATE utf8mb4_unicode_ci");
     $pdo->exec("USE `$db`");
} catch (\PDOException $e) {
     $pdo = null;
     if (basename($_SERVER['PHP_SELF']) !== 'setup_database.php') {
         die("<div style='font-family:sans-serif; text-align:center; margin-top:100px;'>
                <h1 style='color:#ef4444;'>No Database Connected!</h1>
                <p>Render is trying to load your website, but it cannot find the MySQL Database.</p>
                <p><b>Did you add your DB_HOST, DB_USER, DB_PASS, and DB_NAME to the Environment Variables on Render?</b></p>
                <br>
                <a href='setup_database.php' style='padding:12px 24px; background:#ff9d2d; color:#fff; font-weight:bold; text-decoration:none; border-radius:10px;'>Run Database Setup</a>
              </div>");
     }
}
?>
