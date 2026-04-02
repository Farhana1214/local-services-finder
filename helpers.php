<?php
// Helper functions for Service Finder Application

// ===== VALIDATION FUNCTIONS =====

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function isValidPassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        return false;
    }
    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
        return false;
    }
    return true;
}

/**
 * Validate phone number
 */
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9+\-\s\(\)]/', '', $phone);
    return preg_match('/^\+?1?\d{9,15}$/', str_replace([' ', '-', '(', ')'], '', $phone)) === 1;
}

/**
 * Validate ZIP code
 */
function isValidZipCode($zip) {
    return preg_match('/^\d{5}(-\d{4})?$/', $zip) === 1;
}

/**
 * Validate URL
 */
function isValidURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// ===== FORMATTING FUNCTIONS =====

/**
 * Format currency
 */
function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format time
 */
function formatTime($time, $format = 'h:i A') {
    if (!$time) return 'N/A';
    return date($format, strtotime($time));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    if (!$datetime) return 'N/A';
    return date($format, strtotime($datetime));
}

/**
 * Calculate duration in human readable format
 */
function formatDuration($hours) {
    if ($hours < 1) {
        return (int)($hours * 60) . ' mins';
    } elseif ($hours == 1) {
        return '1 hour';
    } else {
        return (int)$hours . ' hours';
    }
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

// ===== RATING FUNCTIONS =====

/**
 * Get star rating display
 */
function getStarRating($rating) {
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
    $empty_stars = 5 - $full_stars - $half_star;
    
    $html = '';
    for ($i = 0; $i < $full_stars; $i++) {
        $html .= '★';
    }
    if ($half_star) {
        $html .= '½';
    }
    for ($i = 0; $i < $empty_stars; $i++) {
        $html .= '☆';
    }
    
    return $html;
}

/**
 * Get rating percentage
 */
function getRatingPercentage($rating, $totalRating = 5) {
    return ($rating / $totalRating) * 100;
}

// ===== DISTANCE FUNCTIONS =====

/**
 * Calculate distance between two coordinates (Haversine formula)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * asin(sqrt($a));
    $distance = $earth_radius * $c;
    
    return round($distance, 2);
}

// ===== BOOKING FUNCTIONS =====

/**
 * Get booking status badge
 */
function getStatusBadge($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'in_progress' => 'primary',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    
    $color = $colors[$status] ?? 'secondary';
    return '<span class="badge bg-' . htmlspecialchars($color) . '">' . ucfirst(str_replace('_', ' ', htmlspecialchars($status))) . '</span>';
}

/**
 * Get payment status badge
 */
function getPaymentStatusBadge($status) {
    $colors = [
        'pending' => 'warning',
        'completed' => 'success',
        'failed' => 'danger',
        'refunded' => 'info'
    ];
    
    $color = $colors[$status] ?? 'secondary';
    return '<span class="badge bg-' . htmlspecialchars($color) . '">' . ucfirst(htmlspecialchars($status)) . '</span>';
}

/**
 * Calculate booking days remaining
 */
function daysUntilService($serviceDate) {
    $date = strtotime($serviceDate);
    $today = strtotime(date('Y-m-d'));
    $diff = ($date - $today) / (60 * 60 * 24);
    return (int)$diff;
}

// ===== ACCOUNT FUNCTIONS =====

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate verification token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate unique reference number
 */
function generateRefNumber($prefix = 'BK') {
    return $prefix . strtoupper(substr(uniqid(), -6)) . date('Ymd');
}

// ===== ARRAY FUNCTIONS =====

/**
 * Get array value safely
 */
function getArrayValue($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Check if all required keys exist in array
 */
function hasRequiredKeys($array, $requiredKeys) {
    foreach ($requiredKeys as $key) {
        if (!isset($array[$key]) || empty($array[$key])) {
            return false;
        }
    }
    return true;
}

// ===== RESPONSE FUNCTIONS =====

/**
 * Return JSON response
 */
function jsonResponse($success = true, $message = '', $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}

/**
 * Redirect with message
 */
function redirectWithMessage($location, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $location);
    exit();
}

/**
 * Display and clear session message
 */
function displaySessionMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        return '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    }
    return '';
}

// ===== FILE FUNCTIONS =====

/**
 * Upload file
 */
function uploadFile($file, $directory = 'uploads/profiles/') {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error'];
    }
    
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File size too large'];
    }
    
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    $newFileName = uniqid() . '.' . $fileExt;
    $filePath = $directory . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => $filePath, 'file_name' => $newFileName];
    }
    
    return ['success' => false, 'error' => 'Failed to upload file'];
}

/**
 * Delete file
 */
function deleteFile($filePath) {
    if (file_exists($filePath) && is_file($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// ===== QUERY FUNCTIONS =====

/**
 * Get provider profile
 */
function getProviderProfile($conn, $provider_id) {
    $query = "SELECT sp.*, u.email, u.phone, u.address, u.city, u.state, u.zip_code, 
              AVG(r.rating) as avg_rating, COUNT(r.review_id) as total_reviews
              FROM service_providers sp
              JOIN users u ON sp.user_id = u.user_id
              LEFT JOIN reviews r ON sp.provider_id = r.provider_id
              WHERE sp.provider_id = ?
              GROUP BY sp.provider_id";
    
    return getSingleResult($conn, $query, 'i', [$provider_id]);
}

/**
 * Get services by provider
 */
function getServicesByProvider($conn, $provider_id) {
    $query = "SELECT * FROM services WHERE provider_id = ? AND is_available = 1";
    return getMultipleResults($conn, $query, 'i', [$provider_id]);
}

/**
 * Get user's bookings
 */
function getUserBookings($conn, $user_id, $status = null, $limit = null) {
    $query = "SELECT b.*, s.service_name, p.business_name, p.profile_image, 
              r.review_id, r.rating
              FROM bookings b
              JOIN services s ON b.service_id = s.service_id
              JOIN service_providers p ON b.provider_id = p.provider_id
              LEFT JOIN reviews r ON b.booking_id = r.booking_id
              WHERE b.user_id = ?";
    
    $params = [$user_id];
    $types = 'i';
    
    if ($status) {
        $query .= " AND b.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $query .= " ORDER BY b.booking_date DESC";
    
    if ($limit) {
        $query .= " LIMIT ?";
        $params[] = $limit;
        $types .= 'i';
    }
    
    return getMultipleResults($conn, $query, $types, $params);
}

/**
 * Get provider's bookings
 */
function getProviderBookings($conn, $provider_id, $status = null) {
    $query = "SELECT b.*, s.service_name, u.first_name, u.last_name, u.email, u.phone
              FROM bookings b
              JOIN services s ON b.service_id = s.service_id
              JOIN users u ON b.user_id = u.user_id
              WHERE b.provider_id = ?";
    
    $params = [$provider_id];
    $types = 'i';
    
    if ($status) {
        $query .= " AND b.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $query .= " ORDER BY b.booking_date DESC";
    
    return getMultipleResults($conn, $query, $types, $params);
}

/**
 * Calculate average rating for provider
 */
function getProviderAverageRating($conn, $provider_id) {
    $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE provider_id = ?";
    $result = getSingleResult($conn, $query, 'i', [$provider_id]);
    return $result ? ['average' => round($result['avg_rating'] ?? 0, 2), 'total' => $result['total_reviews'] ?? 0] : ['average' => 0, 'total' => 0];
}

// ===== PAGE TITLE AND META =====

/**
 * Set page title
 */
function setPageTitle($title) {
    $_SESSION['page_title'] = $title;
}

/**
 * Get page title
 */
function getPageTitle() {
    return isset($_SESSION['page_title']) ? $_SESSION['page_title'] : SITE_NAME;
}

// ===== GEOLOCATION FUNCTIONS =====

/**
 * Get JavaScript for browser geolocation and city detection
 */
function getLocationScript() {
    return "<script>
function getAndSetUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                // Get city name from coordinates using reverse geocoding
                reverseLookup(lat, lng);
            },
            function(error) {
                console.log('Geolocation error:', error);
                // Use default city if geolocation fails
                setDefaultLocation();
            }
        );
    } else {
        setDefaultLocation();
    }
}

function reverseLookup(lat, lng) {
    // Estimate city based on coordinates (Pakistan cities)
    const cities = {
        'karachi': {lat: 24.8607, lng: 67.0011, range: 0.5},
        'lahore': {lat: 31.5497, lng: 74.3436, range: 0.5},
        'islamabad': {lat: 33.6844, lng: 73.1566, range: 0.5},
        'jauharabad': {lat: 30.2647, lng: 73.0889, range: 0.5},
        'multan': {lat: 30.2076, lng: 71.4284, range: 0.5},
        'peshawar': {lat: 34.0151, lng: 71.5249, range: 0.5},
        'quetta': {lat: 30.1798, lng: 66.9750, range: 0.5}
    };
    
    for (let city in cities) {
        const c = cities[city];
        if (Math.abs(lat - c.lat) < c.range && Math.abs(lng - c.lng) < c.range) {
            setUserLocation(city);
            return;
        }
    }
    setDefaultLocation();
}

function setUserLocation(city) {
    const element = document.getElementById('user_location');
    if (element) {
        element.value = city.charAt(0).toUpperCase() + city.slice(1);
        // Trigger search if on search page
        if (window.location.pathname.includes('search') || window.location.pathname.includes('smart-search')) {
            const form = document.getElementById('search-form');
            if (form) {
                form.submit();
            }
        }
    }
}

function setDefaultLocation() {
    const element = document.getElementById('user_location');
    if (element) {
        element.value = '" . USER_DEFAULT_CITY . "';
    }
}

// Auto-detect location on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', getAndSetUserLocation);
} else {
    getAndSetUserLocation();
}
</script>";
}
?>
