<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();

$success = '';
$error = '';

// Get user data
$user = getSingleResult($conn, "SELECT * FROM users WHERE user_id = ?", 'i', [$_SESSION['user_id']]);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $city = sanitize_input($_POST['city'] ?? '');
    $state = sanitize_input($_POST['state'] ?? '');
    $zip_code = sanitize_input($_POST['zip_code'] ?? '');
    
    if (empty($first_name) || empty($last_name)) {
        $error = 'Name is required';
    } else {
        $query = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ? WHERE user_id = ?";
        $result = executeQuery($conn, $query, 'sssssssi', [
            $first_name, $last_name, $phone, $address, $city, $state, $zip_code, $_SESSION['user_id']
        ]);
        
        if ($result['success']) {
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $success = 'Profile updated successfully!';
            $user = getSingleResult($conn, "SELECT * FROM users WHERE user_id = ?", 'i', [$_SESSION['user_id']]);
        } else {
            $error = 'Error updating profile';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .profile-card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 2rem; }
        .profile-avatar { width: 120px; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; margin: -3rem auto 1rem; }
        .form-section h5 { margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-briefcase"></i> Service Finder</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="profile-header">
        <div class="container">
            <h1><i class="bi bi-person-circle"></i> My Profile</h1>
        </div>
    </div>
    
    <div class="container" style="max-width: 700px;">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="profile-card">
            <div class="profile-avatar">
                <i class="bi bi-person"></i>
            </div>
            
            <h3 class="text-center mb-4"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
            
            <form method="POST">
                <div class="form-section">
                    <h5>Personal Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label fw-bold">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label fw-bold">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Email (Read-only)</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h5>Address</h5>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label fw-bold">Street Address</label>
                        <input type="text" class="form-control" id="address" name="address" 
                               value="<?php echo htmlspecialchars($user['address']); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="city" class="form-label fw-bold">City</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($user['city']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="state" class="form-label fw-bold">State</label>
                            <input type="text" class="form-control" id="state" name="state" 
                                   value="<?php echo htmlspecialchars($user['state']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="zip_code" class="form-label fw-bold">ZIP Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                   value="<?php echo htmlspecialchars($user['zip_code']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 0.7rem;">
                        <i class="bi bi-check-circle"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Account Info -->
        <div class="profile-card mt-3">
            <h5>Account Information</h5>
            <div style="margin: 1rem 0;">
                <label class="form-label fw-bold">Account Type</label>
                <p><?php echo ucfirst(str_replace('_', ' ', $user['user_type'])); ?></p>
            </div>
            <div style="margin: 1rem 0;">
                <label class="form-label fw-bold">Member Since</label>
                <p><?php echo formatDate($user['created_at'], 'F j, Y'); ?></p>
            </div>
            <button class="btn btn-outline-danger" onclick="alert('Change password feature coming soon')">
                <i class="bi bi-key"></i> Change Password
            </button>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>