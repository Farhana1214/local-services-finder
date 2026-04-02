<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle new ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $title = sanitize_input($_POST['title'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $priority = sanitize_input($_POST['priority'] ?? 'medium');
    $booking_id = intval($_POST['booking_id'] ?? 0);
    
    // Validation
    $errors = [];
    if (empty($title)) {
        $errors[] = 'Ticket title is required';
    } elseif (strlen($title) < 5) {
        $errors[] = 'Title must be at least 5 characters';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    } elseif (strlen($description) < 10) {
        $errors[] = 'Description must be at least 10 characters';
    }
    
    if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
        $errors[] = 'Invalid priority level';
    }
    
    if ($booking_id > 0) {
        // Verify booking belongs to user
        $bookingCheck = getSingleResult($conn, "SELECT booking_id FROM bookings WHERE booking_id = ? AND user_id = ?", 'ii', [$booking_id, $user_id]);
        if (!$bookingCheck) {
            $errors[] = 'Invalid booking reference';
        }
    }
    
    if (count($errors) === 0) {
        $insertQuery = "INSERT INTO support_tickets (user_id, booking_id, title, description, priority, status) 
                       VALUES (?, ?, ?, ?, ?, 'open')";
        
        $result = executeQuery($conn, $insertQuery, 'iisss', [
            $user_id,
            ($booking_id > 0 ? $booking_id : null),
            $title,
            $description,
            $priority
        ]);
        
        if ($result['success']) {
            $ticket_id = $conn->insert_id;
            $success_message = "✅ Support ticket created successfully! Ticket ID: <strong>#$ticket_id</strong>";
        } else {
            $error_message = 'Failed to create ticket. Please try again.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Get user's bookings for reference
$userBookings = getMultipleResults($conn, 
    "SELECT b.booking_id, s.service_name, sp.business_name, b.booking_date, b.status
     FROM bookings b
     JOIN services s ON b.service_id = s.service_id
     JOIN service_providers sp ON b.provider_id = sp.provider_id
     WHERE b.user_id = ?
     ORDER BY b.booking_date DESC
     LIMIT 10", 
    'i', [$user_id]
);

// Get user's support tickets
$tickets = getMultipleResults($conn, 
    "SELECT st.*, 
            COALESCE((SELECT COUNT(*) FROM support_ticket_messages WHERE ticket_id = st.ticket_id), 0) as message_count
     FROM support_tickets st
     WHERE st.user_id = ?
     ORDER BY 
        CASE st.priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        st.created_at DESC", 
    'i', [$user_id]
);

// Get stats
$stats = getSingleResult($conn,
    "SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets
     FROM support_tickets
     WHERE user_id = ?",
    'i', [$user_id]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .ticket-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .ticket-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .ticket-card.urgent {
            border-left-color: #dc3545;
        }
        .ticket-card.high {
            border-left-color: #fd7e14;
        }
        .ticket-card.medium {
            border-left-color: #ffc107;
        }
        .ticket-card.low {
            border-left-color: #28a745;
        }
        .ticket-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        .ticket-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .ticket-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.9rem;
        }
        .ticket-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
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
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-box {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .stat-box .number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        .stat-box .label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .form-section h3 {
            margin-bottom: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        .message-count {
            background: #f0f0f0;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="header">
        <div class="container">
            <h1><i class="bi bi-headset"></i> Contact Support</h1>
            <p class="mb-0">We're here to help! Submit your questions and issues</p>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="number"><?php echo $stats['total_tickets'] ?? 0; ?></div>
                <div class="label">Total Tickets</div>
            </div>
            <div class="stat-box">
                <div class="number" style="color: #0066cc;"><?php echo $stats['open_tickets'] ?? 0; ?></div>
                <div class="label">Open</div>
            </div>
            <div class="stat-box">
                <div class="number" style="color: #ff9800;"><?php echo $stats['in_progress_tickets'] ?? 0; ?></div>
                <div class="label">In Progress</div>
            </div>
            <div class="stat-box">
                <div class="number" style="color: #4caf50;"><?php echo $stats['resolved_tickets'] ?? 0; ?></div>
                <div class="label">Resolved</div>
            </div>
        </div>

        <!-- New Ticket Form -->
        <div class="form-section">
            <h3>Create New Support Ticket</h3>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">
                            <i class="bi bi-pencil"></i> Ticket Title *
                        </label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="e.g., Service quality issue" required>
                        <small class="form-text text-muted">Be descriptive and concise</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="booking_id" class="form-label">
                            <i class="bi bi-calendar-check"></i> Related Booking (Optional)
                        </label>
                        <select class="form-select" id="booking_id" name="booking_id">
                            <option value="">-- Select a booking --</option>
                            <?php foreach ($userBookings as $booking): ?>
                                <option value="<?php echo $booking['booking_id']; ?>">
                                    #<?php echo $booking['booking_id']; ?> - 
                                    <?php echo $booking['service_name']; ?> 
                                    (<?php echo htmlspecialchars($booking['business_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="priority" class="form-label">
                            <i class="bi bi-exclamation-circle"></i> Priority *
                        </label>
                        <select class="form-select" id="priority" name="priority" required>
                            <option value="low">Low - Can wait</option>
                            <option value="medium" selected>Medium - Moderate issue</option>
                            <option value="high">High - Urgent attention needed</option>
                            <option value="urgent">Urgent - Critical issue</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">
                        <i class="bi bi-chat-left-text"></i> Issue Description *
                    </label>
                    <textarea class="form-control" id="description" name="description" 
                              placeholder="Please provide detailed information about your issue..." 
                              required></textarea>
                    <small class="form-text text-muted">Provide as much detail as possible to help us resolve quickly</small>
                </div>

                <button type="submit" name="submit_ticket" class="btn btn-primary">
                    <i class="bi bi-send"></i> Submit Ticket
                </button>
            </form>
        </div>

        <!-- Existing Tickets -->
        <div>
            <h3 class="mb-3">
                <i class="bi bi-list-check"></i> Your Support Tickets
            </h3>

            <?php if (empty($tickets)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> You haven't created any support tickets yet.
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card <?php echo $ticket['priority']; ?>">
                        <div class="ticket-header">
                            <div class="ticket-title">
                                <span>#<?php echo $ticket['ticket_id']; ?></span> - 
                                <?php echo htmlspecialchars($ticket['title']); ?>
                            </div>
                            <div class="ticket-meta">
                                <span>
                                    <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                </span>
                                <span>
                                    <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                                        <?php echo ucfirst($ticket['priority']); ?> Priority
                                    </span>
                                </span>
                                <span>
                                    <i class="bi bi-calendar"></i> 
                                    <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                </span>
                                <span class="message-count">
                                    <i class="bi bi-chat"></i> <?php echo $ticket['message_count']; ?> msg
                                </span>
                            </div>
                        </div>
                        <div style="padding: 1rem 1.5rem; background: #f9f9f9;">
                            <p class="mb-2" style="color: #555;">
                                <?php echo htmlspecialchars(substr($ticket['description'], 0, 150)); ?>
                                <?php if (strlen($ticket['description']) > 150): ?>...<?php endif; ?>
                            </p>
                            <a href="ticket-details.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <hr class="my-4">
        <div class="alert alert-light">
            <h6><i class="bi bi-info-circle"></i> Need Immediate Help?</h6>
            <p class="mb-0">
                <strong>Email:</strong> <a href="mailto:support@servicefinder.com">support@servicefinder.com</a><br>
                <strong>Phone:</strong> <a href="tel:+92300000000">+92 (300) 000-0000</a><br>
                <strong>Available:</strong> Monday - Friday, 9:00 AM - 6:00 PM (PKT)
            </p>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2026 Service Finder. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
