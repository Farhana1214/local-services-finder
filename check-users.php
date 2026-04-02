<?php
include 'database_connection.php';

// Check customers
$customers = getMultipleResults($conn, "SELECT user_id, username, email, first_name, user_type FROM users WHERE user_type = 'customer' LIMIT 10");
$providers = getMultipleResults($conn, "SELECT user_id, username, email, first_name, user_type FROM users WHERE user_type = 'service_provider' LIMIT 10");

?>
<!DOCTYPE html>
<html>
<head>
    <title>User Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Database Users Check</h1>
    
    <h3>Customers (Customer Users)</h3>
    <?php if (count($customers) > 0): ?>
        <table class="table table-striped table-sm">
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th></tr>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?php echo $c['user_id']; ?></td>
                    <td><?php echo $c['username']; ?></td>
                    <td><?php echo $c['email']; ?></td>
                    <td><?php echo $c['first_name']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p><strong>TRY LOGGING IN WITH ANY OF THESE:</strong></p>
        <ul>
            <?php foreach ($customers as $c): ?>
                <li><strong>Username:</strong> <?php echo $c['username']; ?> | <strong>Password:</strong> Customer123!</li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-danger"><strong>NO CUSTOMERS FOUND!</strong></div>
        <p>You need to either:</p>
        <ol>
            <li><a href="import-data.php">Import dummy data</a> OR</li>
            <li><a href="user_registeration.php?type=customer">Register as a customer</a> (new account)</li>
        </ol>
    <?php endif; ?>
    
    <hr>
    
    <h3>Service Providers</h3>
    <?php if (count($providers) > 0): ?>
        <table class="table table-striped table-sm">
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th></tr>
            <?php foreach ($providers as $p): ?>
                <tr>
                    <td><?php echo $p['user_id']; ?></td>
                    <td><?php echo $p['username']; ?></td>
                    <td><?php echo $p['email']; ?></td>
                    <td><?php echo $p['first_name']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">No providers found</div>
    <?php endif; ?>
    
    <hr>
    <a href="login.php" class="btn btn-primary">Go to Login</a>
</div>
</body>
</html>
