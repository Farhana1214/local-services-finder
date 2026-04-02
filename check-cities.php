<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

echo "<h2>🔍 Service Data by City</h2>";

// Check services by city
$query = "
    SELECT 
        u.city,
        COUNT(DISTINCT sp.provider_id) as providers,
        COUNT(DISTINCT s.service_id) as services
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    LEFT JOIN services s ON sp.provider_id = s.provider_id
    WHERE sp.is_active = 1 AND sp.is_verified = 1
    GROUP BY u.city
    ORDER BY services DESC
";

$result = $conn->query($query);
if ($result) {
    echo "<table border='1' cellpadding='10' style='width:100%; border-collapse: collapse;'>";
    echo "<tr style='background: #667eea; color: white;'><th>City</th><th>Providers</th><th>Services</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $bgColor = $row['services'] > 0 ? '#d4edda' : '#f8d7da';
        echo "<tr style='background: $bgColor;'>";
        echo "<td><strong>{$row['city']}</strong></td>";
        echo "<td>{$row['providers']}</td>";
        echo "<td>{$row['services']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Query error: " . $conn->error;
}

echo "<br><br>";

// Show sample services in each city
echo "<h3>Services Available in Each City:</h3>";
$query = "
    SELECT DISTINCT
        u.city,
        sp.business_name,
        s.service_name,
        c.category_name
    FROM services s
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    JOIN users u ON sp.user_id = u.user_id
    JOIN service_categories c ON s.category_id = c.category_id
    WHERE sp.is_active = 1 AND sp.is_verified = 1
    ORDER BY u.city, c.category_name
";

$result = $conn->query($query);
$current_city = '';

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['city'] !== $current_city) {
            if ($current_city !== '') echo "</ul><br>";
            $current_city = $row['city'];
            echo "<h4 style='color: #667eea;'>📍 " . htmlspecialchars($current_city) . "</h4>";
            echo "<ul>";
        }
        echo "<li><strong>{$row['business_name']}</strong> → {$row['service_name']} ({$row['category_name']})</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'><strong>❌ NO SERVICES FOUND!</strong></p>";
    echo "<p>Your database might be empty. Run: <a href='import-data.php'>import-data.php</a></p>";
}

echo "<br><hr><br>";

// Check total counts
$users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$providers = $conn->query("SELECT COUNT(*) as cnt FROM service_providers")->fetch_assoc()['cnt'];
$services = $conn->query("SELECT COUNT(*) as cnt FROM services")->fetch_assoc()['cnt'];
$categories = $conn->query("SELECT COUNT(*) as cnt FROM service_categories")->fetch_assoc()['cnt'];

echo "<h3>Database Summary:</h3>";
echo "Users: <strong>$users</strong><br>";
echo "Service Providers: <strong>$providers</strong><br>";
echo "Services: <strong>$services</strong><br>";
echo "Categories: <strong>$categories</strong><br>";

if ($services == 0) {
    echo "<br><p style='color: red; font-weight: bold;'>⚠️ You have NO SERVICES in the database!</p>";
    echo "<p>Click here to import data: <a href='import-data.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Import Data Now</a></p>";
}
?>
