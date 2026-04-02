<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

echo "<h2>Password Test & Debug</h2>";

// Test 1: Hash and Verify a test password
$testPassword = "Test123!@";
echo "<h3>Test 1: Hash & Verify</h3>";
echo "Original Password: " . $testPassword . "<br>";
$hash = hashPassword($testPassword);
echo "Hashed Password: " . $hash . "<br>";
echo "Hash Length: " . strlen($hash) . "<br>";
echo "Verification Result: " . (verifyPassword($testPassword, $hash) ? "✓ PASS" : "✗ FAIL") . "<br><br>";

// Test 2: Check database password column
echo "<h3>Test 2: Database Password Column</h3>";
$result = $conn->query("DESCRIBE users");
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'password') {
        echo "Password Column Type: " . $row['Type'] . "<br>";
        echo "Null: " . $row['Null'] . "<br>";
        echo "Default: " . $row['Default'] . "<br>";
    }
}
echo "<br>";

// Test 3: Check last registered user's password
echo "<h3>Test 3: Last Registered User Password</h3>";
$query = "SELECT username, email, password FROM users ORDER BY user_id DESC LIMIT 1";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    echo "Username: " . $row['username'] . "<br>";
    echo "Email: " . $row['email'] . "<br>";
    echo "Stored Hash Length: " . strlen($row['password']) . "<br>";
    if (strlen($row['password']) < 10) {
        echo "<span style='color:red;'>⚠️ PASSWORD FIELD APPEARS EMPTY OR CORRUPTED (Length: " . strlen($row['password']) . ")</span><br>";
    }
    echo "Stored Hash: " . substr($row['password'], 0, 20) . "..." . (strlen($row['password']) > 20 ? substr($row['password'], -10) : "") . "<br>";
    
    // Try to verify with empty password
    echo "Verification with Test123!@: " . (verifyPassword("Test123!@", $row['password']) ? "✓ MATCH" : "✗ NO MATCH") . "<br>";
} else {
    echo "No users found in database<br>";
}
echo "<br>";

// Test 4: Simulate registration
echo "<h3>Test 4: Simulate Registration & Login</h3>";

// Create test user
$testUser = "testuser" . time();
$testEmail = "test_" . time() . "@test.com";
$testPass = "Password123!@";

echo "Creating test user: " . $testUser . "<br>";
$hashedPass = hashPassword($testPass);
echo "Hashed password length: " . strlen($hashedPass) . "<br>";

$query = "INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code, user_type, is_verified, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$result = executeQuery($conn, $query, 'sssssssssssi', [
    $testUser, $testEmail, $hashedPass, 'Test', 'User',
    '03001234567', 'Test Address', 'Lahore', 'Punjab', '12345', 'customer', 0
]);

if ($result['success']) {
    echo "✓ User created successfully (ID: " . $conn->insert_id . ")<br>";
    
    // Now try to verify with login query
    echo "<br>Testing login with same credentials...<br>";
    $loginQuery = "SELECT user_id, username, email, password, user_type FROM users WHERE username = ? OR email = ? LIMIT 1";
    $user = getSingleResult($conn, $loginQuery, 'ss', [$testUser, $testUser]);
    
    if ($user) {
        echo "User found in database<br>";
        echo "Stored password hash: " . $user['password'] . "<br>";
        echo "Password verification: " . (verifyPassword($testPass, $user['password']) ? "✓ SUCCESS - LOGIN WORKS!" : "✗ FAILED - LOGIN BROKEN") . "<br>";
    } else {
        echo "User not found in database<br>";
    }
} else {
    echo "✗ Error creating user: " . ($result['error'] ?? 'Unknown error') . "<br>";
}

?>
