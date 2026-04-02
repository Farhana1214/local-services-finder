<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();
requireUserType('service_provider');

$provider_id = $_SESSION['user_id'];

// Get provider statistics
$statsQuery = "SELECT 
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as jobs_completed,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_jobs,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_jobs,
    COALESCE(SUM(CASE WHEN p.payment_status = 'completed' THEN p.amount ELSE 0 END), 0) as total_earned,
    COUNT(DISTINCT b.user_id) as total_customers
FROM bookings b
LEFT JOIN payments p ON b.booking_id = p.booking_id
WHERE b.provider_id = ?";
$stats = getSingleResult($conn, $statsQuery, 'i', [$provider_id]);

// Get upcoming bookings
$bookingsQuery = "SELECT b.*, s.service_name, u.first_name, u.last_name, u.phone, u.email, u.city
                 FROM bookings b
                 JOIN services s ON b.service_id = s.service_id
                 JOIN users u ON b.user_id = u.user_id
                 WHERE b.provider_id = ? AND b.status IN ('pending', 'confirmed', 'in_progress')
                 ORDER BY b.service_date ASC, b.service_time ASC
                 LIMIT 10";
$bookings = getMultipleResults($conn, $bookingsQuery, 'i', [$provider_id]);

// Get provider's own profile for ratings
$profileQuery = "SELECT sp.*, 
    COUNT(DISTINCT r.review_id) as total_reviews,
    COALESCE(AVG(r.rating), 0) as avg_rating
FROM service_providers sp
LEFT JOIN reviews r ON sp.provider_id = r.provider_id
WHERE sp.provider_id = ?
GROUP BY sp.provider_id";
$provider = getSingleResult($conn, $profileQuery, 'i', [$provider_id]);

// Get provider's services
$servicesQuery = "SELECT * FROM services WHERE provider_id = ? ORDER BY service_name ASC";
$services = getMultipleResults($conn, $servicesQuery, 'i', [$provider_id]);

// Handle actions
if ($_POST['action'] ?? false) {
    $action = sanitize_input($_POST['action']);
    
    if ($action === 'confirm_booking' && isset($_POST['booking_id'])) {
        $booking_id = (int)$_POST['booking_id'];
        executeQuery($conn, "UPDATE bookings SET status = 'confirmed' WHERE booking_id = ? AND provider_id = ?", 
                    'ii', [$booking_id, $provider_id]);
    }
    elseif ($action === 'start_service' && isset($_POST['booking_id'])) {
        $booking_id = (int)$_POST['booking_id'];
        executeQuery($conn, "UPDATE bookings SET status = 'in_progress' WHERE booking_id = ? AND provider_id = ?", 
                    'ii', [$booking_id, $provider_id]);
    }
    elseif ($action === 'complete_service' && isset($_POST['booking_id'])) {
        $booking_id = (int)$_POST['booking_id'];
        executeQuery($conn, "UPDATE bookings SET status = 'completed' WHERE booking_id = ? AND provider_id = ?", 
                    'ii', [$booking_id, $provider_id]);
    }
    
    $_SESSION['success_message'] = 'Booking updated successfully';
    header('Location: provider-dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .dashboard-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 8px; padding: 1.5rem; border-top: 4px solid #667eea; text-align: center; }
        .stat-number { font-size: 1.8rem; font-weight: bold; color: #667eea; }
        .stat-label { color: #999; font-size: 0.9rem; text-transform: uppercase; }
        .booking-card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid; }
        .booking-pending { border-left-color: #ffc107; }
        .booking-confirmed { border-left-color: #17a2b8; }
        .booking-in-progress { border-left-color: #007bff; }
        .service-item { background: white; padding: 1rem; margin-bottom: 1rem; border-radius: 6px; }
        .rating-badge { background: #ffc107; color: #333; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-briefcase"></i> Service Finder</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-white me-2">Provider: <?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?></span>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <?php displaySessionMessage(); ?>
    
    <div class="dashboard-header">
        <div class="container">
            <h1><i class="bi bi-graph-up"></i> Provider Dashboard</h1>
            <p class="mb-0">Business overview and booking management</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['jobs_completed'] ?? 0; ?></div>
                    <div class="stat-label">Completed Jobs</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatCurrency($stats['total_earned'] ?? 0); ?></div>
                    <div class="stat-label">Total Earned</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_customers'] ?? 0; ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($provider['avg_rating'] ?? 0, 1); ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
        </div>
        
        <!-- Pending Actions -->
        <?php if ($stats['pending_jobs'] > 0 || $stats['in_progress_jobs'] > 0): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-bell"></i>
                You have <strong><?php echo ($stats['pending_jobs'] ?? 0) + ($stats['in_progress_jobs'] ?? 0); ?></strong> 
                active bookings requiring attention
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Upcoming Bookings -->
            <div class="col-lg-8">
                <h3 class="mb-3"><i class="bi bi-calendar-check"></i> Upcoming Bookings</h3>
                
                <?php if ($bookings): ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card booking-<?php echo $booking['status']; ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5><?php echo htmlspecialchars($booking['service_name']); ?></h5>
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> <?php echo formatDate($booking['service_date']); ?> 
                                        at <?php echo formatTime($booking['service_time']); ?>
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($booking['service_location']); ?>
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($booking['phone'] ?? ''); ?> | 
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($booking['email'] ?? ''); ?>
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div style="margin-bottom: 1rem;">
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] === 'pending' ? 'warning' : 
                                                 ($booking['status'] === 'confirmed' ? 'info' : 'primary');
                                        ?>"><?php echo ucfirst($booking['status']); ?></span>
                                    </div>
                                    <div style="font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem;">
                                        <?php echo formatCurrency($booking['final_amount']); ?>
                                    </div>
                                    
                                    <form method="POST" style="display: contents;">
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <button type="submit" name="action" value="confirm_booking" class="btn btn-sm btn-success w-100 mb-2" onclick="return confirm('Confirm this booking?')">
                                                <i class="bi bi-check-circle"></i> Confirm
                                            </button>
                                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                                            <button type="submit" name="action" value="start_service" class="btn btn-sm btn-primary w-100 mb-2" onclick="return confirm('Mark as in progress?')">
                                                <i class="bi bi-play-circle"></i> Start Service
                                            </button>
                                        <?php elseif ($booking['status'] === 'in_progress'): ?>
                                            <button type="submit" name="action" value="complete_service" class="btn btn-sm btn-success w-100 mb-2" onclick="return confirm('Mark as completed?')">
                                                <i class="bi bi-check-circle"></i> Complete
                                            </button>
                                        <?php endif; ?>
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle"></i> No upcoming bookings at the moment
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Services Management -->
            <div class="col-lg-4">
                <h3 class="mb-3"><i class="bi bi-tools"></i> Your Services</h3>
                
                <?php if ($services): ?>
                    <?php foreach ($services as $service): ?>
                        <div class="service-item">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <h6><?php echo htmlspecialchars($service['service_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars(truncateText($service['service_description'], 60)); ?>
                                    </small><br>
                                    <small class="text-muted">
                                        PKR <?php echo number_format($service['price'], 0); ?> • 
                                        <?php echo $service['duration_hours']; ?> hours
                                    </small>
                                </div>
                                <div>
                                    <a href="manage-services.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> No services listed yet
                    </div>
                <?php endif; ?>
                
                <a href="manage-services.php" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-plus-circle"></i> Manage Services
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>