<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

// Get statistics
$statsQuery = "SELECT 
              COUNT(DISTINCT p.provider_id) as providers,
              COUNT(DISTINCT s.service_id) as services,
              COUNT(DISTINCT b.booking_id) as bookings
              FROM service_providers p
              LEFT JOIN services s ON p.provider_id = s.provider_id
              LEFT JOIN bookings b ON p.provider_id = b.provider_id";
$siteStats = getSingleResult($conn, $statsQuery, '', []);

// Get featured service providers
$featuredQuery = "SELECT sp.*, u.city, u.state,
                 COUNT(r.review_id) as review_count,
                 AVG(r.rating) as avg_rating
                 FROM service_providers sp
                 JOIN users u ON sp.user_id = u.user_id
                 LEFT JOIN reviews r ON sp.provider_id = r.provider_id
                 WHERE sp.is_active = 1 AND sp.is_verified = 1
                 GROUP BY sp.provider_id
                 ORDER BY avg_rating DESC, review_count DESC
                 LIMIT 6";
$featured = getMultipleResults($conn, $featuredQuery, '', []);

// Get categories
$categories = getMultipleResults($conn, "SELECT * FROM service_categories ORDER BY category_name LIMIT 12");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Finder - Find Local Services Easily</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5rem 0;
            margin-bottom: 3rem;
        }
        .hero h1 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }
        .search-box {
            background: white;
            border-radius: 50px;
            padding: 0.5rem;
            display: flex;
            gap: 0.5rem;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        .search-box input {
            border: none;
            padding: 0.7rem 1.5rem;
            flex: 1;
            outline: none;
        }
        .search-box button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.7rem 2rem;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
        }
        .stats {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .stat {
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .stat-label {
            opacity: 0.9;
        }
        .category-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            color: inherit;
        }
        .category-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .category-name {
            font-weight: 600;
            color: #333;
        }
        .provider-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
        }
        .provider-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            color: inherit;
        }
        .provider-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }
        .provider-content {
            padding: 1.5rem;
        }
        .provider-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .provider-rating {
            color: #ffc107;
            margin-bottom: 0.5rem;
        }
        .provider-location {
            color: #666;
            font-size: 0.9rem;
        }
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .section-header h2 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #333;
        }
        .section-header p {
            font-size: 1.1rem;
            color: #666;
        }
        .feature-box {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .feature-title {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .feature-description {
            color: #666;
        }
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            border-radius: 10px;
            text-align: center;
            margin: 3rem 0;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.7rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #5568d3 0%, #65398a 100%);
            color: white;
            transform: translateY(-2px);
        }
        footer {
            background: #333;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
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
                        <a class="nav-link" href="#categories">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#featured">Featured</a>
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
                        <li class="nav-item">
                            <a class="nav-link" href="user_registeration.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            <h1><i class="bi bi-search"></i> Find Local Services</h1>
            <p>Discover trusted service providers in your area</p>
            
            <form action="search.php" method="GET" class="search-box">
                <input type="text" name="keyword" placeholder="Search services (plumber, electrician, cleaning...)" required>
                <button type="submit"><i class="bi bi-search"></i> Search</button>
            </form>
            
            <div class="stats">
                <div class="stat">
                    <div class="stat-number"><?php echo $siteStats['providers'] ?? 0; ?></div>
                    <div class="stat-label">Service Providers</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $siteStats['services'] ?? 0; ?></div>
                    <div class="stat-label">Services</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $siteStats['bookings'] ?? 0; ?></div>
                    <div class="stat-label">Bookings Completed</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Features -->
        <div class="section-header">
            <h2>Why Choose Service Finder?</h2>
            <p>Your one-stop platform to find and book trusted service providers</p>
        </div>
        
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                    <div class="feature-title">Verified Providers</div>
                    <div class="feature-description">All service providers are thoroughly vetted and verified</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon"><i class="bi bi-hand-thumbs-up"></i></div>
                    <div class="feature-title">Trusted Reviews</div>
                    <div class="feature-description">Read honest reviews from verified customers</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon"><i class="bi bi-lightning-charge"></i></div>
                    <div class="feature-title">Quick Booking</div>
                    <div class="feature-description">Easy and fast booking process with multiple payment options</div>
                </div>
            </div>
        </div>
        
        <!-- Categories -->
        <div class="section-header" id="categories">
            <h2>Browse by Category</h2>
            <p>Find services in various categories</p>
        </div>
        
        <div class="row mb-5">
            <?php foreach ($categories as $cat): ?>
                <div class="col-md-4 col-lg-3 mb-3">
                    <a href="search.php?category=<?php echo $cat['category_id']; ?>" class="category-card">
                        <div class="category-icon">
                            <i class="bi bi-tools"></i>
                        </div>
                        <div class="category-name"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Featured Providers -->
        <div class="section-header" id="featured">
            <h2>Featured Service Providers</h2>
            <p>Check out highly-rated providers in your area</p>
        </div>
        
        <div class="row mb-5">
            <?php foreach ($featured as $provider): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <a href="provider-profile.php?id=<?php echo $provider['provider_id']; ?>" class="provider-card">
                        <div class="provider-image">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div class="provider-content">
                            <div class="provider-name"><?php echo htmlspecialchars(truncateText($provider['business_name'], 25)); ?></div>
                            <div class="provider-rating">
                                <?php echo getStarRating($provider['avg_rating'] ?? 0); ?>
                                <strong><?php echo number_format($provider['avg_rating'] ?? 0, 1); ?></strong>
                            </div>
                            <div class="provider-location">
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($provider['city']); ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- CTA Section -->
        <div class="cta-section">
            <h2>Ready to Get Started?</h2>
            <p>Join thousands of customers finding trusted service providers</p>
            <?php if (!isLoggedIn()): ?>
                <div style="margin-top: 2rem;">
                    <a href="user_registeration.php?type=customer" class="btn btn-light" style="padding: 0.7rem 2rem; border-radius: 50px; font-weight: 600; margin-right: 1rem;">
                        <i class="bi bi-person-plus"></i> Register as Customer
                    </a>
                    <a href="user_registeration.php?type=provider" class="btn btn-outline-light" style="padding: 0.7rem 2rem; border-radius: 50px; font-weight: 600;">
                        <i class="bi bi-briefcase"></i> Become a Provider
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="bi bi-briefcase"></i> Service Finder</h5>
                    <p>Your trusted platform for finding local services</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="search.php" class="text-white-50" style="text-decoration: none;">Search Services</a></li>
                        <li><a href="#" class="text-white-50" style="text-decoration: none;">About Us</a></li>
                        <li><a href="#" class="text-white-50" style="text-decoration: none;">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Follow Us</h5>
                    <div>
                        <a href="#" class="text-white-50" style="text-decoration: none; margin-right: 1rem;"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white-50" style="text-decoration: none; margin-right: 1rem;"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white-50" style="text-decoration: none;"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr style="background-color: #555;">
            <p class="text-center text-white-50 mb-0">&copy; 2024 Service Finder. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>