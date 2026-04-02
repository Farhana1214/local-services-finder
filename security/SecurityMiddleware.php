<?php

/**
 * Security & Rate Limiting Middleware
 * Phase 4: Security - API Security & Protection
 * 
 * Implements rate limiting, input validation, CSRF protection
 */

class RateLimiter {
    private $conn;
    private $redis; // Optional Redis for faster rate limiting
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        // Initialize Redis if available
        try {
            $this->redis = new Redis();
            $this->redis->connect('localhost', 6379);
        } catch (Exception $e) {
            $this->redis = null; // Fallback to database
        }
    }
    
    /**
     * Check rate limit for an endpoint
     * 
     * @param string $user_id User identifier
     * @param string $endpoint API endpoint
     * @param int $limit Max requests allowed
     * @param int $window Time window in seconds
     * @return array Rate limit status
     */
    public function checkLimit($user_id, $endpoint, $limit = 100, $window = 3600) {
        $key = "ratelimit:{$user_id}:{$endpoint}";
        $count = 0;
        
        if ($this->redis) {
            // Use Redis (fastest)
            $count = $this->redis->incr($key);
            
            if ($count === 1) {
                $this->redis->expire($key, $window);
            }
        } else {
            // Fallback to database
            $count = $this->getAndIncrementCount($key, $window);
        }
        
        return [
            'allowed' => $count <= $limit,
            'remaining' => max(0, $limit - $count),
            'limit' => $limit,
            'reset_at' => time() + $window
        ];
    }
    
    /**
     * Database-based rate limiting fallback
     * 
     * @param string $key
     * @param int $window
     * @return int Current count
     */
    private function getAndIncrementCount($key, $window) {
        // Clean old entries
        $this->conn->query("
            DELETE FROM rate_limits 
            WHERE created_at < NOW() - INTERVAL {$window} SECOND
        ");
        
        // Get current count
        $query = "
            SELECT COUNT(*) as count FROM rate_limits 
            WHERE rate_key = ? AND created_at > NOW() - INTERVAL {$window} SECOND
        ";
        
        $result = getSingleResult($this->conn, $query, 's', [$key]);
        $count = $result['count'] ?? 0;
        
        // Increment
        $insert = "INSERT INTO rate_limits (rate_key, created_at) VALUES (?, NOW())";
        executeQuery($this->conn, $insert, 's', [$key]);
        
        return $count + 1;
    }
    
    /**
     * Apply rate limit headers
     * 
     * @param array $limit_status Rate limit check result
     */
    public static function applyHeaders($limit_status) {
        header('X-RateLimit-Limit: ' . $limit_status['limit']);
        header('X-RateLimit-Remaining: ' . $limit_status['remaining']);
        header('X-RateLimit-Reset: ' . $limit_status['reset_at']);
    }
}

/**
 * Input Validation & Data Sanitization
 * 
 * Prevent XSS, SQL Injection, and other attacks
 */
class InputValidator {
    
    /**
     * Validate and sanitize input data
     * 
     * @param array $data Input data
     * @param array $rules Validation rules
     * @return array Validation result
     */
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $field_rules) {
            $value = $data[$field] ?? null;
            
            foreach ($field_rules as $rule) {
                $error = self::validateRule($field, $value, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate single rule
     * 
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @return string|null Error message
     */
    private static function validateRule($field, $value, $rule) {
        // Parse rule format: rule_name:param1:param2
        $parts = explode(':', $rule);
        $rule_name = $parts[0];
        $params = array_slice($parts, 1);
        
        return match($rule_name) {
            'required' => self::validateRequired($value, $field),
            'email' => self::validateEmail($value, $field),
            'password' => self::validatePassword($value, $field),
            'phone' => self::validatePhone($value, $field),
            'min' => self::validateMin($value, $field, $params[0] ?? null),
            'max' => self::validateMax($value, $field, $params[0] ?? null),
            'url' => self::validateURL($value, $field),
            'numeric' => self::validateNumeric($value, $field),
            'date' => self::validateDate($value, $field),
            default => null
        };
    }
    
    /**
     * Validate required field
     */
    private static function validateRequired($value, $field) {
        if (empty($value) && $value !== '0') {
            return "{$field} is required";
        }
        return null;
    }
    
    /**
     * Validate email
     */
    private static function validateEmail($value, $field) {
        if (empty($value)) return null;
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "{$field} must be a valid email";
        }
        return null;
    }
    
    /**
     * Validate strong password
     */
    private static function validatePassword($value, $field) {
        if (empty($value)) return null;
        
        $errors = [];
        
        if (strlen($value) < 8) {
            $errors[] = "at least 8 characters";
        }
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = "uppercase letter";
        }
        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = "lowercase letter";
        }
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = "number";
        }
        if (!preg_match('/[!@#$%^&*]/', $value)) {
            $errors[] = "special character";
        }
        
        if (!empty($errors)) {
            return "{$field} must contain: " . implode(", ", $errors);
        }
        
        return null;
    }
    
    /**
     * Validate phone number
     */
    private static function validatePhone($value, $field) {
        if (empty($value)) return null;
        
        $phone = preg_replace('/[^0-9+\-]/', '', $value);
        
        if (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
            return "{$field} must be a valid phone number";
        }
        
        return null;
    }
    
    /**
     * Validate minimum length/value
     */
    private static function validateMin($value, $field, $min) {
        if (empty($value)) return null;
        
        if (is_numeric($value)) {
            if ($value < $min) {
                return "{$field} must be at least {$min}";
            }
        } else {
            if (strlen($value) < $min) {
                return "{$field} must be at least {$min} characters";
            }
        }
        
        return null;
    }
    
    /**
     * Validate maximum length/value
     */
    private static function validateMax($value, $field, $max) {
        if (empty($value)) return null;
        
        if (is_numeric($value)) {
            if ($value > $max) {
                return "{$field} cannot exceed {$max}";
            }
        } else {
            if (strlen($value) > $max) {
                return "{$field} cannot exceed {$max} characters";
            }
        }
        
        return null;
    }
    
    /**
     * Validate URL
     */
    private static function validateURL($value, $field) {
        if (empty($value)) return null;
        
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return "{$field} must be a valid URL";
        }
        
        return null;
    }
    
    /**
     * Validate numeric
     */
    private static function validateNumeric($value, $field) {
        if (empty($value)) return null;
        
        if (!is_numeric($value)) {
            return "{$field} must be numeric";
        }
        
        return null;
    }
    
    /**
     * Validate date format
     */
    private static function validateDate($value, $field) {
        if (empty($value)) return null;
        
        if (!strtotime($value)) {
            return "{$field} must be a valid date";
        }
        
        return null;
    }
}

/**
 * CSRF Token Protection
 * 
 * Prevent Cross-Site Request Forgery attacks
 */
class CSRFProtection {
    
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token from request
     * @return bool
     */
    public static function verifyToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token for forms
     * 
     * @return string HTML hidden input
     */
    public static function getTokenField() {
        return '<input type="hidden" name="csrf_token" value="' . 
               htmlspecialchars(self::generateToken()) . '">';
    }
}

/**
 * CORS Configuration
 * 
 * Control cross-origin resource sharing
 */
class CORS {
    private static $allowed_origins = [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:8000',
        'https://yourdomain.com'
    ];
    
    /**
     * Apply CORS headers
     * 
     * @param string $origin Origin header from request
     */
    public static function apply($origin = null) {
        $origin = $origin ?? ($_SERVER['HTTP_ORIGIN'] ?? '');
        
        // Check if origin is allowed
        $allowed = false;
        foreach (self::$allowed_origins as $allowed_origin) {
            if ($origin === $allowed_origin || 
                preg_match('/' . preg_quote($allowed_origin) . '/', $origin)) {
                $allowed = true;
                break;
            }
        }
        
        if ($allowed) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Max-Age: 86400');
        }
    }
    
    /**
     * Handle preflight requests
     */
    public static function handlePreflight() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::apply();
            http_response_code(200);
            exit();
        }
    }
}

/**
 * Security Headers
 * 
 * Set security-related HTTP headers
 */
class SecurityHeaders {
    
    public static function apply() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
        
        // HSTS (HTTP Strict Transport Security)
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Feature Policy
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    }
}

?>
