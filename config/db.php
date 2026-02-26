<?php
// ===========================================
// DATABASE CONFIGURATION
// ===========================================
// Edit these settings to match your database setup
// when sharing this project with others

$database_config = [
    'host' => '127.0.0.1',           // Database host (use 127.0.0.1 for local XAMPP)
    'port' => '3306',                // Primary database port (default is 3306)
    'dbname' => 'spotify_clone',     // Database name
    'username' => 'root',            // Database username
    'password' => '',                // Database password
    'charset' => 'utf8mb4'           // Character set
];

// ===========================================
// DO NOT EDIT BELOW THIS LINE
// ===========================================

try {
    $host = getenv('DB_HOST') ?: $database_config['host'];
    $dbname = getenv('DB_NAME') ?: $database_config['dbname'];
    $username = getenv('DB_USER') ?: $database_config['username'];
    $password = getenv('DB_PASS');
    if ($password === false) {
        $password = $database_config['password'];
    }
    $charset = getenv('DB_CHARSET') ?: $database_config['charset'];

    // Try configured port first, then common local MySQL ports.
    $portCandidates = [
        (string)(getenv('DB_PORT') ?: $database_config['port']),
        '3306',
        '3307'
    ];
    $ports = array_values(array_unique(array_filter($portCandidates)));

    $pdo = null;
    $lastErrors = [];
    foreach ($ports as $port) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            $pdo = new PDO($dsn, $username, $password);
            $database_config['host'] = $host;
            $database_config['port'] = $port;
            break;
        } catch (PDOException $e) {
            $lastErrors[] = "port {$port}: " . $e->getMessage();
        }
    }

    if (!$pdo) {
        $errorSummary = implode(" | ", $lastErrors);
        die(
            "Database connection failed. Tried host {$host} on ports " . implode(', ', $ports) . ".\n" .
            "Errors: {$errorSummary}\n\n" .
            "Please verify MySQL is running in XAMPP and update config/db.php (or DB_* env vars)."
        );
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n\nPlease check your database configuration in config/db.php");
}
?>
