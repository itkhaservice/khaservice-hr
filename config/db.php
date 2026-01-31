<?php
// Security: Session Hardening
// Must be called before session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1); // Prevent JS access to session cookie (XSS protection)
    ini_set('session.use_only_cookies', 1); // Prevent session fixation
    ini_set('session.cookie_samesite', 'Lax'); // CSRF protection
    // If using HTTPS in production, uncomment the line below:
    // ini_set('session.cookie_secure', 1);
    
    session_start();
}

// Database Configuration
$server_name = $_SERVER['SERVER_NAME'] ?? 'localhost';
if ($server_name == 'localhost' || $server_name == '127.0.0.1' || strpos($server_name, '192.168.') === 0) {
    // LOCAL ENVIRONMENT (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'khaservice_hr_db');
    define('BASE_URL', '/khaservice-hr/');
} else {
    // PRODUCTION ENVIRONMENT (InfinityFree)
    // Cập nhật thông tin từ Control Panel Hosting của bạn vào đây
    define('DB_HOST', 'sql100.infinityfree.com'); 
    define('DB_USER', 'if0_40964643');            
    define('DB_PASS', 'qdyu7jD19gNkN');        
    define('DB_NAME', 'if0_40964643_khaservice_hr');
    define('BASE_URL', '/');
}

define('DB_CHARSET', 'utf8mb4');

// Create connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Create mysqli connection for legacy scripts
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        error_log("Mysqli Connection failed: " . mysqli_connect_error());
    } else {
        mysqli_set_charset($conn, DB_CHARSET);
    }
} catch (\PDOException $e) {
    // Security: Do not reveal DB credentials or specific errors to user normally
    error_log("Connection failed: " . $e->getMessage()); // Log internally
    
    if (isset($_GET['debug'])) {
        die("Connection failed: " . $e->getMessage());
    }
    
    die("Hệ thống đang bảo trì hoặc gặp sự cố kết nối cơ sở dữ liệu. Vui lòng thử lại sau.");
}

// Global helper function for queries
function db_query($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (\PDOException $e) {
        // Log error instead of displaying raw SQL error
        error_log("Database Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

// Function to fetch single row
function db_fetch_row($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

// Function to fetch all rows
function db_fetch_all($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

// Function to get last inserted ID
function db_last_insert_id() {
    global $pdo;
    return $pdo->lastInsertId();
}
?>
