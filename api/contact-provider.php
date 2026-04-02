<?php
include '../database_connection.php';
include '../config.php';
include '../helpers.php';

requireLogin();

$provider_id = intval($_POST['provider_id'] ?? 0);
$booking_id = intval($_POST['booking_id'] ?? 0);
$message = sanitize_input($_POST['message'] ?? '');

if (!$provider_id || !$message) {
    jsonResponse(false, 'Provider ID and message are required', null, 400);
}

if (strlen($message) < 10 || strlen($message) > 2000) {
    jsonResponse(false, 'Message must be between 10 and 2000 characters', null, 400);
}

// Get provider info
$provider = getSingleResult($conn, 
    "SELECT sp.provider_id, u.user_id, u.first_name, u.last_name, u.email 
     FROM service_providers sp
     JOIN users u ON sp.user_id = u.user_id
     WHERE sp.provider_id = ?",
    'i',
    [$provider_id]
);

if (!$provider) {
    jsonResponse(false, 'Provider not found', null, 404);
}

// Get customer info
$customer = getSingleResult($conn, 
    "SELECT user_id, email, first_name FROM users WHERE user_id = ?",
    'i',
    [$_SESSION['user_id']]
);

// Create provider message/inquiry in database
$table = 'provider_inquiries';
$insertQuery = "INSERT INTO $table (provider_id, customer_id, booking_id, subject, message, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())";

$subject = !empty($booking_id) ? 'Inquiry about your booking' : 'Service inquiry';

$result = executeQuery($conn, $insertQuery, 'iiiss', [
    $provider_id,
    $_SESSION['user_id'],
    !empty($booking_id) ? $booking_id : null,
    $subject,
    $message
]);

if ($result['success']) {
    // Optional: Send email notification to provider
    // mail($provider['email'], "New message from " . $customer['first_name'], 
    //      "You have a new message from a customer.\n\n" . $message);
    
    jsonResponse(true, 'Message sent successfully', [
        'inquiry_id' => mysqli_insert_id($conn),
        'provider_name' => $provider['first_name'] . ' ' . $provider['last_name'],
        'message' => 'Your message has been sent to the provider. They will get back to you soon.'
    ]);
} else {
    jsonResponse(false, 'Failed to send message', null, 500);
}
?>
