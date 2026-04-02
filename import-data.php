<?php
include 'database_connection.php';
include 'config.php';

// First, clear all existing data (in reverse order of foreign keys)
$truncate_queries = [
    "SET FOREIGN_KEY_CHECKS=0",
    "TRUNCATE TABLE favorites",
    "TRUNCATE TABLE reviews",
    "TRUNCATE TABLE payments",
    "TRUNCATE TABLE bookings",
    "TRUNCATE TABLE services",
    "TRUNCATE TABLE service_providers",
    "TRUNCATE TABLE service_categories",
    "TRUNCATE TABLE promo_codes",
    "TRUNCATE TABLE users",
    "SET FOREIGN_KEY_CHECKS=1"
];

$errors = [];
$count = 0;

// Execute truncate queries
foreach ($truncate_queries as $query) {
    if (!empty($query) && !$conn->query($query)) {
        $errors[] = [
            'query' => $query,
            'error' => $conn->error
        ];
    }
}

// Read the SQL file
$sql_file = file_get_contents('dummy_data.sql');

// Split by semicolon but be careful about them inside strings
$queries = array_filter(array_map('trim', preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql_file)));

foreach ($queries as $query) {
    if (empty($query)) continue;
    
    // Execute each query directly
    if (!$conn->query($query)) {
        $errors[] = [
            'query' => substr($query, 0, 100) . '...',
            'error' => $conn->error
        ];
    } else {
        $count++;
    }
}

// Get counts
$providers_count = getSingleResult($conn, "SELECT COUNT(*) as total FROM service_providers", '', []);
$categories_count = getSingleResult($conn, "SELECT COUNT(*) as total FROM service_categories", '', []);
$users_count = getSingleResult($conn, "SELECT COUNT(*) as total FROM users", '', []);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Import Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Data Import Complete</h1>
    
    <?php if (count($errors) === 0): ?>
        <div class="alert alert-success">✓ All data imported successfully!</div>
    <?php else: ?>
        <div class="alert alert-warning">⚠ Some queries had issues (<?php echo count($errors); ?>)</div>
    <?php endif; ?>
    
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
            <td><strong>Queries Executed:</strong></td>
            <td><?php echo $count; ?></td>
        </tr>
    </table>
    
    <?php if (count($errors) > 0): ?>
        <h3>Errors:</h3>
        <pre class="bg-light p-3" style="overflow-x: auto;">
<?php foreach ($errors as $e): ?>
Query: <?php echo htmlspecialchars($e['query']); ?>
Error: <?php echo htmlspecialchars($e['error']); ?>
---
<?php endforeach; ?>
        </pre>
    <?php endif; ?>
    
    <hr>
    <a href="check-data.php" class="btn btn-info">Check Data</a>
    <a href="search.php" class="btn btn-primary">Go to Search</a>
    <a href="index.php" class="btn btn-success">Go to Home</a>
</div>
</body>
</html>
