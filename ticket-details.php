<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$ticket_id = intval($_GET['ticket_id'] ?? 0);

if ($ticket_id <= 0) {
    redirectWithMessage('contact-support.php', 'Invalid ticket ID', 'danger');
}

// Get ticket
$ticket = getSingleResult($conn, 
    "SELECT * FROM support_tickets WHERE ticket_id = ? AND user_id = ?", 
    'ii', [$ticket_id, $user_id]
);

if (!$ticket) {
    redirectWithMessage('contact-support.php', 'Ticket not found', 'danger');
}

// Handle message submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    // Only allow messages if ticket is not closed
    if ($ticket['status'] === 'closed') {
        $error_message = 'Cannot reply to a closed ticket. Please create a new ticket if needed.';
    } else {
        $message_text = sanitize_input($_POST['message'] ?? '');
        
        if (empty($message_text)) {
            $error_message = 'Message cannot be empty';
        } elseif (strlen($message_text) < 2) {
            $error_message = 'Message is too short';
        } else {
            $insertQuery = "INSERT INTO support_ticket_messages (ticket_id, user_id, message, is_admin) 
                           VALUES (?, ?, ?, 0)";
            
            $result = executeQuery($conn, $insertQuery, 'iis', [$ticket_id, $user_id, $message_text]);
            
            if ($result['success']) {
                $success_message = '✅ Message sent successfully!';
                // Refresh ticket data
                $ticket = getSingleResult($conn, 
                    "SELECT * FROM support_tickets WHERE ticket_id = ? AND user_id = ?", 
                    'ii', [$ticket_id, $user_id]
                );
            } else {
                $error_message = 'Failed to send message. Please try again.';
            }
        }
    }
}

// Get ticket messages
$messages = getMultipleResults($conn, 
    "SELECT stm.*, u.first_name, u.last_name, u.username
     FROM support_ticket_messages stm
     JOIN users u ON stm.user_id = u.user_id
     WHERE stm.ticket_id = ?
     ORDER BY stm.created_at ASC", 
    'i', [$ticket_id]
);

// Get related booking if exists
$booking = null;
if ($ticket['booking_id']) {
    $booking = getSingleResult($conn, 
        "SELECT b.*, s.service_name, sp.business_name, sp.provider_id
         FROM bookings b
         JOIN services s ON b.service_id = s.service_id
         JOIN service_providers sp ON b.provider_id = sp.provider_id
         WHERE b.booking_id = ?", 
        'i', [$ticket['booking_id']]
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Ticket - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .ticket-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .ticket-info {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }
        .status-open {
            background: #e7f3ff;
            color: #0066cc;
        }
        .status-in_progress {
            background: #fff3e0;
            color: #e65100;
        }
        .status-resolved {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-closed {
            background: #f3e5f5;
            color: #6a1b9a;
        }
        .priority-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }
        .priority-urgent {
            background: #ffebee;
            color: #c62828;
        }
        .priority-high {
            background: #ffe0b2;
            color: #e65100;
        }
        .priority-medium {
            background: #fff9c4;
            color: #f57f17;
        }
        .priority-low {
            background: #e8f5e9;
            color: #558b2f;
        }
        .messages-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .messages-list {
            height: 400px;
            overflow-y: auto;
            padding: 2rem 1.5rem;
        }
        .message {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
        }
        .message.user-message {
            flex-direction: row-reverse;
        }
        .message-avatar {
            width: 40px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #667eea;
            flex-shrink: 0;
        }
        .message.user-message .message-avatar {
            background: #e7f3ff;
        }
        .message.admin-message .message-avatar {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .message-content {
            flex: 1;
        }
        .message-header {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            color: #333;
        }
        .message.admin-message .message-header {
            color: #2e7d32;
        }
        .message-body {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 8px;
            word-wrap: break-word;
            color: #555;
        }
        .message.user-message .message-body {
            background: #e7f3ff;
            color: #0055aa;
        }
        .message.admin-message .message-body {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .message-time {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.3rem;
        }
        .booking-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }
        .booking-card h6 {
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .reply-form {
            padding: 1.5rem;
            background: #f9f9f9;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="ticket-header">
        <div class="container">
            <a href="contact-support.php" class="text-white" style="text-decoration: none;">
                <i class="bi bi-arrow-left"></i> Back to Support
            </a>
            <h1 class="mt-2">Support Ticket #<?php echo $ticket['ticket_id']; ?></h1>
        </div>
    </div>

    <div class="container">
        <!-- Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Ticket Information -->
        <div class="ticket-info">
            <h4 class="mb-3"><?php echo htmlspecialchars($ticket['title']); ?></h4>
            
            <div class="info-row">
                <div class="info-label">Status</div>
                <div>
                    <span class="status-badge status-<?php echo $ticket['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Priority</div>
                <div>
                    <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                        <?php echo ucfirst($ticket['priority']); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Created</div>
                <div><?php echo date('M d, Y at h:i A', strtotime($ticket['created_at'])); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Last Updated</div>
                <div><?php echo date('M d, Y at h:i A', strtotime($ticket['updated_at'])); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Issue Description</div>
                <div><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></div>
            </div>

            <?php if ($booking): ?>
                <div class="booking-card">
                    <h6><i class="bi bi-calendar-check"></i> Related Booking</h6>
                    <p class="mb-2">
                        <strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?><br>
                        <strong>Provider:</strong> <?php echo htmlspecialchars($booking['business_name']); ?><br>
                        <strong>Date:</strong> <?php echo date('M d, Y', strtotime($booking['service_date'])); ?><br>
                        <strong>Status:</strong> <span class="badge bg-primary"><?php echo ucfirst($booking['status']); ?></span>
                    </p>
                    <a href="booking-details.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                       class="btn btn-sm btn-outline-primary">
                        View Booking Details
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <div class="messages-container">
            <div class="messages-list" id="messagesContainer">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-chat-left" style="font-size: 2rem; opacity: 0.5;"></i>
                        <p class="mt-2">No messages yet. Start by describing your issue below.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo ($msg['user_id'] == $user_id) ? 'user-message' : 'admin-message'; ?>">
                            <div class="message-avatar">
                                <?php echo strtoupper(substr($msg['first_name'], 0, 1)); ?>
                            </div>
                            <div class="message-content">
                                <div class="message-header">
                                    <?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?>
                                    <?php if ($msg['is_admin']): ?>
                                        <span class="badge bg-success ms-2">Support Team</span>
                                    <?php endif; ?>
                                </div>
                                <div class="message-body">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo date('M d, h:i A', strtotime($msg['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($ticket['status'] !== 'closed'): ?>
                <div class="reply-form">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-600">Send a Reply</label>
                            <textarea class="form-control" name="message" 
                                    placeholder="Type your message here..." required></textarea>
                            <small class="form-text text-muted">
                                Our support team typically responds within 24 hours
                            </small>
                        </div>
                        <button type="submit" name="send_message" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send Message
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info m-3">
                    <i class="bi bi-info-circle"></i> This ticket is closed. 
                    <a href="contact-support.php">Create a new ticket</a> if you need further assistance.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2026 Service Finder. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom
        document.getElementById('messagesContainer').scrollTop = 
            document.getElementById('messagesContainer').scrollHeight;
    </script>
</body>
</html>
