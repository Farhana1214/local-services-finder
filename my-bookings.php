<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();

// Get all user bookings with filters
$status = sanitize_input($_GET['status'] ?? '');
$bookings = getUserBookings($conn, $_SESSION['user_id'], !empty($status) ? $status : null);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .bookings-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .booking-item { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid #667eea; display: flex; justify-content: space-between; align-items: center; }
        .booking-info h5 { margin-bottom: 0.5rem; font-weight: bold; }
        .booking-info small { color: #666; }
        .empty-state { text-align: center; padding: 3rem; color: #666; }
        .filter-pills { margin-bottom: 2rem; }
        .filter-pills .btn { border-radius: 50px; }
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
    
    <div class="bookings-header">
        <div class="container">
            <h1><i class="bi bi-calendar-check"></i> My Bookings</h1>
            <p class="mb-0">View and manage your service bookings</p>
        </div>
    </div>
    
    <div class="container" style="max-width: 900px;">
        <!-- Filters -->
        <div class="filter-pills">
            <a href="my-bookings.php" class="btn btn-sm <?php echo empty($status) ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
            <a href="my-bookings.php?status=pending" class="btn btn-sm <?php echo $status === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
            <a href="my-bookings.php?status=confirmed" class="btn btn-sm <?php echo $status === 'confirmed' ? 'btn-info' : 'btn-outline-info'; ?>">Confirmed</a>
            <a href="my-bookings.php?status=completed" class="btn btn-sm <?php echo $status === 'completed' ? 'btn-success' : 'btn-outline-success'; ?>">Completed</a>
            <a href="my-bookings.php?status=cancelled" class="btn btn-sm <?php echo $status === 'cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?>">Cancelled</a>
        </div>
        
        <?php if (count($bookings) > 0): ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-item">
                    <div class="booking-info">
                        <h5><?php echo htmlspecialchars($booking['service_name']); ?></h5>
                        <small><?php echo htmlspecialchars($booking['business_name']); ?></small><br>
                        <small><i class="bi bi-calendar"></i> <?php echo formatDate($booking['booking_date']); ?></small><br>
                        <small><i class="bi bi-clock"></i> <?php echo htmlspecialchars($booking['service_time']); ?></small>
                    </div>
                    <div style="text-align: right;">
                        <?php echo getStatusBadge($booking['status']); ?>
                        <br>
                        <strong><?php echo formatCurrency($booking['final_amount']); ?></strong>
                        <br>
                        <div style="margin-top: 0.5rem; display: flex; gap: 5px; justify-content: flex-end;">
                            <a href="booking-details.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-info-circle"></i> Details
                            </a>
                            
                            <?php if (!in_array($booking['status'], ['cancelled', 'completed'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $booking['booking_id']; ?>">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#contactModal<?php echo $booking['booking_id']; ?>">
                                <i class="bi bi-chat-dots"></i> Contact
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Cancel Booking Modal -->
                <?php if (!in_array($booking['status'], ['cancelled', 'completed'])): ?>
                <div class="modal fade" id="cancelModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Cancel Booking</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?></p>
                                <p><strong>Date:</strong> <?php echo formatDate($booking['booking_date']); ?></p>
                                <form id="cancelForm<?php echo $booking['booking_id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Cancellation Reason</label>
                                        <textarea class="form-control" name="reason" rows="4" required 
                                                  placeholder="Please let us know why you're cancelling..."></textarea>
                                    </div>
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                                <button type="button" class="btn btn-danger" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                                    <i class="bi bi-x-circle"></i> Confirm Cancellation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Contact Provider Modal -->
                <div class="modal fade" id="contactModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Contact Provider</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Provider:</strong> <?php echo htmlspecialchars($booking['business_name']); ?></p>
                                <p><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?></p>
                                <form id="contactForm<?php echo $booking['booking_id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Your Message</label>
                                        <textarea class="form-control" name="message" rows="5" required 
                                                  placeholder="Type your message here..." minlength="10" maxlength="2000"></textarea>
                                        <small class="text-muted">10-2000 characters</small>
                                    </div>
                                    <input type="hidden" name="provider_id" value="<?php echo $booking['provider_id']; ?>">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="contactProvider(<?php echo $booking['booking_id']; ?>)">
                                    <i class="bi bi-send"></i> Send Message
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p style="margin-top: 1rem;">No bookings found</p>
                <a href="search.php" class="btn btn-primary">Book a Service</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelBooking(bookingId) {
            const form = document.getElementById('cancelForm' + bookingId);
            const formData = new FormData(form);
            
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
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cancelling the booking');
            });
        }
        
        function contactProvider(bookingId) {
            const form = document.getElementById('contactForm' + bookingId);
            const formData = new FormData(form);
            
            // Disable button while sending
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('contactModal' + bookingId));
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
    </script>
</body>
</html>