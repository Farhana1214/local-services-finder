<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();
requireUserType('service_provider');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get provider profile
$provider = getSingleResult($conn, "SELECT * FROM service_providers WHERE user_id = ?", 'i', [$user_id]);
if (!$provider) {
    header("Location: provider-dashboard.php");
    exit();
}

$provider_id = $provider['provider_id'];

// Handle add/edit service
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = sanitize_input($_POST['action'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);
    $service_name = sanitize_input($_POST['service_name'] ?? '');
    $service_description = sanitize_input($_POST['service_description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration = floatval($_POST['duration'] ?? 1);
    $is_available = intval($_POST['is_available'] ?? 1);

    if (empty($service_name) || $price <= 0 || $duration <= 0) {
        $error = 'Please fill all fields correctly';
    } else {
        if ($action === 'add') {
            // Add new service
            $query = "INSERT INTO services (provider_id, service_name, service_description, category_id, price, duration_hours, is_available) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $result = executeQuery($conn, $query, 'isssidi', [
                $provider_id, $service_name, $service_description, $provider['category_id'],
                $price, $duration, $is_available
            ]);
            
            if ($result['success']) {
                $message = "✅ Service added successfully!";
            } else {
                $error = 'Error adding service';
            }
        } elseif ($action === 'edit') {
            // Edit existing service
            $query = "UPDATE services SET service_name = ?, service_description = ?, price = ?, duration_hours = ?, is_available = ? 
                      WHERE service_id = ? AND provider_id = ?";
            
            $result = executeQuery($conn, $query, 'ssdidi', [
                $service_name, $service_description, $price, $duration, $is_available, $service_id, $provider_id
            ]);
            
            if ($result['success']) {
                $message = "✅ Service updated successfully!";
            } else {
                $error = 'Error updating service';
            }
        } elseif ($action === 'delete') {
            // Delete service
            $query = "DELETE FROM services WHERE service_id = ? AND provider_id = ?";
            $result = executeQuery($conn, $query, 'ii', [$service_id, $provider_id]);
            
            if ($result['success']) {
                $message = "✅ Service deleted successfully!";
            } else {
                $error = 'Error deleting service';
            }
        }
    }
}

// Get provider's services
$services = getMultipleResults($conn, "SELECT * FROM services WHERE provider_id = ? ORDER BY service_name ASC", 'i', [$provider_id]);

// Get categories
$categories = getMultipleResults($conn, "SELECT category_id, category_name FROM service_categories ORDER BY category_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Provider Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .service-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-bottom: 1rem;
        }
        .price-badge {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-weight: bold;
        }
        .duration-badge {
            background: #17a2b8;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-weight: 600;
        }
        .status-badge.available {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.unavailable {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-briefcase"></i> Service Finder
            </a>
            <div class="ms-auto">
                <a href="provider-dashboard.php" class="btn btn-light btn-sm">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-light btn-sm ms-2">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-gear"></i> Manage Services</h1>
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="bi bi-plus-circle"></i> Add New Service
            </button>
        </div>

        <!-- Business Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shop"></i> Business Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Business Name:</strong> <?php echo htmlspecialchars($provider['business_name']); ?></p>
                        <p><strong>Category:</strong> 
                            <?php 
                            $cat = getSingleResult($conn, "SELECT category_name FROM service_categories WHERE category_id = ?", 'i', [$provider['category_id']]);
                            echo htmlspecialchars($cat['category_name'] ?? 'N/A');
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>City:</strong> <?php echo htmlspecialchars($provider['business_city']); ?></p>
                        <p><strong>Experience:</strong> <?php echo $provider['years_of_experience']; ?> years</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
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

        <!-- Services List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Your Services (<?php echo count($services); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($services) > 0): ?>
                    <?php foreach ($services as $service): ?>
                        <div class="service-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($service['service_name']); ?></h5>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($service['service_description']); ?></p>
                                    <div class="mt-2">
                                        <span class="price-badge">PKR <?php echo number_format($service['price'], 0); ?></span>
                                        <span class="duration-badge ms-2"><i class="bi bi-clock"></i> <?php echo $service['duration_hours']; ?> hours</span>
                                        <span class="status-badge ms-2 <?php echo $service['is_available'] ? 'available' : 'unavailable'; ?>">
                                            <?php echo $service['is_available'] ? '✓ Available' : '✗ Unavailable'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editServiceModal" 
                                            onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this service?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No services yet. <a href="#" data-bs-toggle="modal" data-bs-target="#addServiceModal">Add your first service</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Service</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="service_name" class="form-label">Service Name *</label>
                            <input type="text" class="form-control" id="service_name" name="service_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="service_description" class="form-label">Description</label>
                            <textarea class="form-control" id="service_description" name="service_description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (PKR) *</label>
                                    <input type="number" class="form-control" id="price" name="price" min="1" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (Hours) *</label>
                                    <input type="number" class="form-control" id="duration" name="duration" min="0.5" step="0.5" value="1" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_available" name="is_available" value="1" checked>
                                <label class="form-check-label" for="is_available">
                                    Available for booking
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Service</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="service_id" id="edit_service_id">
                        
                        <div class="mb-3">
                            <label for="edit_service_name" class="form-label">Service Name *</label>
                            <input type="text" class="form-control" id="edit_service_name" name="service_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_service_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_service_description" name="service_description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_price" class="form-label">Price (PKR) *</label>
                                    <input type="number" class="form-control" id="edit_price" name="price" min="1" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_duration" class="form-label">Duration (Hours) *</label>
                                    <input type="number" class="form-control" id="edit_duration" name="duration" min="0.5" step="0.5" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_available" name="is_available" value="1">
                                <label class="form-check-label" for="edit_is_available">
                                    Available for booking
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-check-circle"></i> Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editService(service) {
            document.getElementById('edit_service_id').value = service.service_id;
            document.getElementById('edit_service_name').value = service.service_name;
            document.getElementById('edit_service_description').value = service.service_description;
            document.getElementById('edit_price').value = service.price;
            document.getElementById('edit_duration').value = service.duration_hours;
            document.getElementById('edit_is_available').checked = service.is_available == 1;
        }
    </script>
</body>
</html>
