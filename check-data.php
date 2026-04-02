<?php
include 'database_connection.php';
include 'config.php';

// Check service_providers count
$providers_count = getSingleResult($conn, "SELECT COUNT(*) as total FROM service_providers", '', []);
$active_providers = getSingleResult($conn, "SELECT COUNT(*) as total FROM service_providers WHERE is_active = 1 AND is_verified = 1", '', []);
$users_count = getSingleResult($conn, "SELECT COUNT(*) as total FROM users", '', []);
$categories_count = getSingleResult($conn, "SELECT COUNT(*) as total FROM service_categories", '', []);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Database Status Check</h1>
    <table class="table table-striped">
        <tr>
            <td><strong>Total Users:</strong></td>
            <td><?php echo $users_count['total'] ?? 0; ?></td>
        </tr>
        <tr>
            <td><strong>Total Categories:</strong></td>
            <td><?php echo $categories_count['total'] ?? 0; ?></td>
        </tr>
        <tr>
            <td><strong>Total Service Providers:</strong></td>
            <td><?php echo $providers_count['total'] ?? 0; ?></td>
        </tr>
        <tr>
            <td><strong>Active & Verified Providers:</strong></td>
            <td><?php echo $active_providers['total'] ?? 0; ?></td>
        </tr>
    </table>
    
    <hr>
    <h2>Sample Providers:</h2>
    <?php
    $sample = getMultipleResults($conn, "
        SELECT sp.provider_id, sp.business_name, sp.is_active, sp.is_verified, 
               u.first_name, u.city, c.category_name
        FROM service_providers sp
        JOIN users u ON sp.user_id = u.user_id
        JOIN service_categories c ON sp.category_id = c.category_id
        LIMIT 10
    ");
    
    if ($sample && count($sample) > 0) {
        echo "<table class='table table-sm'>";
        echo "<tr><th>Name</th><th>City</th><th>Category</th><th>Active</th><th>Verified</th></tr>";
        foreach ($sample as $p) {
            echo "<tr>";
            echo "<td>" . $p['business_name'] . "</td>";
            echo "<td>" . $p['city'] . "</td>";
            echo "<td>" . $p['category_name'] . "</td>";
            echo "<td>" . ($p['is_active'] ? "✓" : "✗") . "</td>";
            echo "<td>" . ($p['is_verified'] ? "✓" : "✗") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='alert alert-danger'>NO PROVIDERS FOUND!</p>";
    }
    ?>
    
    <a href="search.php" class="btn btn-primary">Go to Search</a>
</div>
</body>
</html>
