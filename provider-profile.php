<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

// Get provider ID from URL
$provider_id = intval($_GET['id'] ?? 0);

if ($provider_id <= 0) {
    header('Location: search.php');
    exit();
}

// Get provider details
$provider = getProviderProfile($conn, $provider_id);

if (!$provider) {
    header('Location: search.php');
    exit();
}

// Get provider's services
$services = getServicesByProvider($conn, $provider_id);

// Get provider's reviews
$reviewQuery = "SELECT r.*, u.first_name, u.last_name 
                FROM reviews r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.provider_id = ?
                ORDER BY r.created_at DESC
                LIMIT 10";
$reviews = getMultipleResults($conn, $reviewQuery, 'i', [$provider_id]);

// Get average rating breakdown
$ratingBreakdownQuery = "SELECT 
                        COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                        COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                        COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                        COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                        COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star,
                        COUNT(*) as total
                        FROM reviews WHERE provider_id = ?";
$ratingBreakdown = getSingleResult($conn, $ratingBreakdownQuery, 'i', [$provider_id]);

// Check if user has favorited this provider
$isFavorite = false;
if (isLoggedIn()) {
    $favQuery = "SELECT favorite_id FROM favorites WHERE user_id = ? AND provider_id = ?";
    $favorite = getSingleResult($conn, $favQuery, 'ii', [$_SESSION['user_id'], $provider_id]);
    $isFavorite = $favorite !== null;
}

// Handle add to favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    if ($_POST['action'] === 'add_favorite') {
        $query = "INSERT INTO favorites (user_id, provider_id) VALUES (?, ?)";
        executeQuery($conn, $query, 'ii', [$_SESSION['user_id'], $provider_id]);
        $isFavorite = true;
    } elseif ($_POST['action'] === 'remove_favorite') {
        $query = "DELETE FROM favorites WHERE user_id = ? AND provider_id = ?";
        executeQuery($conn, $query, 'ii', [$_SESSION['user_id'], $provider_id]);
        $isFavorite = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($provider['business_name']); ?> - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .provider-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .provider-cover {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            color: rgba(255,255,255,0.3);
        }
        .provider-info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .rating-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .rating-bar {
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .rating-bar-progress {
            flex: 1;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        .rating-bar-fill {
            height: 100%;
            background: #ffc107;
        }
        .service-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid #eee;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .service-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .service-price {
            font-size: 1.25rem;
            color: #28a745;
            font-weight: bold;
        }
        .service-duration {
            color: #666;
            font-size: 0.9rem;
        }
        .review-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }
        .review-author {
            font-weight: bold;
            color: #333;
        }
        .review-rating {
            color: #ffc107;
            font-weight: bold;
        }
        .review-date {
            color: #999;
            font-size: 0.85rem;
        }
        .review-text {
            color: #666;
            line-height: 1.6;
        }
        .btn-book {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .btn-book:hover {
            background: linear-gradient(135deg, #5568d3 0%, #65398a 100%);
            color: white;
        }
        .badge-verified {
            background: #28a745;
        }
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
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
                        <a class="nav-link" href="search.php">Search Services</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Provider Cover -->
    <div class="provider-cover">
        <i class="bi bi-briefcase-fill"></i>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Provider Info -->
                <div class="provider-info-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h1><?php echo htmlspecialchars($provider['business_name']); ?></h1>
                            <p class="text-muted mb-2">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($provider['category_name']); ?>
                            </p>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="<?php echo $isFavorite ? 'remove_favorite' : 'add_favorite'; ?>">
                            <button type="submit" class="btn btn-light">
                                <i class="bi bi-heart<?php echo $isFavorite ? '-fill' : ''; ?>" style="color: <?php echo $isFavorite ? 'red' : 'gray'; ?>"></i>
                                <?php echo $isFavorite ? 'Remove Favorite' : 'Add to Favorites'; ?>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo number_format($provider['avg_rating'], 1); ?></div>
                                <div class="stat-label">Rating</div>
                                <small><?php echo getStarRating($provider['avg_rating']); ?></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $provider['total_reviews']; ?></div>
                                <div class="stat-label">Reviews</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $provider['total_jobs_completed']; ?></div>
                                <div class="stat-label">Jobs Done</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $provider['years_of_experience']; ?></div>
                                <div class="stat-label">Years Exp.</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($provider['is_verified']): ?>
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle"></i> Verified Service Provider
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- About Section -->
                <div class="provider-info-card">
                    <h3>About</h3>
                    <p><?php echo htmlspecialchars($provider['business_description']); ?></p>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-geo-alt"></i> Location</strong></p>
                            <p><?php echo htmlspecialchars($provider['business_address']); ?></p>
                            <p><?php echo htmlspecialchars($provider['city']); ?>, <?php echo htmlspecialchars($provider['state']); ?> <?php echo htmlspecialchars($provider['business_zip']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-telephone"></i> Contact</strong></p>
                            <p><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($provider['business_phone']); ?></p>
                            <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($provider['business_email']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Services -->
                <div class="provider-info-card">
                    <h3>Services</h3>
                    <?php if (count($services) > 0): ?>
                        <?php foreach ($services as $service): ?>
                            <div class="service-card">
                                <div class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                <p class="mb-2"><?php echo htmlspecialchars($service['service_description']); ?></p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="service-price"><?php echo formatCurrency($service['price']); ?></div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="service-duration">
                                            <i class="bi bi-clock"></i> <?php echo formatDuration($service['duration_hours']); ?>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-book mt-2" onclick="bookService(<?php echo $service['service_id']; ?>, '<?php echo htmlspecialchars($service['service_name']); ?>')">
                                    <i class="bi bi-calendar-check"></i> Book Now
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No services available at the moment.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Reviews -->
                <div class="provider-info-card">
                    <h3>Reviews & Ratings</h3>
                    
                    <?php if ($provider['total_reviews'] > 0): ?>
                        <!-- Rating Breakdown -->
                        <div class="mb-4">
                            <h5>Rating Distribution</h5>
                            
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <?php 
                                $count = $ratingBreakdown['five_star'] ?? 0;
                                if ($i == 4) $count = $ratingBreakdown['four_star'] ?? 0;
                                if ($i == 3) $count = $ratingBreakdown['three_star'] ?? 0;
                                if ($i == 2) $count = $ratingBreakdown['two_star'] ?? 0;
                                if ($i == 1) $count = $ratingBreakdown['one_star'] ?? 0;
                                $percentage = ($ratingBreakdown['total'] > 0) ? ($count / $ratingBreakdown['total']) * 100 : 0;
                                ?>
                                <div class="rating-bar">
                                    <span><?php echo $i; ?>★</span>
                                    <div class="rating-bar-progress">
                                        <div class="rating-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <span><?php echo (int)$percentage; ?>%</span>
                                </div>
                            <?php endfor; ?>
                        </div>
                        
                        <!-- Reviews List -->
                        <h5>Recent Reviews</h5>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div>
                                        <div class="review-author"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></div>
                                        <div class="review-date"><?php echo formatDate($review['created_at']); ?></div>
                                    </div>
                                    <div class="review-rating"><?php echo getStarRating($review['rating']); ?></div>
                                </div>
                                <h6><?php echo htmlspecialchars($review['review_title']); ?></h6>
                                <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                
                                <?php if ($review['cleanliness_rating']): ?>
                                    <small class="text-muted">
                                        Cleanliness: <?php echo getStarRating($review['cleanliness_rating']); ?> | 
                                        Professionalism: <?php echo getStarRating($review['professionalism_rating']); ?> | 
                                        Punctuality: <?php echo getStarRating($review['punctuality_rating']); ?> | 
                                        Value: <?php echo getStarRating($review['value_for_money_rating']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No reviews yet. Be the first to review this provider!</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Info -->
                <div class="provider-info-card sticky-top" style="top: 20px;">
                    <h5>Quick Info</h5>
                    
                    <div class="mb-3">
                        <small class="text-muted">RESPONSE TIME</small>
                        <div>Usually responds within 2 hours</div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">AVAILABILITY</small>
                        <div>Mon - Sun, 9:00 AM - 6:00 PM</div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">SERVICE AREA</small>
                        <div><?php echo htmlspecialchars($provider['city']); ?>, <?php echo htmlspecialchars($provider['state']); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">MEMBER SINCE</small>
                        <div><?php echo formatDate($provider['created_at'], 'M Y'); ?></div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if (isLoggedIn()): ?>
                            <button class="btn btn-book" onclick="contactProvider()">
                                <i class="bi bi-chat-dots"></i> Contact Provider
                            </button>
                            <button class="btn btn-outline-primary" onclick="bookNow()">
                                <i class="bi bi-calendar-check"></i> Book Service
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-book">
                                <i class="bi bi-chat-dots"></i> Login to Contact
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You selected: <strong id="selectedService"></strong></p>
                    <p>Please proceed to the booking page to complete your reservation.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a id="bookingLink" href="#" class="btn btn-primary">Continue to Booking</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function bookService(serviceId, serviceName) {
            document.getElementById('selectedService').textContent = serviceName;
            document.getElementById('bookingLink').href = 'booking.php?service_id=' + serviceId;
            new bootstrap.Modal(document.getElementById('bookingModal')).show();
        }
        
        function bookNow() {
            <?php if (count($services) > 0): ?>
                bookService(<?php echo $services[0]['service_id']; ?>, '<?php echo htmlspecialchars($services[0]['service_name']); ?>');
            <?php else: ?>
                alert('No services available to book');
            <?php endif; ?>
        }
        
        function contactProvider() {
            alert('Feature coming soon! You will be able to contact the provider directly.');
        }
    </script>
</body>
</html>