<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();
requireUserType('service_provider');

$provider_id = $_SESSION['user_id'];

// Get provider's own details
$provider = getSingleResult($conn, "
    SELECT sp.*, u.first_name, u.last_name, u.phone, u.email, u.address, u.city, u.state, u.zip_code,
           c.category_name,
           COUNT(DISTINCT s.service_id) as service_count,
           COUNT(DISTINCT b.booking_id) as booking_count,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(DISTINCT r.review_id) as review_count
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    JOIN service_categories c ON sp.category_id = c.category_id
    LEFT JOIN services s ON sp.provider_id = s.provider_id
    LEFT JOIN bookings b ON sp.provider_id = b.provider_id
    LEFT JOIN reviews r ON sp.provider_id = r.provider_id
    WHERE sp.provider_id = ?
    GROUP BY sp.provider_id
", 'i', [$provider_id]);

// Get services
$services = getMultipleResults($conn, "
    SELECT * FROM services WHERE provider_id = ? ORDER BY service_name
", 'i', [$provider_id]);

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize_input($_POST['action']);
    
    if ($action === 'update_profile') {
        $business_name = sanitize_input($_POST['business_name'] ?? '');
        $business_description = sanitize_input($_POST['business_description'] ?? '');
        $business_phone = sanitize_input($_POST['business_phone'] ?? '');
        $business_email = sanitize_input($_POST['business_email'] ?? '');
        $business_address = sanitize_input($_POST['business_address'] ?? '');
        $business_city = sanitize_input($_POST['business_city'] ?? '');
        $business_state = sanitize_input($_POST['business_state'] ?? '');
        $business_zip = sanitize_input($_POST['business_zip'] ?? '');
        $years_exp = intval($_POST['years_experience'] ?? 0);
        
        if (empty($business_name)) {
            $error = 'Business name is required';
        } else {
            $query = "UPDATE service_providers 
                      SET business_name = ?, business_description = ?, business_phone = ?, 
                          business_email = ?, business_address = ?, business_city = ?, 
                          business_state = ?, business_zip = ?, years_of_experience = ?
                      WHERE provider_id = ?";
            
            $result = executeQuery($conn, $query, 'ssssssssii', [
                $business_name, $business_description, $business_phone, $business_email,
                $business_address, $business_city, $business_state, $business_zip,
                $years_exp, $provider_id
            ]);
            
            if ($result['success']) {
                $success = '✅ Profile updated successfully!';
                // Refresh provider data
                $provider = getSingleResult($conn, "
                    SELECT sp.*, u.first_name, u.last_name, u.phone, u.email, u.address, u.city, u.state, u.zip_code,
                           c.category_name,
                           COUNT(DISTINCT s.service_id) as service_count,
                           COUNT(DISTINCT b.booking_id) as booking_count,
                           COALESCE(AVG(r.rating), 0) as avg_rating,
                           COUNT(DISTINCT r.review_id) as review_count
                    FROM service_providers sp
                    JOIN users u ON sp.user_id = u.user_id
                    JOIN service_categories c ON sp.category_id = c.category_id
                    LEFT JOIN services s ON sp.provider_id = s.provider_id
                    LEFT JOIN bookings b ON sp.provider_id = b.provider_id
                    LEFT JOIN reviews r ON sp.provider_id = r.provider_id
                    WHERE sp.provider_id = ?
                    GROUP BY sp.provider_id
                ", 'i', [$provider_id]);
            } else {
                $error = 'Error updating profile';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Provider Profile - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .profile-card { background: white; border-radius: 10px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-box { background: white; padding: 1.5rem; border-radius: 8px; text-align: center; margin-bottom: 1rem; border-top: 4px solid #667eea; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #667eea; }
        .stat-label { color: #999; font-size: 0.9rem; text-transform: uppercase; margin-top: 0.5rem; }
        .rating-stars { color: #ffc107; font-size: 1.2rem; }
        .service-list { background: white; border-radius: 8px; padding: 1.5rem; }
        .service-item { padding: 1rem; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .service-item:last-child { border-bottom: none; }
        .btn-edit { background: #667eea; color: white; border: none; }
        .btn-edit:hover { background: #5568d3; color: white; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'navbar.php'; ?>
    
    <!-- Header -->
    <div class="header">
        <div class="container">
            <h1><i class="bi bi-briefcase"></i> My Provider Profile</h1>
            <p class="mb-0">View and manage your business details</p>
        </div>
    </div>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Stats -->
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $provider['service_count']; ?></div>
                    <div class="stat-label">Services Listed</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $provider['booking_count']; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">
                        <span class="rating-stars">★</span> <?php echo number_format($provider['avg_rating'], 1); ?>
                    </div>
                    <div class="stat-label"><?php echo $provider['review_count']; ?> Reviews</div>
                </div>
            </div>

            <!-- Profile Details -->
            <div class="col-md-9">
                <div class="profile-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h3><i class="bi bi-building"></i> Business Information</h3>
                        <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Business Name</h6>
                            <p class="h5"><?php echo htmlspecialchars($provider['business_name']); ?></p>

                            <h6 class="text-muted mt-3">Category</h6>
                            <p class="h5"><span class="badge bg-primary"><?php echo htmlspecialchars($provider['category_name']); ?></span></p>

                            <h6 class="text-muted mt-3">Experience</h6>
                            <p class="h5"><?php echo $provider['years_of_experience']; ?> years</p>

                            <h6 class="text-muted mt-3">Status</h6>
                            <p>
                                <span class="badge bg-<?php echo $provider['is_verified'] ? 'success' : 'warning'; ?>">
                                    <?php echo $provider['is_verified'] ? '✅ Verified' : '⏳ Pending Verification'; ?>
                                </span>
                                <span class="badge bg-<?php echo $provider['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $provider['is_active'] ? '🟢 Active' : '🔴 Inactive'; ?>
                                </span>
                            </p>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-muted">Description</h6>
                            <p><?php echo htmlspecialchars($provider['business_description']); ?></p>

                            <h6 class="text-muted mt-3">Contact Information</h6>
                            <p>
                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($provider['business_phone']); ?><br>
                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($provider['business_email']); ?>
                            </p>

                            <h6 class="text-muted mt-3">Address</h6>
                            <p>
                                <?php echo htmlspecialchars($provider['business_address']); ?><br>
                                <?php echo htmlspecialchars($provider['business_city'] . ', ' . $provider['business_state'] . ' ' . $provider['business_zip']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Services -->
                <div class="service-list">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3><i class="bi bi-tags"></i> Your Services (<?php echo count($services); ?>)</h3>
                        <a href="manage-services.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Add Service
                        </a>
                    </div>

                    <?php if ($services): ?>
                        <?php foreach ($services as $service): ?>
                            <div class="service-item">
                                <div>
                                    <h6><?php echo htmlspecialchars($service['service_name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars(truncateText($service['service_description'], 60)); ?></small><br>
                                    <small class="text-muted">
                                        PKR <?php echo number_format($service['price'], 0); ?> • 
                                        <?php echo $service['duration_hours']; ?> hours
                                    </small>
                                </div>
                                <div>
                                    <a href="manage-services.php?edit=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">No services added yet. <a href="manage-services.php">Add one now!</a></p>
                    <?php endif; ?>
                </div>

                <!-- Quick Links -->
                <div style="margin-top: 2rem; padding: 1.5rem; background: white; border-radius: 8px;">
                    <h5 class="mb-3">📱 Quick Links</h5>
                    <a href="provider-dashboard.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-graph-up"></i> Dashboard
                    </a>
                    <a href="manage-services.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-tools"></i> Manage Services
                    </a>
                    <a href="my-bookings.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-calendar-check"></i> My Bookings
                    </a>
                    <a href="my-reviews.php" class="btn btn-outline-primary">
                        <i class="bi bi-star"></i> Reviews
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Business Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label for="business_name" class="form-label">Business Name</label>
                            <input type="text" class="form-control" id="business_name" name="business_name" 
                                   value="<?php echo htmlspecialchars($provider['business_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="business_description" class="form-label">Description</label>
                            <textarea class="form-control" id="business_description" name="business_description" rows="4"><?php echo htmlspecialchars($provider['business_description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="business_phone" name="business_phone" 
                                           value="<?php echo htmlspecialchars($provider['business_phone']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="business_email" name="business_email" 
                                           value="<?php echo htmlspecialchars($provider['business_email']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="business_address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="business_address" name="business_address" 
                                   value="<?php echo htmlspecialchars($provider['business_address']); ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="business_city" name="business_city" 
                                           value="<?php echo htmlspecialchars($provider['business_city']); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="business_state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="business_state" name="business_state" 
                                           value="<?php echo htmlspecialchars($provider['business_state']); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="business_zip" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" id="business_zip" name="business_zip" 
                                           value="<?php echo htmlspecialchars($provider['business_zip']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="years_experience" class="form-label">Years of Experience</label>
                            <input type="number" class="form-control" id="years_experience" name="years_experience" 
                                   value="<?php echo $provider['years_of_experience']; ?>" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
