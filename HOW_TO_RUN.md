# 🚀 Service Finder - Localhost Setup Guide

## Step 1: Start XAMPP

### Windows:
1. Open **XAMPP Control Panel** (usually `C:\xampp\xampp-control.exe`)
2. Click **Start** next to:
   - ✅ Apache
   - ✅ MySQL
3. Wait for both to show **green** status

## Step 2: Access Your Application

### Open in Browser:
```
http://localhost/farhana/
```

### Home Page:
- You'll see the Service Finder landing page
- Click **Search Services** to explore

## Step 3: Setup Database (First Time Only)

### Option A: Import Sample Data
```
http://localhost/farhana/import-data.php
```
- This adds all test providers, services, cities
- Takes ~3 seconds
- Shows success message ✅

### Option B: Check Database Status
```
http://localhost/farhana/check-data.php
```
- Shows how many users, providers, services exist
- Tells you if data was imported

### Option C: Check Services by City
```
http://localhost/farhana/check-cities.php
```
- Shows which cities have services
- Shows all available services

## Step 4: Register & Login

### Create Customer Account:
1. Go to: `http://localhost/farhana/user_registeration.php?type=customer`
2. Fill form (username, email, password)
3. **Password must have:** uppercase, number, special char (e.g., `Password123!@`)
4. Click **Register**
5. Go to **Login**: `http://localhost/farhana/login.php`
6. Use your credentials

### Create Service Provider:
1. Go to: `http://localhost/farhana/user_registeration.php?type=provider`
2. Fill form + business details
3. Click **Register**
4. Login with provider account

### OR Quick Admin Add Provider:
```
http://localhost/farhana/admin-add-provider.php?key=admin123
```
- Instantly creates verified provider
- Auto password: `Provider123!`

## Step 5: Use Main Features

### 📍 Search Services:
```
http://localhost/farhana/search.php
```
- Type: "plumber", "electrical", "cleaning"
- See provider autocomplete suggestions ✅
- Click provider to see details

### 💬 Smart Chatbot:
```
http://localhost/farhana/chatbot.php
```
- Say: "I need plumber"
- Says: "I need electrical work"
- Autodetects service type → shows providers

### 📅 Book Service (as Customer):
1. Search for service
2. Click **View Profile**
3. Click **Book Service**
4. Fill booking details
5. Submit booking

### 🏪 Manage Services (as Provider):
1. Login as provider
2. Go to **Provider Dashboard**: `http://localhost/farhana/provider-dashboard.php`
3. Click **Manage Services**
4. Add/Edit/Delete services

### 📱 My Bookings:
```
http://localhost/farhana/my-bookings.php
```
- See all your bookings (customer side)
- View booking details
- Add reviews

### ⭐ Reviews:
```
http://localhost/farhana/my-reviews.php
```
- Write reviews for completed services
- Rate providers 1-5 stars

### 💬 Support/Chatbot:
```
http://localhost/farhana/contact-support.php
```
- Create support tickets
- Chat with support team

## Key URLs Summary

| Feature | URL |
|---------|-----|
| Home | `http://localhost/farhana/` |
| Register | `http://localhost/farhana/user_registeration.php` |
| Login | `http://localhost/farhana/login.php` |
| Search | `http://localhost/farhana/search.php` |
| Chatbot | `http://localhost/farhana/chatbot.php` |
| Dashboard | `http://localhost/farhana/dashboard.php` |
| Provider Dashboard | `http://localhost/farhana/provider-dashboard.php` |
| Manage Services | `http://localhost/farhana/manage-services.php` |
| My Bookings | `http://localhost/farhana/my-bookings.php` |
| My Reviews | `http://localhost/farhana/my-reviews.php` |
| Support Tickets | `http://localhost/farhana/contact-support.php` |
| Admin Add Provider | `http://localhost/farhana/admin-add-provider.php?key=admin123` |

## Test with Sample Data

### Sample Customer (after import):
- Username: `john_doe`
- Password: `Customer123!@`

### Sample Provider (after import):
- Username: `pro_plumber`
- Password: `Provider123!@`

## Troubleshooting

### ❌ "Database connection failed"
- Open XAMPP Control Panel
- Start MySQL
- Wait 3 seconds
- Refresh page

### ❌ "No services found"
- Run: `http://localhost/farhana/import-data.php`
- This imports all sample data

### ❌ "Can't login"
- Run: `http://localhost/farhana/test-password.php`
- This tests password hashing

### ❌ "404 Not Found"
- Make sure folder is: `C:\xampp\htdocs\farhana\`
- Restart Apache

## Quick Start (30 seconds!)

1. ✅ Start XAMPP (Apache + MySQL)
2. ✅ Visit: `http://localhost/farhana/import-data.php` (import data)
3. ✅ Visit: `http://localhost/farhana/user_registeration.php` (create account)
4. ✅ Visit: `http://localhost/farhana/login.php` (login)
5. ✅ Visit: `http://localhost/farhana/search.php` (search services)

**You're ready to go!** 🎉
