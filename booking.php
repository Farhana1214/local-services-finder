<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin(); // Ensure user is logged in

// Get service ID from URL
$service_id = intval($_GET['service_id'] ?? 0);

if ($service_id <= 0) {
    redirectWithMessage('search.php', 'Invalid service selected', 'danger');
}

// Get service details
$query = "SELECT s.*, sp.business_name, sp.provider_id, sp.business_phone, sp.business_address, sp.business_city, u.first_name, u.last_name
          FROM services s
          JOIN service_providers sp ON s.provider_id = sp.provider_id
          JOIN users u ON sp.user_id = u.user_id
          WHERE s.service_id = ?";
$service = getSingleResult($conn, $query, 'i', [$service_id]);

if (!$service) {
    redirectWithMessage('search.php', 'Service not found', 'danger');
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $service_date = sanitize_input($_POST['service_date'] ?? '');
    $service_time = sanitize_input($_POST['service_time'] ?? '');
    $location = sanitize_input($_POST['location'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');
    $promo_code = sanitize_input($_POST['promo_code'] ?? '');
    $payment_method = sanitize_input($_POST['payment_method'] ?? 'card');
    
    // Validation
    $errors = [];
    if (empty($service_date) || empty($service_time) || empty($location)) {
        $errors[] = 'Please fill all required fields';
    }
    
    if (strtotime($service_date) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Service date cannot be in the past';
    }
    
    if (count($errors) === 0) {
        // Calculate total amount
        $total_amount = $service['price'];
        $discount_amount = 0;
        
        // Check promo code
        if (!empty($promo_code)) {
            $promoQuery = "SELECT * FROM promo_codes WHERE code = ? AND is_active = 1 AND valid_from <= CURDATE() AND valid_until >= CURDATE() AND current_uses < max_uses";
            $promo = getSingleResult($conn, $promoQuery, 's', [$promo_code]);
            
            if ($promo) {
                $discount_amount = $promo['discount_type'] === 'percentage' 
                    ? ($total_amount * $promo['discount_value'] / 100)
                    : $promo['discount_value'];
            } else {
                $errors[] = 'Invalid or expired promo code';
            }
        }
        
        if (count($errors) === 0) {
            $final_amount = $total_amount - $discount_amount;
            $booking_datetime = date('Y-m-d H:i:s');
            
            // Insert booking
            $bookingQuery = "INSERT INTO bookings (user_id, service_id, provider_id, booking_date, service_date, service_time, location, service_notes, status, total_amount, discount_amount, final_amount, payment_method, payment_status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $result = executeQuery($conn, $bookingQuery, 'iiissssssdddss', [
                $_SESSION['user_id'],
                $service_id,
                $service['provider_id'],
                $booking_datetime,
                $service_date,
                $service_time,
                $location,
                $notes,
                BOOKING_STATUS_PENDING,
                $total_amount,
                $discount_amount,
                $final_amount,
                $payment_method,
                PAYMENT_STATUS_PENDING
            ]);
            
            if ($result['success']) {
                $booking_id = $conn->insert_id;
                
                // Update promo code usage
                if (!empty($promo_code) && isset($promo)) {
                    $updatePromoQuery = "UPDATE promo_codes SET current_uses = current_uses + 1 WHERE promo_id = ?";
                    executeQuery($conn, $updatePromoQuery, 'i', [$promo['promo_id']]);
                }
                
                // Create payment record
                $paymentQuery = "INSERT INTO payments (booking_id, user_id, provider_id, amount, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?)";
                executeQuery($conn, $paymentQuery, 'iiidss', [
                    $booking_id,
                    $_SESSION['user_id'],
                    $service['provider_id'],
                    $final_amount,
                    $payment_method,
                    PAYMENT_STATUS_PENDING
                ]);
                
                redirectWithMessage('booking-confirmation.php?booking_id=' . $booking_id, 'Booking created successfully!', 'success');
            } else {
                $errors[] = 'Error creating booking. Please try again.';
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
    <title>Book Service - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .booking-container {
            max-width: 900px;
            margin: 2rem auto;
        }
        .booking-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .booking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
        }
        .booking-body {
            padding: 2rem;
        }
        .service-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .summary-label {
            font-weight: 600;
        }
        .summary-value {
            text-align: right;
        }
        .summary-divider {
            border-top: 2px solid #ddd;
            margin: 1rem 0;
        }
        .summary-total {
            font-size: 1.25rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
        }
        .form-section {
            margin-bottom: 2rem;
        }
        .form-section h5 {
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
            color: #333;
            font-weight: bold;
        }
        .btn-book-service {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.7rem 2rem;
            font-weight: 600;
        }
        .btn-book-service:hover {
            background: linear-gradient(135deg, #5568d3 0%, #65398a 100%);
            color: white;
        }
        .promo-result {
            margin-top: 0.5rem;
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="search.php">Back to Search</a>
            </div>
        </div>
    </nav>
    
    <div class="container booking-container">
        <!-- Errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Booking Form -->
            <div class="col-lg-8">
                <div class="booking-card">
                    <div class="booking-header">
                        <h2><i class="bi bi-calendar-check"></i> Book Service</h2>
                        <p class="mb-0">Complete the form below to book this service</p>
                    </div>
                    
                    <div class="booking-body">
                        <form method="POST" action="">
                            <!-- Service Details Section -->
                            <div class="form-section">
                                <h5>Service Details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Service Provider</label>
                                            <p><?php echo htmlspecialchars($service['business_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Service</label>
                                            <p><?php echo htmlspecialchars($service['service_name']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted"><?php echo htmlspecialchars($service['service_description']); ?></p>
                            </div>
                            
                            <!-- Booking Details Section -->
                            <div class="form-section">
                                <h5>Booking Details</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="service_date" class="form-label fw-bold">Service Date *</label>
                                            <input type="date" class="form-control" id="service_date" name="service_date" 
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                            <small class="text-muted">Select the date you want the service</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="service_time" class="form-label fw-bold">Service Time *</label>
                                            <input type="time" class="form-control" id="service_time" name="service_time" required>
                                            <small class="text-muted">Preferred time of service</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="location" class="form-label fw-bold">Service Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="Enter the address where service is needed" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label fw-bold">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Any special requirements or instructions for the service provider"></textarea>
                                </div>
                            </div>
                            
                            <!-- Payment Section -->
                            <div class="form-section">
                                <h5>Payment Method</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="payment_card" 
                                                   name="payment_method" value="card" checked>
                                            <label class="form-check-label" for="payment_card">
                                                <i class="bi bi-credit-card"></i> Credit/Debit Card
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="payment_upi" 
                                                   name="payment_method" value="upi">
                                            <label class="form-check-label" for="payment_upi">
                                                <i class="bi bi-phone"></i> UPI
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="payment_wallet" 
                                                   name="payment_method" value="wallet">
                                            <label class="form-check-label" for="payment_wallet">
                                                <i class="bi bi-wallet2"></i> Wallet
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="payment_cash" 
                                                   name="payment_method" value="cash">
                                            <label class="form-check-label" for="payment_cash">
                                                <i class="bi bi-cash-coin"></i> Cash
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Promo Code -->
                            <div class="form-section">
                                <h5>Promo Code</h5>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="promo_code" class="form-label fw-bold">Have a Promo Code?</label>
                                            <input type="text" class="form-control" id="promo_code" name="promo_code" 
                                                   placeholder="Enter promo code (optional)" onchange="validatePromo()">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">&nbsp;</label>
                                            <button type="button" class="btn btn-outline-primary w-100" onclick="validatePromo()">
                                                Apply
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="promo_result"></div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="provider-profile.php?id=<?php echo $service['provider_id']; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Back
                                </a>
                                <button type="submit" name="submit_booking" class="btn btn-book-service flex-grow-1">
                                    <i class="bi bi-check-circle"></i> Confirm Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Summary Sidebar -->
            <div class="col-lg-4">
                <div class="booking-card sticky-top" style="top: 20px;">
                    <div class="booking-header">
                        <h5 class="mb-0">Booking Summary</h5>
                    </div>
                    
                    <div class="booking-body">
                        <div class="service-summary">
                            <div class="summary-item">
                                <span class="summary-label">Service:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($service['service_name']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Provider:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($service['business_name']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Duration:</span>
                                <span class="summary-value"><?php echo formatDuration($service['duration_hours']); ?></span>
                            </div>
                            
                            <div class="summary-divider"></div>
                            
                            <div class="summary-item">
                                <span class="summary-label">Service Fee:</span>
                                <span class="summary-value" id="service_fee"><?php echo formatCurrency($service['price']); ?></span>
                            </div>
                            
                            <div class="summary-item">
                                <span class="summary-label">Discount:</span>
                                <span class="summary-value" id="discount" style="color: #28a745;">-<?php echo formatCurrency(0); ?></span>
                            </div>
                            
                            <div class="summary-divider"></div>
                            
                            <div class="summary-total">
                                <span>Total:</span>
                                <span id="total_amount"><?php echo formatCurrency($service['price']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Provider Info -->
                        <div class="alert alert-info mb-0">
                            <h6 class="mb-2"><i class="bi bi-info-circle"></i> Provider Information</h6>
                            <p class="mb-1"><i class="bi bi-person"></i> <?php echo htmlspecialchars($service['first_name'] . ' ' . $service['last_name']); ?></p>
                            <p class="mb-1"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($service['business_phone']); ?></p>
                            <p class="mb-0"><i class="bi bi-map"></i> <?php echo htmlspecialchars($service['business_city']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validatePromo() {
            const promoCode = document.getElementById('promo_code').value.trim();
            if (!promoCode) {
                document.getElementById('promo_result').innerHTML = '';
                updateTotal(0);
                return;
            }
            
            // Simulate promo validation (in real app, this would call server)
            const validPromos = {
                'SAVE10': 10,
                'SAVE20': 20,
                'FIRST50': 50
            };
            
            const discountPercentage = validPromos[promoCode] || 0;
            if (discountPercentage > 0) {
                const basePrice = <?php echo $service['price']; ?>;
                const discountAmount = (basePrice * discountPercentage) / 100;
                document.getElementById('promo_result').innerHTML = '<div class="promo-result text-success"><i class="bi bi-check-circle"></i> Promo code applied!</div>';
                updateTotal(discountAmount);
            } else {
                document.getElementById('promo_result').innerHTML = '<div class="promo-result text-danger"><i class="bi bi-x-circle"></i> Invalid promo code</div>';
                updateTotal(0);
            }
        }
        
        function updateTotal(discount) {
            const basePrice = <?php echo $service['price']; ?>;
            const total = basePrice - discount;
            document.getElementById('discount').textContent = '-<?php echo CURRENCY; ?>' + discount.toFixed(2);
            document.getElementById('total_amount').textContent = '<?php echo CURRENCY; ?>' + total.toFixed(2);
        }
    </script>
</body>
</html>