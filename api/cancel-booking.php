<?php
include '../database_connection.php';
include '../config.php';
include '../helpers.php';

requireLogin();

$booking_id = intval($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$reason = sanitize_input($_POST['reason'] ?? '');

if (!$booking_id) {
    jsonResponse(false, 'Invalid booking ID', null, 400);
}

// Get booking details
$booking = getSingleResult($conn, 
    "SELECT b.*, s.service_name, sp.business_name 
     FROM bookings b
     JOIN services s ON b.service_id = s.service_id
     JOIN service_providers sp ON b.provider_id = sp.provider_id
     WHERE b.booking_id = ? AND b.user_id = ?",
    'ii',
    [$booking_id, $_SESSION['user_id']]
);

if (!$booking) {
    jsonResponse(false, 'Booking not found', null, 404);
}

// Check if booking can be cancelled
if (in_array($booking['status'], ['completed', 'cancelled'])) {
    jsonResponse(false, 'This booking cannot be cancelled', null, 400);
}

// Check if cancellation is within time limit (24 hours before service)
$service_datetime = new DateTime($booking['service_date'] . ' ' . $booking['service_time']);
$now = new DateTime();
$hours_until_service = ($service_datetime->getTimestamp() - $now->getTimestamp()) / 3600;

if ($hours_until_service < 24) {
    jsonResponse(false, 'Cancellations must be made at least 24 hours before service time', null, 400);
}

// Update booking status
$updateQuery = "UPDATE bookings SET status = 'cancelled', cancellation_reason = ? WHERE booking_id = ?";
$result = executeQuery($conn, $updateQuery, 'si', [$reason, $booking_id]);

if ($result['success']) {
    // Log cancellation activity
    $logQuery = "INSERT INTO booking_activity_log (booking_id, activity_type, description, created_at) 
                 VALUES (?, 'cancelled', ?, NOW())
                 ON DUPLICATE KEY UPDATE created_at = NOW()";
    executeQuery($conn, $logQuery, 'is', [$booking_id, 'Booking cancelled by customer. Reason: ' . $reason]);
    
    jsonResponse(true, 'Booking cancelled successfully', [
        'booking_id' => $booking_id,
        'status' => 'cancelled',
        'message' => 'Your booking for ' . $booking['service_name'] . ' has been cancelled.'
    ]);
} else {
    jsonResponse(false, 'Failed to cancel booking', null, 500);
}
?>
