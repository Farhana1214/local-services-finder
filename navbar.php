<?php
// Navigation bar - reusable component
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isLoggedIn();
?>

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
                    <a class="nav-link <?php echo $current_page === 'search.php' ? 'active' : ''; ?>" href="search.php">
                        <i class="bi bi-search"></i> Search Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'smart-search.php' ? 'active' : ''; ?>" href="smart-search.php">
                        <i class="bi bi-robot"></i> Smart Search
                    </a>
                </li>
                
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php 
                            $user_name = $_SESSION['first_name'] ?? 'User';
                            echo htmlspecialchars(strlen($user_name) > 12 ? substr($user_name, 0, 12) . '...' : $user_name);
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-gear"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-calendar-check"></i> My Bookings</a></li>
                            <li><a class="dropdown-item" href="my-reviews.php"><i class="bi bi-star"></i> My Reviews</a></li>
                            <li><a class="dropdown-item" href="chatbot.php"><i class="bi bi-robot"></i> Support Chat</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_registeration.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
