# 🚀 Service Finder Pakistan - AI-Integrated Service Booking Platform v5.0

> Production-ready service booking marketplace with AI-powered smart search, automated content generation, intelligent chatbot, enterprise security, and third-party integrations.

**Version**: 5.0 (Complete 5-Phase Implementation)
**Status**: ✅ Production Ready
**Last Updated**: April 2026

## Features

### For Customers
- **User Registration & Authentication** - Secure account creation and login
- **Service Search** - Filter by category, location, keyword, and ratings
- **Service Booking** - Easy booking with multiple payment options
- **Reviews & Ratings** - Leave detailed reviews for completed services
- **Favorite Providers** - Save favorite service providers
- **Booking History** - View all past and upcoming bookings
- **Dashboard** - Manage profile and view statistics
- **Payment System** - Support for multiple payment methods (Card, UPI, Wallet, Cash)

### For Service Providers
- **Business Profile** - Create and manage professional profile
- **Service Management** - List and manage services with pricing
- **Booking Management** - Receive and manage service bookings
- **Rating System** - Build professional reputation through reviews
- **Statistics** - Track jobs completed and earnings
- **Transaction History** - View payment and booking records

### Admin Features
- **User Management**
- **Service Provider Verification**
- **Category Management**
- **Promo Code Management**
- **Support Ticket Handling**

## Project Structure

```
mids/
├── structure.sql                 # Database schema
├── dummy_data.sql               # Sample data
├── config.php                   # Configuration settings
├── database_connection.php       # Database connection (improved)
├── helpers.php                  # Helper functions
├── index.php                    # Home page
├── login.php                    # Login page
├── user_registeration.php       # Registration page
├── logout.php                   # Logout handler
├── dash board.php               # Customer dashboard
├── provider-dashboard.php       # Provider dashboard
├── search.php                   # Service search
├── provider-profile.php         # Provider profile
├── booking.php                  # Booking page
├── booking-confirmation.php     # Booking confirmation
├── booking-details.php          # Booking details & review
└── profile.php                  # User profile (to be created)
```

## Database Schema

### Tables
1. **users** - Customer and provider accounts
2. **service_categories** - Service categories
3. **service_providers** - Provider profiles
4. **services** - Individual services offered
5. **bookings** - Service bookings
6. **reviews** - Customer reviews and ratings
7. **payments** - Payment records
8. **favorites** - Favorite providers
9. **promo_codes** - Discount codes
10. **support_tickets** - Customer support
11. **service_categories** - Service types

## Installation & Setup

### Prerequisites
- XAMPP or similar PHP server (PHP 7.4+)
- MySQL 5.7+
- Web browser

### Step 1: Setup Database

1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Create a new database named `service_finder`
3. Import the database schema:
   - Go to Import tab
   - Select `structure.sql` file
   - Click Import
4. Import dummy data:
   - Select `dummy_data.sql` file
   - Click Import

**Alternative Method (Command Line):**
```bash
mysql -u root -p < structure.sql
mysql -u root -p < dummy_data.sql
```

### Step 2: Configure Database Connection

Edit `database_connection.php`:
```php
define('DB_HOST', 'localhost');      // Your database host
define('DB_USER', 'root');           // Your database user
define('DB_PASSWORD', '');           // Your database password
define('DB_NAME', 'service_finder'); // Database name
```

### Step 3: File Permissions

Create necessary directories:
```bash
mkdir uploads
mkdir uploads/profiles
mkdir uploads/services
chmod 755 uploads uploads/profiles uploads/services
```

### Step 4: Access the Application

1. Open your browser
2. Navigate to: `http://localhost/mids/`
3. You should see the home page

## Test Accounts

### Customer Account
- **Username:** john_doe
- **Email:** john@example.com
- **Password:** (Set your own - min 8 chars with uppercase, number, special char)

### Service Provider Account  
- **Username:** plumber_pro
- **Email:** plumber@example.com
- **Password:** (Set your own)

## Key Features Breakdown

### 1. Authentication System
- Secure password hashing (bcrypt)
- Session management with timeout
- Prepared statements to prevent SQL injection
- Input sanitization

### 2. Search Functionality
- Filter by keyword, category, city, rating
- Sort by rating, price, reviews, newest
- Pagination support
- Distance calculation (Haversine formula)

### 3. Booking System
- Real-time availability checking
- Promo code validation
- Multiple payment methods
- Transaction logging
- Booking confirmation emails (optional)

### 4. Review System
- 1-5 star ratings
- Detailed category ratings (cleanliness, professionalism, etc.)
- Review moderation ready
- Helpful votes

### 5. Payment System
- Multiple payment methods
- Transaction tracking
- Refund processing capability
- Commission calculation

## Configuration Options

Edit `config.php` to customize:

```php
define('SITE_NAME', 'Service Finder');
define('CURRENCY', '$');
define('ITEMS_PER_PAGE', 12);
define('SERVICE_COMMISSION', 10); // 10% commission
define('PASSWORD_MIN_LENGTH', 8);
```

## Security Features

1. **SQL Injection Prevention** - Prepared statements
2. **XSS Protection** - Input sanitization and output encoding
3. **Password Security** - Bcrypt hashing
4. **Session Security** - Session timeout and validation
5. **CSRF Protection** - Form token validation (recommended to add)
6. **File Upload Security** - Extension and size restrictions

## API/Helper Functions

### User Functions
- `isLoggedIn()` - Check if user is logged in
- `requireLogin()` - Redirect if not logged in
- `hashPassword($password)` - Hash password
- `verifyPassword($password, $hash)` - Verify password

### Formatting Functions
- `formatCurrency($amount)` - Format as currency
- `formatDate($date)` - Format date
- `formatDateTime($datetime)` - Format date and time
- `truncateText($text, $length)` - Truncate text

### Query Functions
- `getSingleResult($conn, $query, $types, $params)` - Get one row
- `getMultipleResults($conn, $query, $types, $params)` - Get multiple rows
- `getProviderProfile($conn, $provider_id)` - Get provider details
- `getServicesByProvider($conn, $provider_id)` - Get services
- `getUserBookings($conn, $user_id)` - Get user bookings

## Common Workflows

### Customer Registration
1. Visit Registration page
2. Select "Customer" type
3. Fill in personal details
4. Submit
5. Automatic login or redirect to login

### Service Booking
1. Search for services
2. View provider profile
3. Click "Book Service"
4. Fill booking details
5. Apply promo code (optional)
6. Select payment method
7. Confirm booking
8. View confirmation

### Provider Management
1. Create service provider account
2. Set up business profile
3. Add services with pricing
4. Manage bookings
5. View reviews and ratings
6. Track statistics

## Customization Guide

### Adding New Service Categories
1. Go to phpMyAdmin
2. Insert row in `service_categories` table
3. Add category name and description
4. New category appears in search filters

### Modifying Payment Methods
Edit in `config.php` or database:
```sql
UPDATE promo_codes SET discount_value = 15 WHERE code = 'SAVE10';
```

### Changing Commission Rates
In `config.php`:
```php
define('SERVICE_COMMISSION', 15); // Changed from 10%
```

## Performance Optimization

1. **Database Indexing** - Indexes on commonly searched fields
2. **Pagination** - Prevents loading large datasets
3. **Query Optimization** - Proper JOIN statements
4. **Caching** - Session data caching

## Future Enhancements

- [ ] Real-time notification system
- [ ] SMS/Email notifications
- [ ] Mobile app (React Native)
- [ ] Advanced analytics dashboard
- [ ] Automated invoicing
- [ ] Insurance integration
- [ ] Escrow payment system
- [ ] AI-based recommendation engine
- [ ] Video call integration
- [ ] Multi-language support

## Troubleshooting

### "Connection Failed" Error
- Check database credentials in `database_connection.php`
- Ensure MySQL server is running
- Verify database name is correct

### 404 Page Not Found
- Check file paths in URLs
- Ensure all files are in `/mids/` directory
- Verify file names match (case-sensitive on Linux)

### Session Issues
- Clear browser cookies
- Check PHP session settings
- Ensure `/tmp` directory has write permissions

### File Upload Not Working
- Create `uploads/` directory
- Set proper permissions: `chmod 755 uploads`
- Check `php.ini` upload_max_filesize and post_max_size

## Database Backup

### Backup
```bash
mysqldump -u root -p service_finder > backup.sql
```

### Restore
```bash
mysql -u root -p service_finder < backup.sql
```

## Support & Contact

For issues or feature requests:
- GitHub Issues: [Link to repo]
- Email: support@servicefinder.com
- Documentation: [Link to docs]

## License

This project is licensed under the MIT License - see LICENSE file for details.

## Contributors

- PHP Expert (Development)
- Database Architect
- UI/UX Designer

## Changelog

### Version 1.0.0 (Current)
- Initial release
- Core features: Registration, Search, Booking, Reviews
- Payment system integration
- Dashboard for customers and providers

---

**Happy Booking!** 🎉

For the latest updates, visit: `http://localhost/mids/`

---

## Quick Reference

| Feature | File | Function |
|---------|------|----------|
| Search | search.php | getMultipleResults() |
| Booking | booking.php | executeQuery() |
| Reviews | booking-details.php | getSingleResult() |
| Provider | provider-profile.php | getProviderProfile() |
| Dashboard | dashboard.php | getUserBookings() |

---

Made with ❤️ using PHP and MySQL
