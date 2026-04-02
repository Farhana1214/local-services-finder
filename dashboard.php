<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();

// Get user's location from profile
$user_data = getSingleResult($conn, "SELECT * FROM users WHERE user_id = ?", 'i', [$_SESSION['user_id']]);
$user_city = $user_data['city'] ?? USER_DEFAULT_CITY;
$user_state = $user_data['state'] ?? USER_DEFAULT_PROVINCE;

// Only allow customers
if (getUserType() !== 'customer') {
    header('Location: provider-dashboard.php');
    exit();
}

// Get user info
$userQuery = "SELECT * FROM users WHERE user_id = ?";
$user = getSingleResult($conn, $userQuery, 'i', [$_SESSION['user_id']]);

// Get user's bookings
$bookings = getUserBookings($conn, $_SESSION['user_id']);

// Get favorites
$favQuery = "SELECT sp.*, u.city, u.state
            FROM favorites f
            JOIN service_providers sp ON f.provider_id = sp.provider_id
            JOIN users u ON sp.user_id = u.user_id
            WHERE f.user_id = ?
            LIMIT 6";
$favorites = getMultipleResults($conn, $favQuery, 'i', [$_SESSION['user_id']]);

// Get nearby service providers in user's city
$nearbyQuery = "SELECT sp.*, u.email, u.phone, u.city, u.state,
               c.category_name,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(DISTINCT r.review_id) as total_reviews
               FROM service_providers sp
               JOIN users u ON sp.user_id = u.user_id
               JOIN service_categories c ON sp.category_id = c.category_id
               LEFT JOIN reviews r ON sp.provider_id = r.provider_id
               WHERE sp.is_active = 1 AND sp.is_verified = 1 AND u.city = ?
               GROUP BY sp.provider_id
               ORDER BY avg_rating DESC, total_reviews DESC
               LIMIT 6";
$nearby_providers = getMultipleResults($conn, $nearbyQuery, 's', [$user_city]);

// Get statistics
$statsQuery = "SELECT 
              COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
              COUNT(*) as total_bookings,
              SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as total_spent
              FROM bookings WHERE user_id = ?";
$stats = getSingleResult($conn, $statsQuery, 'i', [$_SESSION['user_id']]);

// Get upcoming bookings
$upcomingQuery = "SELECT b.*, s.service_name, sp.business_name
                 FROM bookings b
                 JOIN services s ON b.service_id = s.service_id
                 JOIN service_providers sp ON b.provider_id = sp.provider_id
                 WHERE b.user_id = ? AND b.service_date >= CURDATE() AND b.status != 'cancelled'
                 ORDER BY b.service_date ASC
                 LIMIT 5";
$upcoming = getMultipleResults($conn, $upcomingQuery, 'i', [$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .stat-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .card-custom {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }
        .card-body-custom {
            padding: 1.5rem;
        }
        .booking-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .booking-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .booking-info h6 {
            margin: 0.5rem 0 0 0;
            font-weight: bold;
        }
        .booking-info p {
            margin: 0.3rem 0;
            color: #666;
            font-size: 0.9rem;
        }
        .favorite-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
            margin-bottom: 1rem;
        }
        .favorite-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            color: inherit;
        }
        .favorite-image {
            width: 100%;
            height: 150px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
        .favorite-content {
            padding: 1rem;
        }
        .favorite-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.3rem;
        }
        .favorite-city {
            color: #666;
            font-size: 0.85rem;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-custom:hover {
            background: linear-gradient(135deg, #5568d3 0%, #65398a 100%);
            color: white;
        }
        .nav-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .nav-sidebar .nav-link {
            color: #333;
            padding: 1rem 1.5rem;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        .nav-sidebar .nav-link:hover,
        .nav-sidebar .nav-link.active {
            background: #f8f9fa;
            border-left-color: #667eea;
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-briefcase"></i> Service Finder
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="search.php">
                            <i class="bi bi-search"></i> Search Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-gear"></i> Settings</a></li>
                            <li><a class="dropdown-item" href="my-reviews.php"><i class="bi bi-star"></i> My Reviews</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1><i class="bi bi-speedometer2"></i> Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
            <p class="lead mb-0">Manage your bookings and discover amazing services</p>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                            <div class="stat-value"><?php echo $stats['total_bookings'] ?? 0; ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon" style="color: #28a745;"><i class="bi bi-check-circle"></i></div>
                            <div class="stat-value"><?php echo $stats['completed_bookings'] ?? 0; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon" style="color: #ffc107;"><i class="bi bi-cash-coin"></i></div>
                            <div class="stat-value"><?php echo formatCurrency($stats['total_spent'] ?? 0); ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Bookings -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar"></i> Upcoming Bookings
                        </h5>
                    </div>
                    <div class="card-body-custom">
                        <?php if (count($upcoming) > 0): ?>
                            <?php foreach ($upcoming as $booking): ?>
                                <div class="booking-item">
                                    <div class="booking-info">
                                        <h6><?php echo htmlspecialchars($booking['service_name']); ?></h6>
                                        <p><i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($booking['business_name']); ?></p>
                                        <p><i class="bi bi-calendar"></i> <?php echo formatDate($booking['service_date']); ?> at <?php echo htmlspecialchars($booking['service_time']); ?></p>
                                    </div>
                                    <div>
                                        <?php echo getStatusBadge($booking['status']); ?>
                                        <br>
                                        <a href="booking-details.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="mt-2">No upcoming bookings</p>
                                <a href="search.php" class="btn btn-custom btn-sm">Find Services</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- All Bookings -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Booking History</h5>
                            <a href="my-bookings.php" class="btn btn-light btn-sm">View All</a>
                        </div>
                    </div>
                    <div class="card-body-custom">
                        <?php if (count($bookings) > 0): ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach (array_slice($bookings, 0, 5) as $booking): ?>
                                    <div class="booking-item">
                                        <div class="booking-info">
                                            <h6><?php echo htmlspecialchars($booking['service_name']); ?></h6>
                                            <p><i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($booking['business_name']); ?></p>
                                            <p><i class="bi bi-calendar"></i> <?php echo formatDate($booking['booking_date']); ?></p>
                                        </div>
                                        <div>
                                            <?php echo getStatusBadge($booking['status']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo formatCurrency($booking['final_amount']); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="mt-2">No bookings yet</p>
                                <a href="search.php" class="btn btn-custom btn-sm">Book a Service</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="d-grid gap-2">
                            <a href="search.php" class="btn btn-custom btn-lg">
                                <i class="bi bi-search"></i> Search Services
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="bi bi-gear"></i> My Profile
                            </a>
                            <a href="my-reviews.php" class="btn btn-outline-primary">
                                <i class="bi bi-star"></i> My Reviews
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Nearby Service Providers -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5 class="mb-0">
                            <i class="bi bi-geo-alt"></i> Services in <?php echo htmlspecialchars($user_city); ?>
                        </h5>
                    </div>
                    <div class="card-body-custom">
                        <?php if (count($nearby_providers) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($nearby_providers as $provider): ?>
                                    <a href="provider-profile.php?id=<?php echo $provider['provider_id']; ?>" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                       style="border-bottom: 1px solid #eee;">
                                        <div>
                                            <div style="font-weight: 600; color: #333;">
                                                <?php echo htmlspecialchars(truncateText($provider['business_name'], 25)); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($provider['category_name']); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-warning text-dark">
                                            ⭐ <?php echo number_format($provider['avg_rating'], 1); ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <a href="smart-search.php?city=<?php echo urlencode($user_city); ?>" 
                               class="btn btn-outline-primary btn-sm w-100 mt-2">
                                View All in <?php echo htmlspecialchars($user_city); ?>
                            </a>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-geo-alt" style="font-size: 2rem; color: #ccc;"></i>
                                <p class="mt-2">No services found in your city</p>
                                <a href="smart-search.php" class="btn btn-custom btn-sm">Browse All Services</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Favorite Providers -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5 class="mb-0">
                            <i class="bi bi-heart"></i> Favorite Providers
                        </h5>
                    </div>
                    <div class="card-body-custom">
                        <?php if (count($favorites) > 0): ?>
                            <div class="row">
                                <?php foreach ($favorites as $fav): ?>
                                    <div class="col-md-6">
                                        <a href="provider-profile.php?id=<?php echo $fav['provider_id']; ?>" class="favorite-card">
                                            <div class="favorite-image">
                                                <i class="bi bi-briefcase"></i>
                                            </div>
                                            <div class="favorite-content">
                                                <div class="favorite-name"><?php echo htmlspecialchars(truncateText($fav['business_name'], 20)); ?></div>
                                                <div class="favorite-city"><?php echo htmlspecialchars($fav['city']); ?></div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-heart" style="font-size: 2rem; color: #ccc;"></i>
                                <p class="mt-2">No favorites yet</p>
                                <p style="font-size: 0.9rem; margin: 0;">Add providers you love to your favorites</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Chatbot Support -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-robot"></i> Smart Assistant</h5>
                    </div>
                    <div class="card-body-custom">
                        <p>Need service recommendations? Ask our AI chatbot anytime!</p>
                        <p><small>Get instant service suggestions, price estimates & nearby providers</small></p>
                        <a href="chatbot.php" class="btn btn-primary w-100">
                            <i class="bi bi-chat-dots"></i> Open Chatbot 💬
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
