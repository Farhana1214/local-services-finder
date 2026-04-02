<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();

// Get booking ID from URL
$booking_id = intval($_GET['booking_id'] ?? 0);

// Get booking details
$query = "SELECT b.*, s.service_name, s.price, sp.business_name, sp.business_phone, sp.business_email, sp.provider_id
          FROM bookings b
          JOIN services s ON b.service_id = s.service_id
          JOIN service_providers sp ON b.provider_id = sp.provider_id
          WHERE b.booking_id = ? AND b.user_id = ?";
$booking = getSingleResult($conn, $query, 'ii', [$booking_id, $_SESSION['user_id']]);

if (!$booking) {
    redirectWithMessage('dashboard.php', 'Booking not found', 'danger');
}

// Generate booking reference
$booking_ref = 'BK' . str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .confirmation-container {
            max-width: 700px;
            margin: 3rem auto;
        }
        .confirmation-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
        }
        .confirmation-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 3rem 2rem;
        }
        .confirmation-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .confirmation-body {
            padding: 2rem;
        }
        .booking-ref {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin: 1rem 0;
            font-family: monospace;
        }
        .detail-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #333;
        }
        .detail-value {
            color: #666;
        }
        .status-badge {
            display: inline-block;
            background: #ffc107;
            color: black;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            margin: 1rem 0;
        }
        .btn-group-custom {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        @media (max-width: 576px) {
            .btn-group-custom {
                flex-direction: column;
            }
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
    
    <div class="container confirmation-container">
        <div class="confirmation-card">
            <div class="confirmation-header">
                <div class="confirmation-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h2>Booking Confirmed!</h2>
                <p class="mb-0">Your service booking has been successfully created</p>
            </div>
            
            <div class="confirmation-body">
                <p>Thank you for booking with us! Your service provider will contact you soon to confirm the details.</p>
                
                <!-- Booking Reference -->
                <div class="booking-ref">
                    <?php echo $booking_ref; ?>
                </div>
                <small class="text-muted">Keep this reference number for your records</small>
                
                <!-- Booking Details -->
                <div class="detail-box">
                    <div class="detail-row">
                        <span class="detail-label">Service</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Service Provider</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['business_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Service Date</span>
                        <span class="detail-value"><?php echo formatDate($booking['service_date']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Service Time</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['service_time']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Location</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['location']); ?></span>
                    </div>
                </div>
                
                <!-- Price Details -->
                <div class="detail-box">
                    <div class="detail-row">
                        <span class="detail-label">Service Fee</span>
                        <span class="detail-value"><?php echo formatCurrency($booking['total_amount']); ?></span>
                    </div>
                    <?php if ($booking['discount_amount'] > 0): ?>
                        <div class="detail-row">
                            <span class="detail-label">Discount</span>
                            <span class="detail-value text-success">-<?php echo formatCurrency($booking['discount_amount']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row" style="border-bottom: 2px solid #667eea; font-weight: bold;">
                        <span class="detail-label">Total Amount</span>
                        <span class="detail-value"><?php echo formatCurrency($booking['final_amount']); ?></span>
                    </div>
                    <div class="detail-row mt-2">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value"><?php echo ucfirst($booking['payment_method']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Status</span>
                        <span class="detail-value"><?php echo getPaymentStatusBadge($booking['payment_status']); ?></span>
                    </div>
                </div>
                
                <!-- Status -->
                <div>
                    <span class="status-badge">
                        <i class="bi bi-clock"></i> <?php echo getStatusBadge($booking['status']); ?>
                    </span>
                </div>
                
                <!-- Provider Contact -->
                <div class="alert alert-info mt-3 mb-3">
                    <h6 class="alert-heading">Service Provider Contact Information</h6>
                    <p class="mb-1"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($booking['business_phone']); ?></p>
                    <p class="mb-0"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($booking['business_email']); ?></p>
                </div>
                
                <!-- Next Steps -->
                <div class="accordion mb-3" id="nextStepsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#nextSteps">
                                <i class="bi bi-list-check"></i> What happens next?
                            </button>
                        </h2>
                        <div id="nextSteps" class="accordion-collapse collapse show" data-bs-parent="#nextStepsAccordion">
                            <div class="accordion-body text-start">
                                <ol>
                                    <li>The service provider will review your booking</li>
                                    <li>They will contact you to confirm the details</li>
                                    <li>On the scheduled date, they will arrive at the specified location</li>
                                    <li>Payment can be made during or after the service</li>
                                    <li>After service completion, you can rate and review the provider</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="btn-group-custom">
                    <a href="dashboard.php" class="btn btn-primary btn-lg flex-grow-1">
                        <i class="bi bi-speedometer2"></i> Go to Dashboard
                    </a>
                    <a href="booking-details.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-outline-primary btn-lg flex-grow-1">
                        <i class="bi bi-details"></i> View Booking Details
                    </a>
                </div>
                
                <!-- Download Receipt -->
                <button class="btn btn-outline-secondary btn-sm mt-3" onclick="window.print()">
                    <i class="bi bi-download"></i> Download Receipt
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>