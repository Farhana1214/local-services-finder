<?php

/**
 * RAG-Based Smart Search Engine
 * Phase 2: Core Development - AI Integration
 * 
 * Implements Retrieval-Augmented Generation for semantic service search
 * Using vector embeddings for intelligent matching
 */

class AISearchEngine {
    private $conn;
    private $openai_api_key;
    private $vector_db;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->openai_api_key = getenv('OPENAI_API_KEY');
        // Vector store can be Pinecone or Weaviate
        $this->vector_db = new VectorStore($this->openai_api_key);
    }
    
    /**
     * Search services using RAG
     * Converts natural language query to embeddings and finds similar services
     * 
     * @param string $query User search query
     * @param array $filters Optional filters (category, location, price range, rating)
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array Search results with relevance scores
     */
    public function searchWithRAG($query, $filters = [], $limit = 20, $offset = 0) {
        try {
            // Step 1: Convert query to embedding
            $query_embedding = $this->generateEmbedding($query);
            
            if (!$query_embedding) {
                return [
                    'success' => false,
                    'message' => 'Failed to process search query'
                ];
            }
            
            // Step 2: Find similar services from vector store
            $vector_results = $this->vector_db->search(
                $query_embedding,
                ['limit' => $limit * 3, 'offset' => $offset] // Retrieve more for filtering
            );
            
            // Step 3: Fetch full details from database
            $service_ids = array_map(function($result) {
                return $result['service_id'];
            }, $vector_results);
            
            $services = $this->getServiceDetails($service_ids);
            
            // Step 4: Apply additional filters
            if (!empty($filters)) {
                $services = $this->applyFilters($services, $filters);
            }
            
            // Step 5: Enrich with AI-generated insights
            $enriched_services = array_map(function($service) {
                $service['ai_insights'] = $this->generateServiceInsights($service);
                return $service;
            }, array_slice($services, 0, $limit));
            
            return [
                'success' => true,
                'data' => [
                    'services' => $enriched_services,
                    'total' => count($services),
                    'query' => $query
                ]
            ];
            
        } catch (Exception $e) {
            error_log("RAG Search Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate embeddings for services on index creation
     * Called during service creation/update
     * 
     * @param int $service_id
     * @param string $text Service description combined with metadata
     */
    public function indexService($service_id, $text) {
        try {
            $embedding = $this->generateEmbedding($text);
            
            // Store in vector database
            $this->vector_db->upsert([
                'id' => "service_{$service_id}",
                'values' => $embedding,
                'metadata' => [
                    'service_id' => $service_id,
                    'indexed_at' => date('Y-m-d H:i:s')
                ]
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Indexing Error for service {$service_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate embeddings using OpenAI API
     * Uses sentence-transformers model for efficient embeddings
     * 
     * @param string $text Text to embed
     * @return array|null Embedding vector
     */
    private function generateEmbedding($text) {
        try {
            // Cache embeddings for common queries
            $cache_key = 'embedding_' . md5($text);
            if ($cached = getCachedData($cache_key)) {
                return $cached;
            }
            
            // Call OpenAI Embedding API
            $response = $this->callOpenAIAPI(
                'https://api.openai.com/v1/embeddings',
                'POST',
                [
                    'model' => 'text-embedding-3-small',
                    'input' => $text
                ]
            );
            
            if ($response && isset($response['data'][0]['embedding'])) {
                $embedding = $response['data'][0]['embedding'];
                
                // Cache for 30 days
                setCachedData($cache_key, $embedding, 86400 * 30);
                
                return $embedding;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Embedding Generation Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get full service details from database
     * 
     * @param array $service_ids Service IDs from vector search
     * @return array Service details with ratings and provider info
     */
    private function getServiceDetails($service_ids) {
        if (empty($service_ids)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
        
        $query = "
            SELECT 
                s.id, s.category_id, s.provider_id, 
                s.name, s.description, s.price,
                sp.business_name, sp.city, sp.rating,
                COUNT(r.id) as review_count,
                AVG(r.rating) as avg_rating
            FROM services s
            LEFT JOIN service_providers sp ON s.provider_id = sp.id
            LEFT JOIN reviews r ON sp.id = r.provider_id
            WHERE s.id IN ({$placeholders})
            GROUP BY s.id
        ";
        
        $result = executeQuery(
            $this->conn,
            $query,
            str_repeat('i', count($service_ids)),
            $service_ids
        );
        
        if ($result['success']) {
            $stmt = $result['stmt'];
            $services = [];
            
            while ($row = $stmt->fetch_assoc()) {
                $services[] = $row;
            }
            
            return $services;
        }
        
        return [];
    }
    
    /**
     * Apply additional filters to search results
     * 
     * @param array $services
     * @param array $filters
     * @return array Filtered services
     */
    private function applyFilters($services, $filters) {
        return array_filter($services, function($service) use ($filters) {
            // Filter by category
            if (isset($filters['category_id']) && 
                $service['category_id'] != $filters['category_id']) {
                return false;
            }
            
            // Filter by location/city
            if (isset($filters['city']) && 
                stripos($service['city'], $filters['city']) === false) {
                return false;
            }
            
            // Filter by price range
            if (isset($filters['min_price']) && 
                $service['price'] < $filters['min_price']) {
                return false;
            }
            
            if (isset($filters['max_price']) && 
                $service['price'] > $filters['max_price']) {
                return false;
            }
            
            // Filter by rating
            if (isset($filters['min_rating']) && 
                $service['avg_rating'] < $filters['min_rating']) {
                return false;
            }
            
            return true;
        });
    }
    
    /**
     * Generate AI insights for a service
     * Creates auto-generated descriptions, strengths, and recommendations
     * 
     * @param array $service Service data
     * @return array AI-generated insights
     */
    public function generateServiceInsights($service) {
        try {
            $prompt = "Analyze this service and provide brief professional insights:
            Service: {$service['name']}
            Provider: {$service['business_name']}
            Rating: {$service['avg_rating']}/5.0
            Price: {$service['price']}
            Location: {$service['city']}
            
            Provide JSON response with:
            {
                'summary': 'one line summary',
                'why_choose': 'why choose this service',
                'best_for': 'best for which tasks/customers',
                'confidence': 'confidence score (0-100)'
            }";
            
            $response = $this->callOpenAIAPI(
                'https://api.openai.com/v1/chat/completions',
                'POST',
                [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a professional service analyzer. Respond only with valid JSON.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 200
                ]
            );
            
            if ($response && isset($response['choices'][0]['message']['content'])) {
                $content = $response['choices'][0]['message']['content'];
                return json_decode($content, true) ?? [];
            }
            
            return [];
        } catch (Exception $e) {
            error_log("Insights Generation Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get personalized recommendations for a user
     * Based on search history, bookings, and ratings
     * 
     * @param int $user_id Customer ID
     * @param int $limit Number of recommendations
     * @return array Recommended services
     */
    public function getPersonalizedRecommendations($user_id, $limit = 10) {
        try {
            // Get user's previous searches and bookings
            $user_history = $this->getUserHistory($user_id);
            
            if (empty($user_history)) {
                // Return popular services for new users
                return $this->getPopularServices($limit);
            }
            
            // Combine search history into a user profile
            $user_profile = implode(" ", $user_history);
            $user_embedding = $this->generateEmbedding($user_profile);
            
            // Find services similar to user profile
            $recommendations = $this->vector_db->search(
                $user_embedding,
                ['limit' => $limit * 2]
            );
            
            // Get details and personalize
            $service_ids = array_map(function($r) { return $r['service_id']; }, 
                                     $recommendations);
            $services = $this->getServiceDetails($service_ids);
            
            return array_slice($services, 0, $limit);
            
        } catch (Exception $e) {
            error_log("Recommendations Error: " . $e->getMessage());
            return $this->getPopularServices($limit);
        }
    }
    
    /**
     * Get popular/trending services
     * 
     * @param int $limit
     * @return array
     */
    private function getPopularServices($limit = 10) {
        $query = "
            SELECT 
                s.*, 
                COUNT(b.id) as booking_count,
                AVG(r.rating) as avg_rating
            FROM services s
            LEFT JOIN bookings b ON s.id = b.service_id
            LEFT JOIN reviews r ON b.id = r.booking_id
            WHERE s.status = 'active'
            GROUP BY s.id
            ORDER BY booking_count DESC, avg_rating DESC
            LIMIT ?
        ";
        
        return getMultipleResults($this->conn, $query, 'i', [$limit]);
    }
    
    /**
     * Get user's search and booking history
     * 
     * @param int $user_id
     * @return array
     */
    private function getUserHistory($user_id) {
        $query = "
            SELECT 
                s.name, s.description, sc.name as category,
                CONCAT(sp.business_name, ' ', sp.city) as provider_info
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            JOIN service_categories sc ON s.category_id = sc.id
            JOIN service_providers sp ON s.provider_id = sp.id
            WHERE b.customer_id = ?
            ORDER BY b.created_at DESC
            LIMIT 10
        ";
        
        $results = getMultipleResults($this->conn, $query, 'i', [$user_id]);
        
        return array_map(function($row) {
            return $row['name'] . ' ' . $row['description'];
        }, $results ?? []);
    }
    
    /**
     * Call OpenAI API with error handling and rate limiting
     * 
     * @param string $url
     * @param string $method
     * @param array $data
     * @return array|null
     */
    private function callOpenAIAPI($url, $method, $data) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->openai_api_key,
                'Content-Type: application/json'
            ]);
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200 || $http_code === 201) {
                return json_decode($response, true);
            }
            
            error_log("OpenAI API Error (HTTP {$http_code}): {$response}");
            return null;
            
        } catch (Exception $e) {
            error_log("OpenAI API Call Error: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Vector Store Interface
 * Can be implemented with Pinecone, Weaviate, or other vector databases
 */
class VectorStore {
    private $api_key;
    private $endpoint = 'https://api.pinecone.io/v1';
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }
    
    /**
     * Search vector store
     * 
     * @param array $vector Query embedding
     * @param array $options Search options
     * @return array Results with service IDs and scores
     */
    public function search($vector, $options = []) {
        // Implementation depends on chosen vector store
        // This is a template for Pinecone integration
        
        $limit = $options['limit'] ?? 20;
        $offset = $options['offset'] ?? 0;
        
        $request_body = [
            'vector' => $vector,
            'topK' => $limit,
            'includeMetadata' => true,
            'offset' => $offset
        ];
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->endpoint . '/query');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Api-Key: ' . $this->api_key,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200) {
                $data = json_decode($response, true);
                return $data['matches'] ?? [];
            }
            
            return [];
        } catch (Exception $e) {
            error_log("Vector Store Search Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Upsert vector (insert or update)
     * 
     * @param array $vector
     * @return bool
     */
    public function upsert($vector) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->endpoint . '/vectors/upsert');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Api-Key: ' . $this->api_key,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'vectors' => [$vector]
            ]));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $http_code === 200;
        } catch (Exception $e) {
            error_log("Vector Store Upsert Error: " . $e->getMessage());
            return false;
        }
    }
}

?>
