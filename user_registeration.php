<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

$userType = sanitize_input($_GET['type'] ?? 'customer');
if (!in_array($userType, ['customer', 'provider'])) {
    $userType = 'customer';
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitize_input($_POST['first_name'] ?? '');
    $lastName = sanitize_input($_POST['last_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $city = sanitize_input($_POST['city'] ?? '');
    $state = sanitize_input($_POST['state'] ?? '');
    $zipCode = sanitize_input($_POST['zip_code'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
        $error = 'All required fields must be filled';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email address';
    } elseif (strlen($username) < 4) {
        $error = 'Username must be at least 4 characters';
    } elseif (!isValidPassword($password)) {
        $error = 'Password must be at least 8 characters with uppercase, number, and special character';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (!empty($phone) && !isValidPhone($phone)) {
        $error = 'Invalid phone number format';
    } elseif (!empty($zipCode) && !isValidZipCode($zipCode)) {
        $error = 'Invalid ZIP code format';
    } else {
        // Check if username already exists
        $query = "SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1";
        $existingUser = getSingleResult($conn, $query, 'ss', [$username, $email]);
        
        if ($existingUser) {
            $error = 'Username or email already exists';
        } else {
            // Hash password
            $hashedPassword = hashPassword($password);
            
            // Register user
            if ($userType === 'provider') {
                $businessName = sanitize_input($_POST['business_name'] ?? '');
                $businessDescription = sanitize_input($_POST['business_description'] ?? '');
                $categoryId = intval($_POST['category_id'] ?? 0);
                $yearsExperience = intval($_POST['years_experience'] ?? 0);
                
                if (empty($businessName) || $categoryId <= 0) {
                    $error = 'Business name and category are required for service providers';
                } else {
                    // Insert user
                    $query = "INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code, user_type, is_verified) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $result = executeQuery($conn, $query, 'sssssssssssi', [
                        $username, $email, $hashedPassword, $firstName, $lastName,
                        $phone, $address, $city, $state, $zipCode, 'service_provider', 0
                    ]);
                    
                    if ($result['success']) {
                        $userId = $conn->insert_id;
                        // Insert service provider profile
                        $query2 = "INSERT INTO service_providers (user_id, business_name, business_description, category_id, business_phone, business_email, business_address, business_city, business_state, business_zip, years_of_experience, is_verified, is_active) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        // Type: i + ss + i + ssssss + iii = 13 total
                        $typeString = 'i' . 'ss' . 'i' . 'ssssss' . 'iii';
                        $result2 = executeQuery($conn, $query2, $typeString, [
                            $userId, $businessName, $businessDescription, $categoryId,
                            $phone, $email, $address, $city, $state, $zipCode,
                            $yearsExperience, 1, 1
                        ]);
                        
                        if ($result2['success']) {
                            $success = 'Service provider account created successfully! <a href="login.php" class="alert-link">Login here</a>';
                        } else {
                            $error = 'Error creating service provider profile. Please try again.';
                        }
                    } else {
                        $error = 'Error creating account. Please try again.';
                    }
                }
            } else {
                // Insert customer user
                $query = "INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code, user_type, is_verified) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $result = executeQuery($conn, $query, 'sssssssssssi', [
                    $username, $email, $hashedPassword, $firstName, $lastName,
                    $phone, $address, $city, $state, $zipCode, 'customer', 0
                ]);
                
                if ($result['success']) {
                    $success = 'Account created successfully! <a href="login.php" class="alert-link">Login here</a>';
                } else {
                    $error = 'Error creating account. Please try again.';
                }
            }
        }
    }
}

// Get categories for service providers
$categories = getMultipleResults($conn, "SELECT category_id, category_name FROM service_categories ORDER BY category_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h1 {
            color: #667eea;
            font-weight: bold;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.7rem;
            font-weight: 600;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .tab-btn {
            flex: 1;
            padding: 0.7rem;
            border: 2px solid #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        .tab-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        .section {
            display: none;
        }
        .section.active {
            display: block;
        }
        .alert {
            border-radius: 8px;
        }
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .password-toggle {
            cursor: pointer;
            border: 1px solid #ddd;
            background: #f8f9fa;
            transition: all 0.2s;
        }
        .password-toggle:hover {
            background: #e9ecef;
            color: #667eea;
        }
        .password-toggle:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="bi bi-person-plus"></i> Create Account</h1>
            <p class="text-muted">Join our community of service seekers and providers</p>
        </div>
        
        <!-- Type Selection Tabs -->
        <div class="tabs">
            <button class="tab-btn <?php echo $userType === 'customer' ? 'active' : ''; ?>" 
                    onclick="switchTab('customer', this)">
                <i class="bi bi-person"></i> Customer
            </button>
            <button class="tab-btn <?php echo $userType === 'provider' ? 'active' : ''; ?>" 
                    onclick="switchTab('provider', this)">
                <i class="bi bi-briefcase"></i> Service Provider
            </button>
        </div>
        
        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>?type=<?php echo $userType; ?>">
            <!-- Common Fields -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="first_name" class="form-label fw-bold">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="last_name" class="form-label fw-bold">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="username" class="form-label fw-bold">Username *</label>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="At least 4 characters" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label fw-bold">Email Address *</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Min 8 chars, uppercase, number, special char" required>
                            <button class="btn password-toggle" type="button" id="togglePassword" title="Show/Hide Password">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <small class="text-muted">Must contain: 8+ characters, uppercase, number, special character</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label fw-bold">Confirm Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn password-toggle" type="button" id="toggleConfirmPassword" title="Show/Hide Password">
                                <i class="bi bi-eye" id="toggleConfirmIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="(123) 456-7890">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="city" class="form-label fw-bold">City</label>
                        <input type="text" class="form-control" id="city" name="city">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="state" class="form-label fw-bold">State</label>
                        <input type="text" class="form-control" id="state" name="state">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="zip_code" class="form-label fw-bold">ZIP Code</label>
                        <input type="text" class="form-control" id="zip_code" name="zip_code" placeholder="12345 or 12345-6789">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label fw-bold">Address</label>
                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
            </div>
            
            <!-- Service Provider Only Fields -->
            <div id="provider-fields" style="display: <?php echo $userType === 'provider' ? 'block' : 'none'; ?>;">
                <hr>
                <h5 class="mb-3">Service Provider Information</h5>
                
                <div class="mb-3">
                    <label for="business_name" class="form-label fw-bold">Business Name *</label>
                    <input type="text" class="form-control" id="business_name" name="business_name" 
                           placeholder="Your business or professional name">
                </div>
                
                <div class="mb-3">
                    <label for="category_id" class="form-label fw-bold">Service Category *</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="years_experience" class="form-label fw-bold">Years of Experience</label>
                    <input type="number" class="form-control" id="years_experience" name="years_experience" min="0" max="70">
                </div>
                
                <div class="mb-3">
                    <label for="business_description" class="form-label fw-bold">Business Description</label>
                    <textarea class="form-control" id="business_description" name="business_description" 
                              rows="3" placeholder="Tell customers about your services and expertise"></textarea>
                </div>
            </div>
            
            <input type="hidden" name="user_type" value="<?php echo $userType; ?>">
            
            <button type="submit" class="btn btn-primary btn-register w-100 mt-3">
                <i class="bi bi-check-circle"></i> Create Account
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function switchTab(type, btn) {
            // Update form action and hidden input
            const form = document.querySelector('form');
            form.action = '<?php echo $_SERVER['PHP_SELF']; ?>?type=' + type;
            
            // Show/hide provider fields
            const providerFields = document.getElementById('provider-fields');
            if (type === 'provider') {
                providerFields.style.display = 'block';
                document.getElementById('business_name').required = true;
                document.getElementById('category_id').required = true;
            } else {
                providerFields.style.display = 'none';
                document.getElementById('business_name').required = false;
                document.getElementById('category_id').required = false;
            }
            
            // Update button styles
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function(e) {
            e.preventDefault();
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });

        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function(e) {
            e.preventDefault();
            const passwordInput = document.getElementById('confirm_password');
            const toggleIcon = document.getElementById('toggleConfirmIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
