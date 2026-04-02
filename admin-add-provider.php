<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

// Simple auth check - in production use proper admin verification
$admin_key = sanitize_input($_GET['key'] ?? '');
if ($admin_key !== 'admin123') {
    die('Access denied. Use ?key=admin123');
}

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $city = sanitize_input($_POST['city'] ?? '');
    $business_name = sanitize_input($_POST['business_name'] ?? '');
    $business_description = sanitize_input($_POST['business_description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $years_experience = intval($_POST['years_experience'] ?? 0);
    $rating = floatval($_POST['rating'] ?? 4.5);

    // Validation
    if (empty($first_name) || empty($email) || empty($business_name) || empty($category_id) || empty($city)) {
        $error = 'Please fill all required fields';
    } else {
        // Check if email exists
        $existing = getSingleResult($conn, "SELECT user_id FROM users WHERE email = ?", 's', [$email]);
        if ($existing) {
            $error = 'Email already exists';
        } else {
            // Create user account
            $username = strtolower(str_replace(' ', '_', $first_name . '_' . $last_name));
            $password = hashPassword('Provider123!');
            
            $query = "INSERT INTO users (username, email, password, first_name, last_name, phone, city, user_type, is_verified) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $result = executeQuery($conn, $query, 'ssssssssi', [
                $username, $email, $password, $first_name, $last_name,
                $phone, $city, 'service_provider', 1
            ]);
            
            if ($result['success']) {
                $user_id = $conn->insert_id;
                
                // Create service provider profile
                $query2 = "INSERT INTO service_providers (user_id, business_name, business_description, category_id, 
                          business_phone, business_email, business_city, years_of_experience, average_rating, is_verified, is_active) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $result2 = executeQuery($conn, $query2, 'isssissidi', [
                    $user_id, $business_name, $business_description, $category_id,
                    $phone, $email, $city, $years_experience, $rating, 1, 1
                ]);
                
                if ($result2['success']) {
                    $message = "✅ Provider added successfully! Email: " . htmlspecialchars($email) . " | Password: Provider123!";
                } else {
                    $error = 'Error creating provider profile';
                }
            } else {
                $error = 'Error creating user account';
            }
        }
    }
}

// Get categories
$categories = getMultipleResults($conn, "SELECT category_id, category_name FROM service_categories ORDER BY category_name");

// Get cities
$cities = getMultipleResults($conn, "SELECT DISTINCT city FROM users WHERE city IS NOT NULL AND city != '' ORDER BY city");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service Provider - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.7rem 2rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .required {
            color: red;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header p-4">
                <h2><i class="bi bi-person-plus"></i> Add Service Provider</h2>
                <p class="mb-0 mt-2 small">Quickly add a new service provider to the system</p>
            </div>
            
            <div class="card-body p-4">
                <div class="info-box">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Default Password:</strong> Provider123! (User can change after login)
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- Personal Info Section -->
                    <h5 class="mb-3 mt-4" style="color: #667eea;"><i class="bi bi-person"></i> Personal Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name <span class="required">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="required">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>

                    <!-- Business Info Section -->
                    <h5 class="mb-3 mt-4" style="color: #667eea;"><i class="bi bi-briefcase"></i> Business Information</h5>

                    <div class="mb-3">
                        <label for="business_name" class="form-label">Business Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="business_name" name="business_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="business_description" class="form-label">Business Description</label>
                        <textarea class="form-control" id="business_description" name="business_description" rows="3" placeholder="Service description"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Service Category <span class="required">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>">
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="city" class="form-label">City <span class="required">*</span></label>
                                <input type="text" class="form-control" id="city" name="city" list="city-list" required>
                                <datalist id="city-list">
                                    <?php foreach ($cities as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['city']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="years_experience" class="form-label">Years of Experience</label>
                                <input type="number" class="form-control" id="years_experience" name="years_experience" min="0" value="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rating" class="form-label">Initial Rating</label>
                                <input type="number" class="form-control" id="rating" name="rating" min="0" max="5" step="0.1" value="4.5">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle"></i> Add Provider
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <small class="text-muted">
                        <a href="index.php"><i class="bi bi-house"></i> Back to Home</a> | 
                        <a href="search.php"><i class="bi bi-search"></i> Search Services</a>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
