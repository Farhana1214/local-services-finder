<?php
include '../database_connection.php';
include '../config.php';
include '../helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = sanitize_input($input['query'] ?? '');
    
    if (strlen($query) < 1) {
        echo json_encode(['suggestions' => []]);
        exit;
    }
    
    $searchTerm = '%' . $query . '%';
    
    // Search for providers and services that match
    $sql = "
        SELECT DISTINCT
            sp.provider_id,
            sp.business_name as name,
            'provider' as type,
            c.category_name as category,
            sp.business_description as description
        FROM service_providers sp
        JOIN service_categories c ON sp.category_id = c.category_id
        WHERE (sp.business_name LIKE ? 
               OR sp.business_description LIKE ?
               OR c.category_name LIKE ?)
        AND sp.is_active = 1 
        AND sp.is_verified = 1
        
        UNION
        
        SELECT DISTINCT
            s.service_id,
            s.service_name as name,
            'service' as type,
            c.category_name as category,
            sp.business_name as description
        FROM services s
        JOIN service_categories c ON s.category_id = c.category_id
        JOIN service_providers sp ON s.provider_id = sp.provider_id
        WHERE (s.service_name LIKE ? OR c.category_name LIKE ?)
        AND sp.is_active = 1 
        AND sp.is_verified = 1
        
        ORDER BY name
        LIMIT 10
    ";
    
    $result = executeQuery($conn, $sql, 'sssss', [
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    ]);
    
    if (!$result['success']) {
        echo json_encode([
            'suggestions' => [],
            'error' => $result['error']
        ]);
        exit;
    }
    
    $stmt = $result['stmt'];
    $queryResult = $stmt->get_result();
    $suggestions = [];
    
    while ($row = $queryResult->fetch_assoc()) {
        $suggestions[] = [
            'id' => $row['provider_id'] ?? $row['service_id'],
            'name' => htmlspecialchars($row['name']),
            'type' => $row['type'],
            'category' => htmlspecialchars($row['category']),
            'description' => htmlspecialchars(substr($row['description'], 0, 50))
        ];
    }
    
    echo json_encode(['suggestions' => $suggestions]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
