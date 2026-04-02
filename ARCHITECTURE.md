# System Architecture - Service Finder v5.0

## 🏗️ High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                   Web/Mobile Frontend                       │
│               (HTML5, CSS3, JavaScript)                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────┴──────────────────────────────────────┐
│            API Gateway & Middleware Layer                    │
│  ┌─────────────────┬────────────────┬─────────────────┐    │
│  │  JWT Auth      │ Rate Limiting  │ Input Validation│    │
│  │  & OAuth2      │  & Throttling  │ & Sanitization │    │
│  └─────────────────┴────────────────┴─────────────────┘    │
└──────────────┬───────────────────────────────────────┬──────┘
               │                                        │
        ┌──────▼──────┐                       ┌────────▼────────┐
        │ REST API    │                       │  WebSocket/Chat │
        │ Endpoints   │                       │    Server       │
        └──────┬──────┘                       └────────┬────────┘
               │                                      │
        ┌──────┴──────┬──────────┬──────────┬────────┴─────┐
        │             │          │          │              │
    ┌───▼───┐  ┌─────▼──┐  ┌───▼────┐ ┌──▼────┐  ┌──────▼──┐
    │ Auth  │  │ Service│  │ Booking│ │ Review│  │AI/Chat  │
    │Engine │  │ Engine │  │ Engine │ │Engine │  │Engine   │
    └───┬───┘  └─────┬──┘  └───┬────┘ └──┬────┘  └──────┬──┘
        │            │         │        │              │
        └────────────┼─────────┼────────┼──────────────┘
                     │         │        │
        ┌────────────┴─────────┴────────┴────────┐
        │   Persistent Storage Layer              │
        ├──────────────────────────────────────────┤
        │                                          │
    ┌───▼────┐  ┌─────────┐  ┌──────────┐  ┌───┐ │
    │ MySQL  │  │ Pinecone│  │  Redis   │  │...│ │
    │  DB    │  │ Vector  │  │  Cache   │  └───┘ │
    │(Main)  │  │  Store  │  │(Session) │        │
    └────────┘  └─────────┘  └──────────┘        │
        │                                          │
        └──────────────────────────────────────────┘
               │
    ┌──────────┴──────────┬──────────────┬─────────┐
    │                     │              │         │
┌───▼────┐        ┌──────▼────┐   ┌────▼──┐  ┌──▼───┐
│Message  │        │External   │   │Web    │  │Event │
│Queue    │        │APIs       │   │Hooks  │  │Bus   │
│(RabbitMQ       │(Stripe,   │   │Mgmt   │  └──────┘
│/Redis)   │        │Twilio)    │   │       │
└────────┘        └───────────┘   └───────┘

```

## 📊 Layered Architecture

### 1. **Presentation Layer**
- User Interface (Web Browser)
- API Clients (Mobile Apps, Third-party)
- WebSocket Connections (Real-time Chat)

### 2. **API Gateway Layer**
- Request routing
- Authentication & Authorization
- Rate limiting & throttling
- Input validation
- CORS handling
- Request/Response logging

### 3. **Business Logic Layer**

#### Authentication & Authorization Service
```php
// JWT Token Generation
POST /api/v1/auth/login
POST /api/v1/auth/register
POST /api/v1/auth/refresh-token

// Permission Checking
RBAC: Admin, Provider, Customer, Support
```

#### Service Catalog Engine
```php
// Service Management
GET /api/v1/services
POST /api/v1/services (Provider)
PUT /api/v1/services/{id} (Provider)
DELETE /api/v1/services/{id} (Provider)

// Categories
GET /api/v1/categories
POST /api/v1/categories (Admin)
```

#### Booking Engine
```php
// Booking Lifecycle
CREATE booking → CONFIRM → IN_PROGRESS → COMPLETE → REVIEW
CANCEL (at any stage with rules)

// State Machine
booking.php handles transitions with validations
```

#### AI/Search Engine
```php
// RAG-based Search
Vector embeddings of services
Semantic similarity matching
Context-aware recommendations

// Content Generation
Service descriptions
Insights generation
Review summaries
```

#### Smart Chatbot
```php
// NLP-powered Chat
Context preservation
Intent recognition
Multi-turn conversations
Integration with support tickets
```

### 4. **Data Access Layer**
- Database queries (prepared statements)
- Object-relational mapping
- Query optimization
- Connection pooling
- Caching strategies

### 5. **External Integration Layer**
- Payment processors (Stripe, JazzCash)
- SMS provider (Twilio)
- Email service (SendGrid)
- Maps API (Google Maps)
- Cloud storage (AWS S3)

---

## 🗄️ Database Architecture

### Main Database (MySQL)

#### Core Tables
```
users (id, email, password, type, status, created_at)
├── Indexes: email, type, status, created_at
├── Storage: 100K+ records
└── Partitioning: By created_at (monthly)

service_categories (id, name, description)
├── Indexes: name
└── Records: ~50

services (id, category_id, provider_id, name, description, price)
├── Indexes: provider_id, category_id, status
├── Storage: 10K+ records
└── ForeignKeys: provider_id → users, category_id → service_categories

bookings (id, service_id, customer_id, provider_id, status, date)
├── Indexes: customer_id, provider_id, status, date
├── Storage: 50K+ records
└── Partitioning: By date (monthly)

reviews (id, booking_id, provider_id, rating, comment)
├── Indexes: provider_id, rating, created_at
└── Full-text index on comment

payments (id, booking_id, amount, method, status)
├── Indexes: booking_id, status, created_at
└── Records: Transaction history
```

### Vector Store (Pinecone/Weaviate)

```
Service Embeddings:
- Service ID: "service_123"
- Embedding: [0.234, -0.156, 0.789, ...]
- Metadata: {category, location, rating, provider_id}
- Dimension: 384 (sentence-transformers)

Query Example:
"plumber near karachi with good reviews"
↓
Convert to embedding
↓
Find nearest neighbors (cosine similarity)
↓
Return top-k matching services
```

### Cache Layer (Redis)

```
Session Data:
SET session:{session_id} {user_data} EX 3600

Service Cache:
SET service:{id} {data} EX 300

Rate Limit:
INCR api_calls:{user_id}:{endpoint}
EXPIRE api_calls:{user_id}:{endpoint} 3600
```

---

## 🔌 API Architecture

### RESTful API Design

**Base URL**: `/api/v1`

#### Authentication
```
POST   /auth/register              Create account
POST   /auth/login                 User login
POST   /auth/refresh-token         Refresh JWT
POST   /auth/logout                Logout
POST   /auth/forgot-password       Password reset
```

#### Users
```
GET    /users/{id}                 Get user profile
PUT    /users/{id}                 Update profile
DELETE /users/{id}                 Delete account
GET    /users/{id}/bookings        User's bookings
PUT    /users/{id}/verify          Email verification
```

#### Services
```
GET    /services                   List services
GET    /services/{id}              Service details
POST   /services                   Create service (Provider)
PUT    /services/{id}              Update service
DELETE /services/{id}              Delete service
GET    /services/search            Smart search with RAG
GET    /services/{id}/stats        Service statistics
```

#### Bookings
```
POST   /bookings                   Create booking
GET    /bookings/{id}              Booking details
GET    /bookings                   List user bookings (paginated)
PUT    /bookings/{id}              Update booking
PUT    /bookings/{id}/cancel       Cancel booking
PUT    /bookings/{id}/complete     Mark complete
```

#### Reviews
```
POST   /reviews                    Create review
GET    /reviews/service/{id}       Service reviews
GET    /reviews/provider/{id}      Provider reviews
PUT    /reviews/{id}               Update review
DELETE /reviews/{id}               Delete review
```

#### AI/Chatbot
```
POST   /ai/search                  AI-powered search
GET    /ai/recommendations         Personalized suggestions
GET    /ai/insights/{service_id}   Generated insights
POST   /chat/message               Send chat message
GET    /chat/history               Chat history
```

#### Admin
```
GET    /admin/users                List all users
GET    /admin/providers            List providers
POST   /admin/providers/{id}/verify Verify provider
GET    /admin/analytics            System analytics
GET    /admin/reports              Business reports
```

### Response Format

**Success Response**:
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "timestamp": "2026-04-01T12:34:56Z"
}
```

**Error Response**:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input parameters",
    "details": [
      {"field": "email", "message": "Invalid email format"}
    ]
  },
  "timestamp": "2026-04-01T12:34:56Z"
}
```

---

## 🔐 Security Architecture

### Authentication Flow
```
User Login
    ↓
Credentials Validation
    ↓
Password Verification (bcrypt)
    ↓
Generate JWT Token
    ↓
Set Secure HttpOnly Cookie
    ↓
Return Token + Refresh Token
```

### JWT Token Structure
```
Header:  {alg: "HS256", typ: "JWT"}
Payload: {sub: user_id, role: "customer", exp: 1700000000}
Signature: HMACSHA256(header.payload, secret)
```

### Authorization Model
```
RBAC (Role-Based Access Control):
├── Admin
│   ├── Manage users
│   ├── Manage providers
│   └── View analytics
├── Service Provider
│   ├── Create/Edit services
│   ├── Manage bookings
│   └── View earnings
└── Customer
    ├── Search services
    ├── Create bookings
    └── Write reviews
```

### Encryption Layer
```
Sensitive Fields:
- Passwords: bcrypt (cost: 12)
- API Keys: AES-256 encryption
- Payment info: Tokenized (PCI DSS)
- Phone numbers: Encrypted at rest
- Addresses: Encrypted at rest

Transport Security:
- HTTPS/TLS 1.3
- HSTS (Strict-Transport-Security)
- Certificate pinning (optional)
```

---

## 📈 Performance Architecture

### Caching Strategy
```
Level 1: Browser Cache (Static assets)
Level 2: Redis Cache (API responses)
Level 3: Database Cache (Query cache)
Level 4: CDN Cache (Images, files)

Cache Invalidation:
- TTL-based expiration
- Tag-based invalidation
- Event-based invalidation
```

### Database Optimization
```
Query Optimization:
1. Use prepared statements
2. Optimize JOIN operations
3. Add appropriate indexes
4. Use EXPLAIN ANALYZE
5. Pagination (LIMIT, OFFSET)

Indexing Strategy:
- Primary key on id
- Composite indexes on (user_id, status)
- Partial indexes on status = 'active'
- Full-text indexes on searchable fields

Connection Pooling:
- Max connections: 100
- Min connections: 10
- Connection timeout: 30s
```

### Load Balancing
```
                    Load Balancer (Nginx)
                            │
                ┌───────────┼───────────┐
                │           │           │
            ┌───▼───┐   ┌───▼───┐   ┌──▼───┐
            │PHP-FPM│   │PHP-FPM│   │PHP-FPM│
            │ Pool 1│   │ Pool 2│   │ Pool 3│
            └───┬───┘   └───┬───┘   └──┬───┘
                │           │          │
                └───────────┼──────────┘
                            │
                    ┌───────▴────────┐
                    │                │
                ┌───▼──┐        ┌───▼──┐
                │MySQL │ (Repl)│MySQL │
                │Master│←→     │Slave │
                └──────┘        └──────┘
```

---

## 🔄 Asynchronous Processing

### Message Queue Architecture
```
Event Producer
    │
    ├─→ Send Email
    ├─→ Generate Report
    ├─→ Process Payment
    ├─→ Update Analytics
    └─→ Notify User

All events go to Message Queue (RabbitMQ)
    │
    ├─→ Email Worker (Picks up email events)
    ├─→ Report Worker (Picks up report events)
    ├─→ Payment Worker (Picks up payment events)
    └─→ Notification Worker (Picks up notification events)
```

### Job Processing Flow
```
User Creates Booking
    ↓
Booking Created Event
    ↓
Event pushed to Queue
    ↓
Worker picks job
    ↓
Process (Send email, update inventory, etc.)
    ↓
Mark complete / Retry on failure
```

---

## 🌐 Scalability Architecture

### Horizontal Scaling
- Multiple PHP-FPM processes
- Load balancer (Round-robin, Least connections)
- Database replication (Master-Slave)
- Cache cluster (Redis Sentinel)
- Message queue distributed setup

### Vertical Scaling
- Increase server resources (CPU, RAM, Disk)
- Optimize PHP configuration
- Fine-tune MySQL settings
- Increase cache size

### Database Scaling
```
Write Operations         Read Operations
    │                          │
    └──→ Master DB ←──────────┘
         │
    ┌────┴─────┬──────────┐
    │           │          │
 Slave 1    Slave 2    Slave 3
 (Read)     (Read)     (Read)
```

---

## 📊 Monitoring & Observability

### Metrics Collected
- API response times
- Database query performance
- Cache hit/miss rates
- Queue processing delays
- Error rates by endpoint
- User session metrics

### Logging Architecture
```
Application Logs
    ↓
┌───────────┬────────────┬──────────┐
│ Error Log │ Access Log │ App Log  │
└───────────┴────────────┴──────────┘
    ↓
ELK Stack (Elasticsearch, Logstash, Kibana)
    ↓
Alerting (PagerDuty, Slack)
```

### Health Checks
```
GET /health                    System health
GET /health/database           Database connectivity
GET /health/cache              Redis connectivity
GET /health/queue              Message queue status
GET /health/external-services  Third-party APIs
```

---

## 🚀 Deployment Architecture

### Development Environment
- Single VM or Local machine
- SQLite or local MySQL
- All services on single machine

### Staging Environment
- 2-3 node cluster
- Separate database server
- Redis cache
- Message queue

### Production Environment
- 5+ node cluster
- Database replication
- Cache cluster
- Load balancer
- CDN
- Monitoring stack
- Backup systems

---

## 🔗 Integration Points

### Payment Processing
```
Booking Created
    ↓
Initiate Payment
    ↓
User Payment Gateway (Stripe/JazzCash)
    ↓
Payment Webhook
    ↓
Update Booking Status
    ↓
Send Confirmation Email
```

### SMS Notifications
```
Booking Status Change
    ↓
Queue SMS Job
    ↓
Queue Processor picks job
    ↓
Twilio API Call
    ↓
SMS sent to user
    ↓
Webhook confirmation
```

### Real-time Notifications
```
Event Occurs
    ↓
WebSocket Event emitted
    ↓
All connected clients receive notification
    ↓
Client-side UI updated
```

---

## 📐 Data Flow Diagrams

### Service Search Flow
```
User Query: "plumber near karachi"
    ↓
NLP Processing
    ↓
Convert to embedding
    ↓
Query Vector Store (Pinecone)
    ↓
Get service IDs + scores
    ↓
Fetch full details from MySQL
    ↓
Apply filters (rating, price, availability)
    ↓
Sort & paginate
    ↓
Return results with rankings
```

### Booking Flow
```
User selects service
    ↓
Fill booking details
    ↓
Validate input
    ↓
Check availability
    ↓
Apply discount (if any)
    ↓
Create booking (status: pending)
    ↓
Initiate payment
    ↓
Payment confirmed webhook
    ↓
Update booking (status: confirmed)
    ↓
Send confirmation emails
    ↓
Notify provider in real-time
```

### Review Flow
```
Booking marked complete
    ↓
Send review request email/SMS
    ↓
Customer submits review
    ↓
Validate review (no spam)
    ↓
Store review
    ↓
Update provider rating
    ↓
Trigger recommendation update
    ↓
Notify provider
    ↓
Update search rankings
```

---

## 🎯 Design Principles

1. **Separation of Concerns** - Each component has single responsibility
2. **DRY (Don't Repeat Yourself)** - Reusable functions and classes
3. **KISS (Keep It Simple, Stupid)** - Simple, readable code
4. **SOLID Principles** - S, O, L, I, D followed
5. **Scalability First** - Designed for growth
6. **Security First** - Security built into design
7. **Observability** - Everything is logged and monitored
8. **Testability** - Easy to unit test and integration test

---

This architecture supports all 5 phases and provides a foundation for enterprise-scale service marketplace.
