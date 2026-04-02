<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

// Get search parameters
$keyword = sanitize_input($_GET['keyword'] ?? '');
$category = sanitize_input($_GET['category'] ?? '');
$city = sanitize_input($_GET['city'] ?? '');
$minRating = floatval($_GET['min_rating'] ?? 0);
$maxPrice = intval($_GET['max_price'] ?? 0);
$sortBy = sanitize_input($_GET['sort_by'] ?? 'relevance'); // relevance, price, rating, reviews
$page = intval($_GET['page'] ?? 1);

// Get user's city if logged in, otherwise use default
$user_city = USER_DEFAULT_CITY;
if (isLoggedIn()) {
    $user_data = getSingleResult($conn, "SELECT city FROM users WHERE user_id = ?", 'i', [$_SESSION['user_id']]);
    $user_city = $user_data['city'] ?? USER_DEFAULT_CITY;
}

// If no city specified in search, auto-use user's city
if (empty($city)) {
    $city = $user_city;
}

// Calculate offset for pagination
$offset = ($page - 1) * SEARCH_RESULTS_PER_PAGE;

// If user searches with relative location, use their city
if ($city === 'near me' && !empty($user_city)) {
    $city = $user_city;
}

// Build intelligent query with RAG (Retrieval-Augmented Generation)
$query = "
    SELECT 
        sp.provider_id,
        sp.business_name,
        sp.business_description,
        sp.years_of_experience,
        u.email,
        u.phone,
        u.city,
        u.state,
        c.category_id,
        c.category_name,
        ROUND(COALESCE(AVG(r.rating), 0), 2) as avg_rating,
        COUNT(DISTINCT r.review_id) as total_reviews,
        MIN(s.price) as min_price,
        MAX(s.price) as max_price,
        ROUND(AVG(s.price), 2) as avg_price,
        COUNT(DISTINCT s.service_id) as service_count
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    JOIN service_categories c ON sp.category_id = c.category_id
    LEFT JOIN services s ON sp.provider_id = s.provider_id
    LEFT JOIN reviews r ON sp.provider_id = r.provider_id
    WHERE sp.is_active = 1 AND sp.is_verified = 1
";

// Build dynamic WHERE conditions
$types = '';
$params = [];

// Add keyword search
if (!empty($keyword)) {
    $query .= " AND (sp.business_name LIKE ? OR c.category_name LIKE ? OR s.service_name LIKE ?)";
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
    $types .= 'sss';
}

// Add category filter
if (!empty($category)) {
    $query .= " AND c.category_id = ?";
    $params[] = intval($category);
    $types .= 'i';
}

// Add city filter
if (!empty($city)) {
    $query .= " AND u.city LIKE ?";
    $params[] = '%' . $city . '%';
    $types .= 's';
}

// Group by provider
$query .= " GROUP BY sp.provider_id";

// Add rating filter after GROUP BY (HAVING clause)
if ($minRating > 0) {
    $query .= " HAVING AVG(r.rating) >= ?";
    $params[] = $minRating;
    $types .= 'd';
}

// Add price filter after GROUP BY (HAVING clause)
if ($maxPrice > 0) {
    $query .= ($minRating > 0 ? " AND " : " HAVING ") . "MIN(s.price) <= ?";
    $params[] = $maxPrice;
    $types .= 'i';
}

// Add sorting (relevance will be calculated in PHP)
switch ($sortBy) {
    case 'price':
        $query .= " ORDER BY min_price ASC";
        break;
    case 'rating':
        $query .= " ORDER BY avg_rating DESC, total_reviews DESC";
        break;
    case 'reviews':
        $query .= " ORDER BY total_reviews DESC";
        break;
    case 'experience':
        $query .= " ORDER BY years_of_experience DESC";
        break;
    case 'relevance':
    default:
        $query .= " ORDER BY avg_rating DESC, total_reviews DESC";
        break;
}

// Get total count for pagination - build count query based on main query structure
// We'll count results after applying all filters
$countQuery = "
    SELECT COUNT(DISTINCT sp.provider_id) as total
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    JOIN service_categories c ON sp.category_id = c.category_id
    LEFT JOIN services s ON sp.provider_id = s.provider_id
    LEFT JOIN reviews r ON sp.provider_id = r.provider_id
    WHERE sp.is_active = 1 AND sp.is_verified = 1
";

// Build separate arrays for count query (same conditions as main query)
$countTypes = '';
$countParams = [];

// Add keyword search
if (!empty($keyword)) {
    $countQuery .= " AND (sp.business_name LIKE ? OR c.category_name LIKE ? OR s.service_name LIKE ?)";
    $countParams[] = '%' . $keyword . '%';
    $countParams[] = '%' . $keyword . '%';
    $countParams[] = '%' . $keyword . '%';
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

// For count, we need GROUP BY to apply HAVING conditions
$countQuery .= " GROUP BY sp.provider_id";

// Add rating filter after GROUP BY
if ($minRating > 0) {
    $countQuery .= " HAVING AVG(r.rating) >= ?";
    $countParams[] = $minRating;
    $countTypes .= 'd';
}

// Add price filter after GROUP BY
if ($maxPrice > 0) {
    $countQuery .= ($minRating > 0 ? " AND " : " HAVING ") . "MIN(s.price) <= ?";
    $countParams[] = $maxPrice;
    $countTypes .= 'i';
}

// Execute count query wrapped in a subquery
$countQuery = "SELECT COUNT(*) as total FROM (" . $countQuery . ") as count_providers";

$countResult = executeQuery($conn, $countQuery, $countTypes, $countParams);
if ($countResult['success']) {
    try {
        $result = $countResult['stmt']->get_result();
        $countData = $result->fetch_assoc();
        $totalResults = $countData['total'] ?? 0;
    } catch (Exception $e) {
        // If count query fails, we'll count from results instead
        $totalResults = 0;
    }
} else {
    // Fallback: we'll count actual results below
    $totalResults = 0;
}

$totalPages = ceil($totalResults > 0 ? $totalResults : 1 / SEARCH_RESULTS_PER_PAGE);

// Add LIMIT and OFFSET
$query .= " LIMIT ? OFFSET ?";
$params[] = SEARCH_RESULTS_PER_PAGE;
$params[] = $offset;
$types .= 'ii';

// Get results
$results = getMultipleResults($conn, $query, $types, $params);
$all_providers = is_array($results) ? $results : [];

// If count query failed, use actual result count
if ($totalResults === 0 && !empty($all_providers)) {
    $totalResults = count($all_providers);
    $totalPages = ceil($totalResults / SEARCH_RESULTS_PER_PAGE);
}

$providers = $all_providers;

// Calculate relevance scores in PHP
if (!empty($keyword) && $sortBy === 'relevance') {
    foreach ($providers as &$provider) {
        $relevance_score = 0;
        
        // Keyword matching (0-30 points)
        $keyword_lower = strtolower($keyword);
        $business_match = stripos($provider['business_name'], $keyword) !== false ? 30 : 
                         (stripos($provider['category_name'], $keyword) !== false ? 25 : 0);
        $relevance_score += $business_match;
        
        // Rating quality (0-25 points)
        $rating = floatval($provider['avg_rating'] ?? 0);
        $relevance_score += ($rating / 5) * 25;
        
        // Review count (0-20 points)
        $review_count = intval($provider['total_reviews'] ?? 0);
        $relevance_score += min(($review_count / 50) * 20, 20);
        
        // Experience years (0-15 points)
        $experience = intval($provider['years_of_experience'] ?? 0);
        $relevance_score += min(($experience / 15) * 15, 15);
        
        // Price affordability (0-10 points)
        $min_price = floatval($provider['min_price'] ?? 999999);
        if ($maxPrice > 0 && $min_price <= $maxPrice) {
            $relevance_score += 10;
        } elseif ($min_price <= 5000) {
            $relevance_score += 8;
        } elseif ($min_price <= 10000) {
            $relevance_score += 5;
        }
        
        $provider['relevance_score'] = round($relevance_score, 2);
    }
    
    // Sort by relevance score
    usort($providers, function($a, $b) {
        return $b['relevance_score'] <=> $a['relevance_score'];
    });
}

// Get categories for filter
$categories = getMultipleResults($conn, "SELECT category_id, category_name FROM service_categories ORDER BY category_name");

// Get unique cities from database
$cities = getMultipleResults($conn, "SELECT DISTINCT city FROM users WHERE city IS NOT NULL AND city != '' ORDER BY city LIMIT 50");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Search - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <?= getLocationScript() ?>
    <style>
        body {
            background: #f8f9fa;
        }
        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
        }
        .search-box {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .search-box input {
            border-radius: 25px !important;
            padding: 0.75rem 1.5rem !important;
            border: 2px solid rgba(255,255,255,0.3) !important;
            color: white;
        }
        .search-box input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        .search-box button {
            border-radius: 25px !important;
            padding: 0.75rem 2rem !important;
        }
        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            position: sticky;
            top: 1rem;
        }
        .filter-group {
            margin-bottom: 1.5rem;
        }
        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }
        .filter-group select,
        .filter-group input {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .provider-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
        }
        .provider-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .provider-header {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: start;
            border-bottom: 1px solid #f0f0f0;
        }
        .provider-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }
        .provider-category {
            display: inline-block;
            background: #f0f0f0;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
        }
        .provider-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }
        .rating-stars {
            color: #ffc107;
        }
        .provider-details {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .detail-icon {
            font-size: 1.5rem;
            color: #667eea;
            min-width: 24px;
        }
        .detail-content {
            flex: 1;
        }
        .detail-label {
            font-size: 0.85rem;
            color: #999;
        }
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        .price-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .provider-footer {
            padding: 1.5rem;
            background: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn-book {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.65rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-book:hover {
            transform: scale(1.05);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .relevance-badge {
            background: #e7f3ff;
            color: #0066cc;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .no-results {
            text-align: center;
            padding: 3rem 1rem;
            background: white;
            border-radius: 10px;
        }
        .no-results i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        .pagination-custom {
            justify-content: center;
            margin-top: 2rem;
        }
        .sort-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .sort-btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            background: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .sort-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .sort-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        .search-tips {
            background: #f0f8ff;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }
        .search-tips strong {
            color: #0066cc;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="search-header">
        <div class="container">
            <h1 class="mb-3">🔍 Smart Service Search</h1>
            <p class="mb-4">Find the best service providers with AI-powered recommendations</p>
            
            <form method="GET" class="search-box">
                <input 
                    type="text" 
                    name="keyword" 
                    placeholder="e.g., 'cheap electrician near me'" 
                    value="<?php echo htmlspecialchars($keyword); ?>"
                    class="form-control"
                >
                <input type="hidden" id="user_location" name="city" value="<?php echo htmlspecialchars($city); ?>">
                <button type="submit" class="btn btn-light">
                    <i class="bi bi-search"></i> Search
                </button>
                <button type="button" class="btn btn-light" onclick="getAndSetUserLocation()" title="Find nearby services">
                    <i class="bi bi-geo-alt"></i> Near Me
                </button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="search-filters">
                    <h5 class="mb-3">🎯 Filters</h5>
                    <form method="GET">
                        <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
                        
                        <div class="filter-group">
                            <label>Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" 
                                        <?php echo ($category == $cat['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>City Location</label>
                            <div style="display: flex; gap: 6px;">
                                <select name="city" class="form-select form-select-sm" style="flex: 1;">
                                    <option value="">All Cities</option>
                                    <option value="near me" <?php echo ($city == 'near me') ? 'selected' : ''; ?>>📍 Near Me</option>
                                    <?php foreach ($cities as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['city']); ?>" 
                                            <?php echo ($city == $c['city']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="getAndSetUserLocation()" title="Auto-detect your location">
                                    📍
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">
                                📍 Current location: <strong><?php echo htmlspecialchars($city ?: $user_city); ?></strong>
                            </small>
                        </div>

                        <div class="filter-group">
                            <label>Minimum Rating</label>
                            <select name="min_rating" class="form-select form-select-sm">
                                <option value="0" <?php echo ($minRating == 0) ? 'selected' : ''; ?>>Any Rating</option>
                                <option value="3" <?php echo ($minRating == 3) ? 'selected' : ''; ?>>⭐ 3+</option>
                                <option value="3.5" <?php echo ($minRating == 3.5) ? 'selected' : ''; ?>>⭐ 3.5+</option>
                                <option value="4" <?php echo ($minRating == 4) ? 'selected' : ''; ?>>⭐ 4+</option>
                                <option value="4.5" <?php echo ($minRating == 4.5) ? 'selected' : ''; ?>>⭐ 4.5+</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Max Price (Rs.)</label>
                            <input type="number" name="max_price" class="form-control form-control-sm" 
                                   placeholder="e.g., 5000" value="<?php echo htmlspecialchars($maxPrice); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-sm">Apply Filters</button>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="col-lg-9">
                <?php if (!empty($keyword) || !empty($category) || !empty($city)): ?>
                    <!-- Search Tips -->
                    <div class="search-tips">
                        <strong>💡 Smart Search Features:</strong>
                        Finds providers by location, price, ratings, reviews & experience. Sorted by relevance!
                    </div>

                    <!-- Sort Options -->
                    <div class="sort-buttons">
                        <a href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>'relevance']); ?>" 
                           class="sort-btn <?php echo ($sortBy == 'relevance') ? 'active' : ''; ?>">
                            <i class="bi bi-pin-angle"></i> Relevance
                        </a>
                        <a href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>'price']); ?>" 
                           class="sort-btn <?php echo ($sortBy == 'price') ? 'active' : ''; ?>">
                            <i class="bi bi-tag"></i> Cheapest
                        </a>
                        <a href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>'rating']); ?>" 
                           class="sort-btn <?php echo ($sortBy == 'rating') ? 'active' : ''; ?>">
                            <i class="bi bi-star"></i> Best Rated
                        </a>
                        <a href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>'reviews']); ?>" 
                           class="sort-btn <?php echo ($sortBy == 'reviews') ? 'active' : ''; ?>">
                            <i class="bi bi-chat-dots"></i> Most Reviewed
                        </a>
                        <a href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>'experience']); ?>" 
                           class="sort-btn <?php echo ($sortBy == 'experience') ? 'active' : ''; ?>">
                            <i class="bi bi-award"></i> Most Experienced
                        </a>
                    </div>

                    <?php if (!empty($providers)): ?>
                        <p class="text-muted mb-3">Found <strong><?php echo $totalResults; ?></strong> service providers</p>

                        <?php foreach ($providers as $provider): ?>
                            <div class="provider-card">
                                <div class="provider-header">
                                    <div>
                                        <div class="provider-name"><?php echo htmlspecialchars($provider['business_name']); ?></div>
                                        <div class="provider-category"><?php echo htmlspecialchars($provider['category_name']); ?></div>
                                        <?php if (!empty($provider['relevance_score'])): ?>
                                            <div class="relevance-badge mt-2">
                                                🎯 Score: <?php echo round($provider['relevance_score'], 1); ?>/100
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="provider-rating">
                                        <span class="rating-stars">
                                            <?php for ($i = 0; $i < floor($provider['avg_rating']); $i++): ?>
                                                ⭐
                                            <?php endfor; ?>
                                        </span>
                                        <strong><?php echo number_format($provider['avg_rating'], 1); ?></strong>
                                        <small class="text-muted">(<?php echo $provider['total_reviews']; ?> reviews)</small>
                                    </div>
                                </div>

                                <div class="provider-details">
                                    <div class="detail-item">
                                        <div class="detail-icon">💰</div>
                                        <div class="detail-content">
                                            <div class="detail-label">Price Range</div>
                                            <div class="detail-value">Rs. <?php echo number_format($provider['min_price']); ?> - <?php echo number_format($provider['max_price']); ?></div>
                                        </div>
                                    </div>

                                    <div class="detail-item">
                                        <div class="detail-icon">📍</div>
                                        <div class="detail-content">
                                            <div class="detail-label">Location</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($provider['city']); ?></div>
                                        </div>
                                    </div>

                                    <div class="detail-item">
                                        <div class="detail-icon">⏱️</div>
                                        <div class="detail-content">
                                            <div class="detail-label">Experience</div>
                                            <div class="detail-value"><?php echo $provider['years_of_experience']; ?> years</div>
                                        </div>
                                    </div>

                                    <div class="detail-item">
                                        <div class="detail-icon">🔧</div>
                                        <div class="detail-content">
                                            <div class="detail-label">Services</div>
                                            <div class="detail-value"><?php echo $provider['service_count']; ?> services</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="provider-footer">
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($provider['business_description'], 0, 60)); ?>...</small>
                                    <a href="provider-profile.php?provider_id=<?php echo $provider['provider_id']; ?>" class="btn btn-book">
                                        View Profile →
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation" class="pagination-custom">
                                <ul class="pagination mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>$sortBy,'page'=>1]); ?>">First</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>$sortBy,'page'=>$page-1]); ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php 
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): 
                                    ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>$sortBy,'page'=>$i]); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>$sortBy,'page'=>$page+1]); ?>">Next</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(['keyword'=>$keyword,'category'=>$category,'city'=>$city,'min_rating'=>$minRating,'max_price'=>$maxPrice,'sort_by'=>$sortBy,'page'=>$totalPages]); ?>">Last</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="no-results">
                            <i class="bi bi-search"></i>
                            <h4>No providers found</h4>
                            <p class="text-muted">Try adjusting your filters or search criteria</p>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        Start searching by entering a keyword like <strong>"cheap electrician near me"</strong>
                    </div>
                <?php endif; ?>
            </div>
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
