<?php
// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Log errors to file
ini_set('error_log', __DIR__ . '/../../error.log');

try {
    include '../database_connection.php';
    include '../config.php';
    include '../helpers.php';

    header('Content-Type: application/json');

    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $user_message = isset($input['message']) ? sanitize_input($input['message']) : '';
    $user_city = isset($input['user_city']) ? sanitize_input($input['user_city']) : '';
    $user_location = $input['user_location'] ?? null;

    if (empty($user_message)) {
        throw new Exception('No message provided');
    }

    // Service keywords mapping
    $service_keywords = [
        'electrical' => 2,
        'electric' => 2,
        'electrician' => 2,
        'wire' => 2,
        'light' => 2,
        'socket' => 2,
        'ac' => 6,
        'air' => 6,
        'cool' => 6,
        'plumb' => 1,
        'pipe' => 1,
        'leak' => 1,
        'clean' => 3,
        'sweep' => 3,
        'paint' => 5,
        'wood' => 4,
        'carpet' => 4,
        'garden' => 7,
        'pet' => 8,
        'dog' => 8,
        'cat' => 8,
        'gym' => 12,
        'fitness' => 12,
        'train' => 12
    ];

    // Detect category
    $detected_category = null;
    $message_lower = strtolower($user_message);
    
    foreach ($service_keywords as $keyword => $cat_id) {
        if (strpos($message_lower, $keyword) !== false) {
            $detected_category = $cat_id;
            break;
        }
    }

    $services = [];

    if ($detected_category) {
        // Search by category
        $query = "
            SELECT DISTINCT
                s.service_id,
                s.service_name,
                s.price,
                sp.provider_id,
                sp.business_name,
                sp.business_description,
                u.city,
                ROUND(COALESCE(AVG(r.rating), 0), 1) as avg_rating,
                COUNT(DISTINCT r.review_id) as total_reviews,
                MIN(s.price) as min_price
            FROM services s
            JOIN service_providers sp ON s.provider_id = sp.provider_id
            JOIN users u ON sp.user_id = u.user_id
            LEFT JOIN reviews r ON sp.provider_id = r.provider_id
            WHERE s.category_id = ? AND sp.is_active = 1 AND sp.is_verified = 1
        ";
        
        $params = [$detected_category];
        $types = 'i';
        
        if (!empty($user_city)) {
            $query .= " AND u.city LIKE ?";
            $params[] = '%' . $user_city . '%';
            $types .= 's';
        }
        
        $query .= " GROUP BY sp.provider_id ORDER BY avg_rating DESC LIMIT 5";
        
        $services = getMultipleResults($conn, $query, $types, $params);
        
        if (!empty($services)) {
            $location_text = !empty($user_city) ? " in " . htmlspecialchars($user_city) : " nearby";
            $reply = "✅ Found " . count($services) . " options" . $location_text . "!<br>";
            $reply .= "💰 Click to book instantly";
            sendResponse(true, $reply, $services);
        } else {
            // No services found for this category
            $location_text = !empty($user_city) ? " in " . htmlspecialchars($user_city) : " in your area";
            $reply = "❌ Sorry, these services are not available" . $location_text . " right now.<br>";
            $reply .= "Try searching for: electrical, plumbing, cleaning, or fitness services.";
            sendResponse(true, $reply, []);
        }
    }

    // Fallback to keyword search
    if (empty($services)) {
        $keyword = '%' . strtolower($user_message) . '%';
        
        $query = "
            SELECT DISTINCT
                s.service_id,
                s.service_name,
                s.price,
                sc.category_name,
                sp.provider_id,
                sp.business_name,
                sp.business_description,
                u.city,
                ROUND(COALESCE(AVG(r.rating), 0), 1) as avg_rating,
                COUNT(DISTINCT r.review_id) as total_reviews,
                MIN(s.price) as min_price
            FROM services s
            JOIN service_categories sc ON s.category_id = sc.category_id
            JOIN service_providers sp ON s.provider_id = sp.provider_id
            JOIN users u ON sp.user_id = u.user_id
            LEFT JOIN reviews r ON sp.provider_id = r.provider_id
            WHERE (s.service_name LIKE ? OR sc.category_name LIKE ? OR sp.business_name LIKE ?)
              AND sp.is_active = 1 AND sp.is_verified = 1
        ";
        
        $params = [$keyword, $keyword, $keyword];
        $types = 'sss';
        
        if (!empty($user_city)) {
            $query .= " AND u.city LIKE ?";
            $params[] = '%' . $user_city . '%';
            $types .= 's';
        }
        
        $query .= " GROUP BY sp.provider_id ORDER BY avg_rating DESC LIMIT 5";
        
        $services = getMultipleResults($conn, $query, $types, $params);
        
        if (!empty($services)) {
            $reply = "🔍 Found matching services!<br>";
            sendResponse(true, $reply, $services);
        } else {
            $reply = "❌ No services found for \"" . htmlspecialchars($user_message) . "\"<br><br>";
            $reply .= "📌 Popular searches:<br>";
            $reply .= "• Plumbing<br>";
            $reply .= "• Electrical<br>";
            $reply .= "• AC Repair<br>";
            $reply .= "• Cleaning<br>";
            $reply .= "• Fitness";
            sendResponse(true, $reply, []);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}

function sendResponse($success, $reply, $services) {
    $response = [
        'success' => $success,
        'reply' => $reply,
        'services' => []
    ];
    
    if (!empty($services)) {
        foreach ($services as $service) {
            $response['services'][] = [
                'service_id' => (int)$service['service_id'],
                'service_name' => htmlspecialchars($service['service_name']),
                'business_name' => htmlspecialchars($service['business_name']),
                'business_description' => htmlspecialchars($service['business_description'] ?? 'Professional services available'),
                'city' => htmlspecialchars($service['city'] ?? ''),
                'avg_rating' => (float)$service['avg_rating'],
                'total_reviews' => (int)$service['total_reviews'],
                'min_price' => (int)$service['min_price']
            ];
        }
    }
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

// Calculate distance between two coordinates (Haversine formula)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Earth's radius in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

// Service keywords mapping (English & Urdu)
$service_keywords = [
    'plumbing' => [1, 'plumber', 'pipe', 'tap', 'leak', 'drainage', 'پلمبنگ', 'پائپ', 'ڈرین'],
    'electrical' => [2, 'electric', 'electrician', 'wire', 'light', 'socket', 'الیکٹریکل', 'بجلی', 'سوکٹ'],
    'cleaning' => [3, 'clean', 'cleaning', 'sweep', 'house clean', 'صفائی', 'پاکی'],
    'carpentry' => [4, 'carpenter', 'wood', 'furniture', 'door', 'cabinet', 'لکڑی', 'فرنیچر'],
    'painting' => [5, 'paint', 'painter', 'color', 'wall', 'پینٹنگ', 'رنگ'],
    'hvac' => [6, 'ac', 'air conditioning', 'cool', 'heat', 'hvac', 'furnace', 'ایئر کنڈیشنر', 'سردی'],
    'gardening' => [7, 'garden', 'landscaping', 'lawn', 'tree', 'gardener', 'باغ', 'درخت'],
    'pet_care' => [8, 'pet', 'dog', 'cat', 'grooming', 'boarding', 'جانور', 'کتا', 'بلی'],
    'appliance' => [9, 'appliance', 'fridge', 'washing', 'dishwasher', 'repair', 'یخ دان', 'واشنگ مشین'],
    'fitness' => [12, 'fitness', 'gym', 'training', 'personal trainer', 'exercise', 'جم', 'ورزش']
];

// Detect service category from message
function detectCategory($message) {
    global $service_keywords;
    $message_lower = strtolower($message);
    
    foreach ($service_keywords as $service => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($message_lower, strtolower($keyword)) !== false) {
                return $keywords[0]; // Return category_id
            }
        }
    }
    
    return null;
}

// Detect urgency/priority
function detectUrgency($message) {
    $urgency_words = ['urgent', 'asap', 'immediately', 'emergency', 'اہم', 'ابھی', 'فوری', 'ایمرجنسی'];
    $message_lower = strtolower($message);
    
    foreach ($urgency_words as $word) {
        if (strpos($message_lower, strtolower($word)) !== false) {
            return 'high';
        }
    }
    
    return 'medium';
}

// Extract budget/price range
function detectBudget($message) {
    // Simple price extraction (e.g., "under 5000", "5000-10000")
    if (preg_match('/under\s*(\d+)/i', $message, $match)) {
        return intval($match[1]);
    }
    if (preg_match('/(\d+)\s*-\s*(\d+)/i', $message, $match)) {
        return ['min' => intval($match[1]), 'max' => intval($match[2])];
    }
    return null;
}

// Main chatbot logic
$category_id = detectCategory($user_message);

// Response based on intent
if ($category_id) {
    // User asked for a specific service
    $query = "
        SELECT 
            s.service_id,
            s.service_name,
            s.price,
            s.duration_hours,
            sp.provider_id,
            sp.business_name,
            sp.business_description,
            sp.business_city,
            sp.latitude,
            sp.longitude,
            u.city,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.review_id) as total_reviews,
            MIN(s.price) as min_price
        FROM services s
        JOIN service_providers sp ON s.provider_id = sp.provider_id
        JOIN users u ON sp.user_id = u.user_id
        LEFT JOIN reviews r ON sp.provider_id = r.provider_id
        WHERE s.category_id = ? AND sp.is_active = 1 AND sp.is_verified = 1
    ";
    
    $params = [$category_id];
    $types = 'i';
    
    // Filter by user's city if available
    if (!empty($user_city)) {
        $query .= " AND u.city = ?";
        $params[] = $user_city;
        $types .= 's';
    }
    
    $query .= " GROUP BY sp.provider_id ORDER BY avg_rating DESC, total_reviews DESC LIMIT 10";
    
    $services = getMultipleResults($conn, $query, $types, $params);
    
    // Sort by distance if user location provided
    if ($user_location && isset($user_location['lat']) && isset($user_location['lng'])) {
        usort($services, function($a, $b) use ($user_location) {
            $distA = ($a['latitude'] && $a['longitude']) ? 
                calculateDistance($user_location['lat'], $user_location['lng'], $a['latitude'], $a['longitude']) : 
                PHP_INT_MAX;
            $distB = ($b['latitude'] && $b['longitude']) ? 
                calculateDistance($user_location['lat'], $user_location['lng'], $b['latitude'], $b['longitude']) : 
                PHP_INT_MAX;
            return $distA <=> $distB;
        });
        $services = array_slice($services, 0, 5);
    } else {
        $services = array_slice($services, 0, 5);
    }
    
    if (!empty($services)) {
        $location_text = !empty($user_city) ? "in your area ($user_city)" : "nearby";
        $reply = "✅ Found " . count($services) . " great options $location_text!<br>";
        $reply .= "💡 <small>You can book instantly or compare prices</small>";
        
        // Check if user mentioned urgency
        $urgency = detectUrgency($user_message);
        if ($urgency === 'high') {
            $reply .= "<br>⚡ <strong>Urgent request noted!</strong> Top-rated providers shown first.";
        }
        
        jsonResponse(true, $reply, $services);
    } else {
        $reply = "😔 No providers available for this service in your area right now.<br>";
        $reply .= "📍 Try another location or service type?";
        jsonResponse(true, $reply, []);
    }
} else {
    // General query - search by keyword
    $keyword = '%' . strtolower($user_message) . '%';
    
    $query = "
        SELECT 
            s.service_id,
            s.service_name,
            s.price,
            sc.category_name,
            sp.provider_id,
            sp.business_name,
            sp.business_description,
            sp.business_city,
            sp.latitude,
            sp.longitude,
            u.city,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.review_id) as total_reviews,
            MIN(s.price) as min_price
        FROM services s
        JOIN service_categories sc ON s.category_id = sc.category_id
        JOIN service_providers sp ON s.provider_id = sp.provider_id
        JOIN users u ON sp.user_id = u.user_id
        LEFT JOIN reviews r ON sp.provider_id = r.provider_id
        WHERE (s.service_name LIKE ? OR sc.category_name LIKE ? OR sp.business_name LIKE ?) 
              AND sp.is_active = 1 AND sp.is_verified = 1
    ";
    
    $params = [$keyword, $keyword, $keyword];
    $types = 'sss';
    
    if (!empty($user_city)) {
        $query .= " AND u.city = ?";
        $params[] = $user_city;
        $types .= 's';
    }
    
    $query .= " GROUP BY sp.provider_id ORDER BY avg_rating DESC LIMIT 10";
    
    $services = getMultipleResults($conn, $query, $types, $params);
    
    // Sort by distance if user location provided
    if ($user_location && isset($user_location['lat']) && isset($user_location['lng'])) {
        usort($services, function($a, $b) use ($user_location) {
            $distA = ($a['latitude'] && $a['longitude']) ? 
                calculateDistance($user_location['lat'], $user_location['lng'], $a['latitude'], $a['longitude']) : 
                PHP_INT_MAX;
            $distB = ($b['latitude'] && $b['longitude']) ? 
                calculateDistance($user_location['lat'], $user_location['lng'], $b['latitude'], $b['longitude']) : 
                PHP_INT_MAX;
            return $distA <=> $distB;
        });
        $services = array_slice($services, 0, 5);
    } else {
        $services = array_slice($services, 0, 5);
    }
    
    if (!empty($services)) {
        $reply = "🔍 Found services matching your search!<br>";
        $reply .= "📌 Here are the best matches:<br>";
        
        $categories = [];
        foreach ($services as $s) {
            if (!empty($s['category_name'])) {
                $categories[] = $s['category_name'];
            }
        }
        $categories = array_unique($categories);
        $reply .= "<small>" . implode(", ", $categories) . "</small>";
        
        jsonResponse(true, $reply, $services);
    } else {
        $suggestions = "💬 I can help with:<br>";
        $suggestions .= "• <strong>AC / Cooling</strong> - \"Mujhe AC repair chahiye\"<br>";
        $suggestions .= "• <strong>Plumbing</strong> - \"Plumbing services nearby\"<br>";
        $suggestions .= "• <strong>Electrical</strong> - \"Electrician required\"<br>";
        $suggestions .= "• <strong>Cleaning</strong> - \"House cleaning service\"<br>";
        $suggestions .= "• <strong>Carpentry</strong> - \"Door installation\"<br>";
        $suggestions .= "• <strong>Fitness</strong> - \"Personal trainer\"<br>";
        $suggestions .= "<small style='color: #999;'>Try asking in Urdu or English!</small>";
        
        jsonResponse(true, $suggestions, []);
    }
}

function jsonResponse($success, $reply, $services = []) {
    http_response_code(200);
    echo json_encode([
        'success' => $success,
        'reply' => $reply,
        'services' => $services
    ]);
    exit();
}
?>
