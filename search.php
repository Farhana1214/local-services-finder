<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

// Get user's default location if logged in
$user_city = USER_DEFAULT_CITY;
if (isLoggedIn()) {
    $user_data = getSingleResult($conn, "SELECT city FROM users WHERE user_id = ?", 'i', [$_SESSION['user_id']]);
    $user_city = $user_data['city'] ?? USER_DEFAULT_CITY;
}

// Get search parameters
$keyword = sanitize_input($_GET['keyword'] ?? '');
$category = sanitize_input($_GET['category'] ?? '');
$city = sanitize_input($_GET['city'] ?? '');
$minRating = floatval($_GET['min_rating'] ?? 0);
$sortBy = sanitize_input($_GET['sort_by'] ?? 'rating');
$page = intval($_GET['page'] ?? 1);

// Validate sort option
$validSortOptions = ['rating', 'price', 'newest', 'reviews'];
if (!in_array($sortBy, $validSortOptions)) {
    $sortBy = 'rating';
}

// Calculate offset for pagination
$offset = ($page - 1) * SEARCH_RESULTS_PER_PAGE;

// Build dynamic query
$query = "SELECT sp.*, 
          u.email, u.phone, u.city, u.state,
          c.category_name,
          COALESCE(AVG(r.rating), 0) as avg_rating,
          COUNT(r.review_id) as total_reviews
          FROM service_providers sp
          JOIN users u ON sp.user_id = u.user_id
          JOIN service_categories c ON sp.category_id = c.category_id
          LEFT JOIN reviews r ON sp.provider_id = r.provider_id
          WHERE sp.is_active = 1 AND sp.is_verified = 1";

$types = '';
$params = [];

// Add conditions
if (!empty($keyword)) {
    $query .= " AND (sp.business_name LIKE ? OR sp.business_description LIKE ? OR c.category_name LIKE ?)";
    $keyword_param = '%' . $keyword . '%';
    $params = array_merge($params, [$keyword_param, $keyword_param, $keyword_param]);
    $types .= 'sss';
}

if (!empty($category)) {
    $query .= " AND sp.category_id = ?";
    $params[] = intval($category);
    $types .= 'i';
}

if (!empty($city)) {
    $query .= " AND u.city LIKE ?";
    $params[] = '%' . $city . '%';
    $types .= 's';
}

// Group by provider
$query .= " GROUP BY sp.provider_id";

// Add rating filter after GROUP BY
if ($minRating > 0) {
    $query .= " HAVING avg_rating >= ?";
    $params[] = $minRating;
    $types .= 'd';
}

// Add sorting
switch ($sortBy) {
    case 'price':
        // Average service price
        $query .= " ORDER BY (SELECT AVG(price) FROM services WHERE provider_id = sp.provider_id) ASC";
        break;
    case 'newest':
        $query .= " ORDER BY sp.created_at DESC";
        break;
    case 'reviews':
        $query .= " ORDER BY total_reviews DESC";
        break;
    case 'rating':
    default:
        $query .= " ORDER BY avg_rating DESC, total_reviews DESC";
        break;
}

// Get total count for pagination - simplified approach
$countQuery = "
    SELECT COUNT(DISTINCT sp.provider_id) as total
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    JOIN service_categories c ON sp.category_id = c.category_id
    LEFT JOIN reviews r ON sp.provider_id = r.provider_id
    WHERE sp.is_active = 1 AND sp.is_verified = 1
";

// Build separate arrays for count query (same conditions as main query)
$countTypes = '';
$countParams = [];

// Add keyword search
if (!empty($keyword)) {
    $countQuery .= " AND (sp.business_name LIKE ? OR sp.business_description LIKE ? OR c.category_name LIKE ?)";
    $keyword_param = '%' . $keyword . '%';
    $countParams = array_merge($countParams, [$keyword_param, $keyword_param, $keyword_param]);
    $countTypes .= 'sss';
}

// Add category filter
if (!empty($category)) {
    $countQuery .= " AND c.category_id = ?";
    $countParams[] = intval($category);
    $countTypes .= 'i';
}

// Add city filter
if (!empty($city)) {
    $countQuery .= " AND u.city LIKE ?";
    $countParams[] = '%' . $city . '%';
    $countTypes .= 's';
}

// Group by provider
$countQuery .= " GROUP BY sp.provider_id";

// Add rating filter after GROUP BY
if ($minRating > 0) {
    $countQuery .= " HAVING AVG(r.rating) >= ?";
    $countParams[] = $minRating;
    $countTypes .= 'd';
}

// Wrap in subquery to count results
$countQuery = "SELECT COUNT(*) as total FROM (" . $countQuery . ") as filtered";

$countResult = getSingleResult($conn, $countQuery, $countTypes, $countParams);
$totalResults = $countResult['total'] ?? 0;
$totalPages = ceil($totalResults / SEARCH_RESULTS_PER_PAGE);

// Add LIMIT and OFFSET
$query .= " LIMIT ? OFFSET ?";
$params[] = SEARCH_RESULTS_PER_PAGE;
$params[] = $offset;
$types .= 'ii';

// Get results
$providers = getMultipleResults($conn, $query, $types, $params);

// Get categories for filter
$categories = getMultipleResults($conn, "SELECT category_id, category_name FROM service_categories ORDER BY category_name");

// Get unique cities from database
$cities = getMultipleResults($conn, "SELECT DISTINCT city FROM users WHERE city IS NOT NULL AND city != '' ORDER BY city LIMIT 100");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Services - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <?php echo getLocationScript(); ?>
    <style>
        body {
            background: #f8f9fa;
        }
        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .filter-group {
            margin-bottom: 1rem;
        }
        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .provider-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
            height: 100%;
        }
        .provider-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .provider-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        .provider-content {
            padding: 1.5rem;
        }
        .provider-name {
            font-size: 1.25rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .provider-category {
            color: #667eea;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .provider-rating {
            color: #ffc107;
            margin-bottom: 0.5rem;
        }
        .provider-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .provider-info {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .provider-price {
            font-size: 1rem;
            font-weight: bold;
            color: #28a745;
            margin: 1rem 0;
        }
        .btn-view-profile {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-view-profile:hover {
            background: linear-gradient(135deg, #5568d3 0%, #65398a 100%);
            color: white;
        }
        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            color: #666;
        }
        .pagination {
            margin: 2rem 0;
            justify-content: center;
        }
        .sort-dropdown {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Autocomplete Dropdown Styles */
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
        
        .autocomplete-dropdown.active {
            display: block;
        }
        
        .autocomplete-item {
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .autocomplete-item:hover {
            background: #f8f9fa;
        }
        
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        
        .autocomplete-name {
            font-weight: 600;
            color: #333;
        }
        
        .autocomplete-category {
            font-size: 0.85rem;
            color: #667eea;
            margin-top: 2px;
        }
        
        .autocomplete-type {
            display: inline-block;
            font-size: 0.7rem;
            background: #667eea;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 5px;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user_registeration.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Search Header -->
    <div class="search-header">
        <div class="container">
            <h1><i class="bi bi-search"></i> Find Services</h1>
            <p class="lead">Search for service providers in your area</p>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="search-filters sticky-top" style="top: 20px;">
                    <h5 class="mb-3">Filter Results</h5>
                    
                    <form method="GET" action="">
                        <!-- Keyword Search -->
                        <div class="filter-group">
                            <label for="keyword">Search Keyword</label>
                            <div style="position: relative;">
                                <input type="text" class="form-control" id="keyword" name="keyword" 
                                       value="<?php echo htmlspecialchars($keyword); ?>" 
                                       placeholder="Service name, provider, etc."
                                       autocomplete="off">
                                <div id="autocompleteDropdown" class="autocomplete-dropdown"></div>
                            </div>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="filter-group">
                            <label for="category">Service Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" 
                                            <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- City Filter -->
                        <div class="filter-group">
                            <label for="city">City</label>
                            <div style="display: flex; gap: 8px;">
                                <select class="form-select" id="city" name="city" style="flex: 1;">
                                    <option value="">All Cities</option>
                                    <option value="near me" <?php echo $city == 'near me' ? 'selected' : ''; ?>>📍 Near Me</option>
                                    <?php foreach ($cities as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['city']); ?>" 
                                                <?php echo $city == $c['city'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="getAndSetUserLocation()" title="Auto-detect location">
                                    <i class="bi bi-geo-alt"></i>
                                </button>
                            </div>
                            <input type="hidden" id="user_location" value="<?php echo htmlspecialchars($user_city); ?>">
                        </div>
                        
                        <!-- Minimum Rating -->
                        <div class="filter-group">
                            <label for="min_rating">Minimum Rating</label>
                            <select class="form-select" id="min_rating" name="min_rating">
                                <option value="0" <?php echo $minRating == 0 ? 'selected' : ''; ?>>Any Rating</option>
                                <option value="3" <?php echo $minRating == 3 ? 'selected' : ''; ?>>3+ Stars</option>
                                <option value="3.5" <?php echo $minRating == 3.5 ? 'selected' : ''; ?>>3.5+ Stars</option>
                                <option value="4" <?php echo $minRating == 4 ? 'selected' : ''; ?>>4+ Stars</option>
                                <option value="4.5" <?php echo $minRating == 4.5 ? 'selected' : ''; ?>>4.5+ Stars</option>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="filter-group">
                            <label for="sort_by">Sort By</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="rating" <?php echo $sortBy == 'rating' ? 'selected' : ''; ?>>Highest Rating</option>
                                <option value="reviews" <?php echo $sortBy == 'reviews' ? 'selected' : ''; ?>>Most Reviewed</option>
                                <option value="newest" <?php echo $sortBy == 'newest' ? 'selected' : ''; ?>>Newest</option>
                                <option value="price" <?php echo $sortBy == 'price' ? 'selected' : ''; ?>>Price: Low to High</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="search.php" class="btn btn-outline-secondary w-100">Reset Filters</a>
                    </form>
                </div>
            </div>
            
            <!-- Results -->
            <div class="col-lg-9">
                <!-- Results Info -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>
                        <?php echo $totalResults; ?> Service Provider<?php echo $totalResults != 1 ? 's' : ''; ?> Found
                    </h5>
                </div>
                
                <!-- Provider Cards -->
                <?php if (count($providers) > 0): ?>
                    <div class="row">
                        <?php foreach ($providers as $provider): ?>
                            <div class="col-md-6">
                                <div class="provider-card">
                                    <div class="provider-image">
                                        <i class="bi bi-briefcase-fill"></i>
                                    </div>
                                    <div class="provider-content">
                                        <div class="provider-name"><?php echo htmlspecialchars($provider['business_name']); ?></div>
                                        <div class="provider-category">
                                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($provider['category_name']); ?>
                                        </div>
                                        
                                        <div class="provider-rating">
                                            <span><?php echo getStarRating($provider['avg_rating']); ?></span>
                                            <strong><?php echo number_format($provider['avg_rating'], 1); ?></strong>
                                            <small>(<?php echo $provider['total_reviews']; ?> reviews)</small>
                                        </div>
                                        
                                        <div class="provider-description">
                                            <?php echo truncateText($provider['business_description'], 80); ?>
                                        </div>
                                        
                                        <div class="provider-info">
                                            <i class="bi bi-geo-alt"></i> 
                                            <?php echo htmlspecialchars($provider['city']); ?>, <?php echo htmlspecialchars($provider['state']); ?>
                                        </div>
                                        
                                        <div class="provider-info">
                                            <i class="bi bi-briefcase-fill"></i> 
                                            <?php echo $provider['total_jobs_completed']; ?> jobs completed
                                        </div>
                                        
                                        <div class="provider-price">
                                            ★ <?php echo $provider['years_of_experience']; ?> years experience
                                        </div>
                                        
                                        <a href="provider-profile.php?id=<?php echo $provider['provider_id']; ?>" 
                                           class="btn btn-view-profile w-100">
                                            <i class="bi bi-eye"></i> View Profile & Services
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?keyword=<?php echo urlencode($keyword); ?>&category=<?php echo $category; ?>&city=<?php echo urlencode($city); ?>&min_rating=<?php echo $minRating; ?>&sort_by=<?php echo $sortBy; ?>&page=1">First</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?keyword=<?php echo urlencode($keyword); ?>&category=<?php echo $category; ?>&city=<?php echo urlencode($city); ?>&min_rating=<?php echo $minRating; ?>&sort_by=<?php echo $sortBy; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php 
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                for ($p = $start; $p <= $end; $p++): 
                                ?>
                                    <li class="page-item <?php echo $p == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?keyword=<?php echo urlencode($keyword); ?>&category=<?php echo $category; ?>&city=<?php echo urlencode($city); ?>&min_rating=<?php echo $minRating; ?>&sort_by=<?php echo $sortBy; ?>&page=<?php echo $p; ?>">
                                            <?php echo $p; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?keyword=<?php echo urlencode($keyword); ?>&category=<?php echo $category; ?>&city=<?php echo urlencode($city); ?>&min_rating=<?php echo $minRating; ?>&sort_by=<?php echo $sortBy; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?keyword=<?php echo urlencode($keyword); ?>&category=<?php echo $category; ?>&city=<?php echo urlencode($city); ?>&min_rating=<?php echo $minRating; ?>&sort_by=<?php echo $sortBy; ?>&page=<?php echo $totalPages; ?>">Last</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-results">
                        <i class="bi bi-search" style="font-size: 3rem; color: #ccc;"></i>
                        <h4 class="mt-3">No Service Providers Found</h4>
                        <p>Try adjusting your search filters or explore other categories.</p>
                        <a href="search.php" class="btn btn-primary mt-2">Reset Search</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const keywordInput = document.getElementById('keyword');
        const autocompleteDropdown = document.getElementById('autocompleteDropdown');
        let debounceTimer;
        
        // Listen for input changes
        keywordInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length < 1) {
                autocompleteDropdown.classList.remove('active');
                autocompleteDropdown.innerHTML = '';
                return;
            }
            
            // Debounce the API call (wait 300ms after user stops typing)
            debounceTimer = setTimeout(() => {
                fetchAutocomplete(query);
            }, 300);
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target !== keywordInput && e.target !== autocompleteDropdown) {
                autocompleteDropdown.classList.remove('active');
            }
        });
        
        async function fetchAutocomplete(query) {
            try {
                const response = await fetch('api/search-autocomplete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ query: query })
                });
                
                const data = await response.json();
                
                if (data.suggestions && data.suggestions.length > 0) {
                    renderAutocomplete(data.suggestions);
                } else {
                    autocompleteDropdown.classList.remove('active');
                    autocompleteDropdown.innerHTML = '';
                }
            } catch (error) {
                console.error('Autocomplete error:', error);
                autocompleteDropdown.classList.remove('active');
            }
        }
        
        function renderAutocomplete(suggestions) {
            autocompleteDropdown.innerHTML = '';
            
            suggestions.forEach(suggestion => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.innerHTML = `
                    <div class="autocomplete-name">
                        ${suggestion.name}
                        <span class="autocomplete-type">${suggestion.type === 'provider' ? 'Business' : 'Service'}</span>
                    </div>
                    <div class="autocomplete-category">
                        ${suggestion.category}
                    </div>
                `;
                
                item.addEventListener('click', function() {
                    keywordInput.value = suggestion.name;
                    autocompleteDropdown.classList.remove('active');
                    // Optional: auto-submit the form
                    // document.querySelector('form').submit();
                });
                
                autocompleteDropdown.appendChild(item);
            });
            
            autocompleteDropdown.classList.add('active');
        }
    </script>
</body>
</html>