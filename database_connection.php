<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'service_finder');

// MySQLi Connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if (!$conn) {
    die(json_encode(['success' => false, 'message' => 'Connection Failed: ' . mysqli_connect_error()]));
}

// Set charset to utf8mb4
mysqli_set_charset($conn, 'utf8mb4');

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Helper function to sanitize input
function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Helper function for prepared statements
function executeQuery($conn, $query, $types = '', $params = []) {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['success' => false, 'error' => $conn->error];
    }
    
    if (!empty($types) && !empty($params)) {
        // Convert array values to references for bind_param
        $paramRefs = [];
        foreach ($params as &$param) {
            $paramRefs[] = &$param;
        }
        $stmt->bind_param($types, ...$paramRefs);
    }
    
    if (!$stmt->execute()) {
        return ['success' => false, 'error' => $stmt->error];
    }
    
    return ['success' => true, 'stmt' => $stmt];
}

// Helper function to get single result
function getSingleResult($conn, $query, $types = '', $params = []) {
    $result = executeQuery($conn, $query, $types, $params);
    if (!$result['success']) {
        return null;
    }
    
    $stmt = $result['stmt'];
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}

// Helper function to get multiple results
function getMultipleResults($conn, $query, $types = '', $params = []) {
    $result = executeQuery($conn, $query, $types, $params);
    if (!$result['success']) {
        return [];
    }
    
    $stmt = $result['stmt'];
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    $stmt->close();
    return $rows;
}

// Session configuration (session already started at top of file)
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_destroy();
    $_SESSION = [];
}
$_SESSION['last_activity'] = time();

// Helper to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Helper to check user type
function getUserType() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

// Helper to redirect to login
function requireLogin() {
    global $conn;
    
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    // Validate user still exists in database
    $userCheck = getSingleResult($conn, "SELECT user_id FROM users WHERE user_id = ?", 'i', [$_SESSION['user_id']]);
    if (!$userCheck) {
        session_destroy();
        $_SESSION = [];
        header('Location: login.php');
        exit();
    }
}

// Helper to redirect based on user type
function requireUserType($type) {
    if (!isLoggedIn() || getUserType() !== $type) {
        header('Location: login.php');
        exit();
    }
}
