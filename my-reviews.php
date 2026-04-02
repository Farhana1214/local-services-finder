<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();

// Get user's reviews
$query = "SELECT r.*, b.service_id, s.service_name, sp.business_name, sp.provider_id
         FROM reviews r
         JOIN bookings b ON r.booking_id = b.booking_id
         JOIN services s ON b.service_id = s.service_id
         JOIN service_providers sp ON r.provider_id = sp.provider_id
         WHERE r.user_id = ?
         ORDER BY r.created_at DESC";
$reviews = getMultipleResults($conn, $query, 'i', [$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .reviews-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .review-card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid #ffc107; }
        .review-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .review-rating { font-size: 1.25rem; color: #ffc107; font-weight: bold; }
        .review-date { color: #999; font-size: 0.9rem; }
        .empty-state { text-align: center; padding: 3rem; color: #666; }
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
    
    <div class="reviews-header">
        <div class="container">
            <h1><i class="bi bi-star"></i> My Reviews</h1>
            <p class="mb-0">View and manage your service reviews</p>
        </div>
    </div>
    
    <div class="container" style="max-width: 800px;">
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div>
                            <h5><?php echo htmlspecialchars($review['review_title']); ?></h5>
                            <small class="text-muted">
                                <strong><?php echo htmlspecialchars($review['service_name']); ?></strong> • 
                                <?php echo htmlspecialchars($review['business_name']); ?>
                            </small><br>
                            <small class="review-date"><i class="bi bi-calendar"></i> <?php echo formatDate($review['created_at']); ?></small>
                        </div>
                        <div class="review-rating">
                            <span><?php echo getStarRating($review['rating']); ?></span>
                            <div style="font-size: 0.9rem; color: #666;"><?php echo $review['rating']; ?}/5</div>
                        </div>
                    </div>
                    
                    <p><?php echo htmlspecialchars($review['review_text']); ?></p>
                    
                    <?php if ($review['cleanliness_rating'] || $review['professionalism_rating']): ?>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                            <small class="text-muted">
                                <?php if ($review['cleanliness_rating']): ?>
                                    Cleanliness: <?php echo getStarRating($review['cleanliness_rating']); ?> |
                                <?php endif; ?>
                                <?php if ($review['professionalism_rating']): ?>
                                    Professionalism: <?php echo getStarRating($review['professionalism_rating']); ?> |
                                <?php endif; ?>
                                <?php if ($review['punctuality_rating']): ?>
                                    Punctuality: <?php echo getStarRating($review['punctuality_rating']); ?> |
                                <?php endif; ?>
                                <?php if ($review['value_for_money_rating']): ?>
                                    Value: <?php echo getStarRating($review['value_for_money_rating']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 1rem;">
                        <a href="provider-profile.php?id=<?php echo $review['provider_id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> View Provider
                        </a>
                        <button class="btn btn-sm btn-outline-danger" onclick="alert('Delete feature coming soon')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-star" style="font-size: 3rem; color: #ccc;"></i>
                <p style="margin-top: 1rem;">No reviews yet</p>
                <small class="text-muted">You can leave reviews for completed bookings</small><br>
                <a href="my-bookings.php" class="btn btn-primary mt-2">View My Bookings</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>