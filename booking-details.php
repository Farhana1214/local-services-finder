<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();

$booking_id = intval($_GET['booking_id'] ?? 0);

// Get booking details
$query = "SELECT b.*, s.service_name, s.price, sp.business_name, sp.business_phone, sp.business_email, sp.provider_id, sp.profile_image,
          u.first_name, u.last_name
          FROM bookings b
          JOIN services s ON b.service_id = s.service_id
          JOIN service_providers sp ON b.provider_id = sp.provider_id
          JOIN users u ON sp.user_id = u.user_id
          WHERE b.booking_id = ? AND b.user_id = ?";
$booking = getSingleResult($conn, $query, 'ii', [$booking_id, $_SESSION['user_id']]);

if (!$booking) {
    redirectWithMessage('dashboard.php', 'Booking not found', 'danger');
}

// Get reviews for this booking
$reviewQuery = "SELECT * FROM reviews WHERE booking_id = ?";
$review = getSingleResult($conn, $reviewQuery, 'i', [$booking_id]);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if ($booking['status'] !== BOOKING_STATUS_COMPLETED) {
        $error = 'You can only review completed bookings';
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $review_title = sanitize_input($_POST['review_title'] ?? '');
        $review_text = sanitize_input($_POST['review_text'] ?? '');
        $cleanliness = intval($_POST['cleanliness'] ?? 0);
        $professionalism = intval($_POST['professionalism'] ?? 0);
        $punctuality = intval($_POST['punctuality'] ?? 0);
        $value = intval($_POST['value'] ?? 0);
        
        if ($rating < 1 || $rating > 5) {
            $error = 'Invalid rating';
        } else {
            $insertQuery = "INSERT INTO reviews (booking_id, user_id, provider_id, rating, review_title, review_text, cleanliness_rating, professionalism_rating, punctuality_rating, value_for_money_rating) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $result = executeQuery($conn, $insertQuery, 'iiiisssiii', [
                $booking_id, $_SESSION['user_id'], $booking['provider_id'],
                $rating, $review_title, $review_text, $cleanliness, $professionalism, $punctuality, $value
            ]);
            
            if ($result['success']) {
                $review = getSingleResult($conn, $reviewQuery, 'i', [$booking_id]);
                $success = 'Thank you for your review!';
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
    <title>Booking Details - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .details-container {
            max-width: 900px;
            margin: 2rem auto;
        }
        .details-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
        }
        .card-body-custom {
            padding: 2rem;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }
        .detail-item {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .detail-value {
            color: #333;
            font-size: 1.1rem;
            margin-top: 0.3rem;
        }
        .timeline {
            position: relative;
            padding: 1rem 0;
        }
        .timeline-item {
            padding: 1rem;
            border-left: 3px solid #667eea;
            margin-left: 1rem;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 1rem;
            width: 13px;
            height: 13px;
            background: white;
            border: 3px solid #667eea;
            border-radius: 50%;
        }
        .timeline-item-title {
            font-weight: 600;
            color: #333;
        }
        .timeline-item-time {
            font-size: 0.9rem;
            color: #999;
        }
        .provider-box {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            background: #f8f9fa;
        }
        .provider-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
    </style>
</head>
<body style="background: #f8f9fa;">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-briefcase"></i> Service Finder
            </a>
        </div>
    </nav>
    
    <div class="container details-container">
        <!-- Back Button -->
        <a href="dashboard.php" class="btn btn-outline-primary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
        
        <!-- Success Message -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Main Details -->
            <div class="col-lg-8">
                <!-- Booking Info -->
                <div class="details-card">
                    <div class="card-header-custom">
                        <h3 class="mb-0">
                            <i class="bi bi-calendar-check"></i> Booking Details
                        </h3>
                    </div>
                    <div class="card-body-custom">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Booking Reference</div>
                                <div class="detail-value font-monospace">BK<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Booking Date</div>
                                <div class="detail-value"><?php echo formatDateTime($booking['booking_date']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Service Date</div>
                                <div class="detail-value"><?php echo formatDate($booking['service_date']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Service Time</div>
                                <div class="detail-value"><?php echo htmlspecialchars($booking['service_time']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Booking Status</div>
                                <div class="detail-value"><?php echo getStatusBadge($booking['status']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Payment Status</div>
                                <div class="detail-value"><?php echo getPaymentStatusBadge($booking['payment_status']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Service Details -->
                <div class="details-card">
                    <div class="card-header-custom">
                        <h3 class="mb-0">
                            <i class="bi bi-wrench"></i> Service Details
                        </h3>
                    </div>
                    <div class="card-body-custom">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Service Name</div>
                                <div class="detail-value"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Service Location</div>
                                <div class="detail-value"><?php echo htmlspecialchars($booking['location']); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($booking['service_notes']): ?>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                                <div class="detail-label">Special Notes</div>
                                <div class="detail-value"><?php echo htmlspecialchars($booking['service_notes']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pricing Details -->
                <div class="details-card">
                    <div class="card-header-custom">
                        <h3 class="mb-0">
                            <i class="bi bi-receipt"></i> Pricing & Payment
                        </h3>
                    </div>
                    <div class="card-body-custom">
                        <div style="display: flex; justify-content: space-between; margin: 0.8rem 0; padding: 0.8rem 0; border-bottom: 1px solid #eee;">
                            <span>Service Fee:</span>
                            <strong><?php echo formatCurrency($booking['total_amount']); ?></strong>
                        </div>
                        <?php if ($booking['discount_amount'] > 0): ?>
                            <div style="display: flex; justify-content: space-between; margin: 0.8rem 0; padding: 0.8rem 0; border-bottom: 1px solid #eee; color: #28a745;">
                                <span>Discount:</span>
                                <strong>-<?php echo formatCurrency($booking['discount_amount']); ?></strong>
                            </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between; margin: 1rem 0; padding: 1rem 0; border-top: 2px solid #667eea; font-size: 1.2rem; font-weight: bold;">
                            <span>Total Amount:</span>
                            <span style="color: #667eea;"><?php echo formatCurrency($booking['final_amount']); ?></span>
                        </div>
                        <div style="margin-top: 1rem;">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo ucfirst($booking['payment_method']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Review Section -->
                <?php if ($booking['status'] === BOOKING_STATUS_COMPLETED && !$review): ?>
                    <div class="details-card">
                        <div class="card-header-custom">
                            <h3 class="mb-0">
                                <i class="bi bi-star"></i> Leave a Review
                            </h3>
                        </div>
                        <div class="card-body-custom">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="rating" class="form-label fw-bold">Rating *</label>
                                    <div class="rating-input">
                                        <input type="radio" id="star5" name="rating" value="5" required>
                                        <label for="star5"><i class="bi bi-star-fill"></i></label>
                                        <input type="radio" id="star4" name="rating" value="4">
                                        <label for="star4"><i class="bi bi-star-fill"></i></label>
                                        <input type="radio" id="star3" name="rating" value="3">
                                        <label for="star3"><i class="bi bi-star-fill"></i></label>
                                        <input type="radio" id="star2" name="rating" value="2">
                                        <label for="star2"><i class="bi bi-star-fill"></i></label>
                                        <input type="radio" id="star1" name="rating" value="1">
                                        <label for="star1"><i class="bi bi-star-fill"></i></label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="review_title" class="form-label fw-bold">Review Title *</label>
                                    <input type="text" class="form-control" id="review_title" name="review_title" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="review_text" class="form-label fw-bold">Your Review *</label>
                                    <textarea class="form-control" id="review_text" name="review_text" rows="4" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cleanliness" class="form-label fw-bold">Cleanliness</label>
                                            <select class="form-select" id="cleanliness" name="cleanliness">
                                                <option value="0">N/A</option>
                                                <option value="1">1 - Poor</option>
                                                <option value="2">2 - Fair</option>
                                                <option value="3">3 - Good</option>
                                                <option value="4">4 - Very Good</option>
                                                <option value="5">5 - Excellent</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="professionalism" class="form-label fw-bold">Professionalism</label>
                                            <select class="form-select" id="professionalism" name="professionalism">
                                                <option value="0">N/A</option>
                                                <option value="1">1 - Poor</option>
                                                <option value="2">2 - Fair</option>
                                                <option value="3">3 - Good</option>
                                                <option value="4">4 - Very Good</option>
                                                <option value="5">5 - Excellent</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="punctuality" class="form-label fw-bold">Punctuality</label>
                                            <select class="form-select" id="punctuality" name="punctuality">
                                                <option value="0">N/A</option>
                                                <option value="1">1 - Poor</option>
                                                <option value="2">2 - Fair</option>
                                                <option value="3">3 - Good</option>
                                                <option value="4">4 - Very Good</option>
                                                <option value="5">5 - Excellent</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="value" class="form-label fw-bold">Value for Money</label>
                                            <select class="form-select" id="value" name="value">
                                                <option value="0">N/A</option>
                                                <option value="1">1 - Poor</option>
                                                <option value="2">2 - Fair</option>
                                                <option value="3">3 - Good</option>
                                                <option value="4">4 - Very Good</option>
                                                <option value="5">5 - Excellent</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="submit_review" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Submit Review
                                    </button>
                                    <a href="booking-details.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif ($review): ?>
                    <div class="details-card">
                        <div class="card-header-custom">
                            <h3 class="mb-0">
                                <i class="bi bi-star"></i> Your Review
                            </h3>
                        </div>
                        <div class="card-body-custom">
                            <p style="margin-bottom: 0.5rem;">
                                <strong><?php echo htmlspecialchars($review['review_title']); ?></strong>
                                <br>
                                <span style="color: #ffc107; font-size: 1.2rem;">
                                    <?php echo getStarRating($review['rating']); ?>
                                </span>
                            </p>
                            <p><?php echo htmlspecialchars($review['review_text']); ?></p>
                            <small class="text-muted">Reviewed on <?php echo formatDate($review['created_at']); ?></small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Provider Info -->
                <div class="details-card">
                    <div class="card-header-custom">
                        <h5 class="mb-0">Service Provider</h5>
                    </div>
                    <div class="card-body-custom">
                        <div style="text-align: center; margin-bottom: 1.5rem;">
                            <div class="provider-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <h5><?php echo htmlspecialchars($booking['business_name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value">
                                <a href="tel:<?php echo htmlspecialchars($booking['business_phone']); ?>">
                                    <?php echo htmlspecialchars($booking['business_phone']); ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">
                                <a href="mailto:<?php echo htmlspecialchars($booking['business_email']); ?>">
                                    <?php echo htmlspecialchars($booking['business_email']); ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="provider-profile.php?id=<?php echo $booking['provider_id']; ?>" class="btn btn-outline-primary w-100">
                                <i class="bi bi-eye"></i> View Profile
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="details-card">
                    <div class="card-header-custom">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#contactModal">
                                <i class="bi bi-chat-dots"></i> Contact Provider
                            </button>
                            <?php if (!in_array($booking['status'], ['cancelled', 'completed'])): ?>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="bi bi-x-circle"></i> Cancel Booking
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                <i class="bi bi-download"></i> Download Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Booking Modal -->
    <?php if (!in_array($booking['status'], ['cancelled', 'completed'])): ?>
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Important:</strong> You can only cancel at least 24 hours before the service date.
                    </div>
                    <p><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?></p>
                    <p><strong>Date:</strong> <?php echo formatDate($booking['service_date']); ?> at <?php echo htmlspecialchars($booking['service_time']); ?></p>
                    <form id="cancelForm">
                        <div class="mb-3">
                            <label class="form-label">Cancellation Reason</label>
                            <textarea class="form-control" id="cancelReason" name="reason" rows="4" required 
                                      placeholder="Please let us know why you're cancelling..."></textarea>
                        </div>
                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                    <button type="button" class="btn btn-danger" onclick="confirmCancelBooking()">
                        <i class="bi bi-x-circle"></i> Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Contact Provider Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact Provider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Provider:</strong> <?php echo htmlspecialchars($booking['business_name']); ?></p>
                    <p><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?></p>
                    <form id="contactForm">
                        <div class="mb-3">
                            <label class="form-label">Your Message</label>
                            <textarea class="form-control" id="contactMessage" name="message" rows="5" required 
                                      placeholder="Type your message here..." minlength="10" maxlength="2000"></textarea>
                            <small class="text-muted">10-2000 characters</small>
                        </div>
                        <input type="hidden" name="provider_id" value="<?php echo $booking['provider_id']; ?>">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmContactProvider()">
                        <i class="bi bi-send"></i> Send Message
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmCancelBooking() {
            const form = document.getElementById('cancelForm');
            const formData = new FormData(form);
            
            // Show loading state
            event.target.disabled = true;
            event.target.innerHTML = '<i class="bi bi-hourglass-split"></i> Cancelling...';
            
            fetch('api/cancel-booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message || 'Booking cancelled successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to cancel booking'));
                    event.target.disabled = false;
                    event.target.innerHTML = '<i class="bi bi-x-circle"></i> Confirm Cancellation';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cancelling the booking');
                event.target.disabled = false;
                event.target.innerHTML = '<i class="bi bi-x-circle"></i> Confirm Cancellation';
            });
        }
        
        function confirmContactProvider() {
            const form = document.getElementById('contactForm');
            const formData = new FormData(form);
            
            // Show loading state
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
            
            fetch('api/contact-provider.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send"></i> Send Message';
                
                if (data.success) {
                    alert('Message sent successfully! The provider will get back to you soon.');
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('contactModal'));
                    if (modal) modal.hide();
                    form.reset();
                } else {
                    alert('Error: ' + (data.message || 'Failed to send message'));
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send"></i> Send Message';
                console.error('Error:', error);
                alert('An error occurred while sending the message');
            });
        }
        
        // Star rating interaction
        const stars = document.querySelectorAll('.rating-input input[type="radio"]');
        stars.forEach(star => {
            star.addEventListener('change', function() {
                const value = this.value;
                stars.forEach(s => {
                    s.parentElement.style.color = s.value <= value ? '#ffc107' : '#ccc';
                });
            });
        });
    </script>
</body>
</html>