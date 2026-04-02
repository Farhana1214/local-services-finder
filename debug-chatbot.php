<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

echo "<h2>🔍 Chatbot Debug Info</h2>";

// 1. Check if we have data
echo "<h3>1️⃣ Database Status</h3>";

$result = $conn->query("SELECT COUNT(*) as cnt FROM services");
$services_count = $result->fetch_assoc()['cnt'];
echo "Total Services: <strong>$services_count</strong><br>";

$result = $conn->query("SELECT COUNT(*) as cnt FROM service_providers WHERE is_verified=1 AND is_active=1");
$providers_count = $result->fetch_assoc()['cnt'];
echo "Active Verified Providers: <strong>$providers_count</strong><br>";

$result = $conn->query("SELECT COUNT(*) as cnt FROM service_categories");
$categories_count = $result->fetch_assoc()['cnt'];
echo "Service Categories: <strong>$categories_count</strong><br>";

echo "<br>";

// 2. Check category mapping
echo "<h3>2️⃣ Service Categories (Check if IDs match)</h3>";
$result = $conn->query("SELECT category_id, category_name FROM service_categories ORDER BY category_id");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['category_id']} → {$row['category_name']}<br>";
}

echo "<br>";

// 3. Test API for each category
echo "<h3>3️⃣ Test Each Category Search</h3>";

$test_categories = [
    1 => 'Plumbing',
    2 => 'Electrical',
    3 => 'Cleaning',
    4 => 'Carpentry',
    5 => 'Painting',
    6 => 'AC/HVAC',
    7 => 'Gardening',
    8 => 'Pet Care',
    12 => 'Fitness'
];

foreach ($test_categories as $cat_id => $cat_name) {
    $query = "
        SELECT s.service_id, s.service_name, s.price, sp.business_name
        FROM services s
        JOIN service_providers sp ON s.provider_id = sp.provider_id
        WHERE s.category_id = ? AND sp.is_active = 1 AND sp.is_verified = 1
        LIMIT 3
    ";
    
    $result = executeQuery($conn, $query, 'i', [$cat_id]);
    if ($result['success']) {
        $stmt = $result['stmt'];
        $res = $stmt->get_result();
        $count = $res->num_rows;
        
        echo "<strong>$cat_id - $cat_name:</strong> $count services found";
        if ($count > 0) {
            echo " ✅<br>";
            while ($row = $res->fetch_assoc()) {
                echo "   • {$row['service_name']} by {$row['business_name']} (Rs. {$row['price']})<br>";
            }
        } else {
            echo " ❌<br>";
        }
    }
}

echo "<br><hr><br>";

// 4. Test API endpoint directly
echo "<h3>4️⃣ Test API Endpoint (Electrical)</h3>";

$testData = [
    'message' => 'I need electrical help',
    'user_city' => 'Lahore',
    'user_location' => null
];

echo "Request Data:<pre>";
print_r($testData);
echo "</pre>";

// Simulate the API call
$input = $testData;
$user_message = $input['message'];
$user_city = $input['user_city'];

$service_keywords = [
    'electrical' => 2,
    'electric' => 2,
    'electrician' => 2,
];

$detected_category = null;
$message_lower = strtolower($user_message);

foreach ($service_keywords as $keyword => $cat_id) {
    if (strpos($message_lower, $keyword) !== false) {
        $detected_category = $cat_id;
        break;
    }
}

echo "Detected Category: <strong>$detected_category</strong><br>";

if ($detected_category) {
    $query = "
        SELECT DISTINCT
            s.service_id,
            s.service_name,
            s.price,
            sp.provider_id,
            sp.business_name,
            u.city,
            ROUND(COALESCE(AVG(r.rating), 0), 1) as avg_rating,
            COUNT(DISTINCT r.review_id) as total_reviews
        FROM services s
        JOIN service_providers sp ON s.provider_id = sp.provider_id
        JOIN users u ON sp.user_id = u.user_id
        LEFT JOIN reviews r ON sp.provider_id = r.provider_id
        WHERE s.category_id = ? AND sp.is_active = 1 AND sp.is_verified = 1
        GROUP BY s.service_id
        LIMIT 10
    ";
    
    $result = executeQuery($conn, $query, 'i', [$detected_category]);
    if ($result['success']) {
        $stmt = $result['stmt'];
        $res = $stmt->get_result();
        $services = $res->fetch_all(MYSQLI_ASSOC);
        
        echo "Results Found: <strong>" . count($services) . "</strong><br>";
        echo "<pre>";
        print_r($services);
        echo "</pre>";
    } else {
        echo "Query Error!<br>";
        print_r($result);
    }
}

?>
