-- Update Service Finder Dummy Data for Pakistan

USE service_finder;

-- Clear existing data (except structure)
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM bookings;
DELETE FROM payments;
DELETE FROM reviews;
DELETE FROM services;
DELETE FROM favorites;
DELETE FROM service_providers;
DELETE FROM users;
DELETE FROM promo_codes;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. KEEP SERVICE CATEGORIES (Already exist)

-- 2. INSERT CUSTOMERS (Pakistani Users)
INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code, user_type, is_verified) VALUES
('ali_khan', 'ali@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q', 'Ali', 'Khan', '03001234567', '123 Mall Road', 'Lahore', 'Punjab', '54000', 'customer', 1),
('fatima_ahmad', 'fatima@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Fatima', 'Ahmad', '03002234567', '456 Blue Area', 'Islamabad', 'ICT', '44000', 'customer', 1),
('hassan_raza', 'hassan@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Hassan', 'Raza', '03003234567', '789 Defense', 'Karachi', 'Sindh', '75500', 'customer', 1),
('ayesha_malik', 'ayesha@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Ayesha', 'Malik', '03004234567', '321 College Road', 'Rawalpindi', 'Punjab', '46000', 'customer', 1),
('usman_ali', 'usman@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Usman', 'Ali', '03005234567', '654 Cantonment', 'Faisalabad', 'Punjab', '38000', 'customer', 1),
('sara_khan', 'sara@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Sara', 'Khan', '03006234567', '987 Peoples Colony', 'Multan', 'Punjab', '60000', 'customer', 1),
('zain_ahmed', 'zain@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Zain', 'Ahmed', '03007234567', '147 MG Road', 'Peshawar', 'KP', '25000', 'customer', 1),
('hina_malik', 'hina@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Hina', 'Malik', '03008234567', '258 Mini Market', 'Khushab', 'Punjab', '48700', 'customer', 1),
('ahmed_hassan', 'ahmed@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Ahmed', 'Hassan', '03009234567', '369 City Centre', 'Sargodha', 'Punjab', '40100', 'customer', 1),
('rabia_khan', 'rabia@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Rabia', 'Khan', '03010234567', '741 Bazaar Road', 'Jauharabad', 'Punjab', '48200', 'customer', 1);

-- 3. INSERT SERVICE PROVIDERS (Pakistani Providers)
INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code, user_type, is_verified) VALUES
('plumber_lahore', 'plumber@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Muhammad', 'Plumber', '03011234567', 'Heera Mandi', 'Lahore', 'Punjab', '54000', 'service_provider', 1),
('electric_isl', 'electric@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Rafiq', 'Electrician', '03012234567', 'F-6 Sector', 'Islamabad', 'ICT', '44000', 'service_provider', 1),
('clean_karachi', 'cleaning@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Fatima', 'Cleaner', '03013234567', 'DHA Phase', 'Karachi', 'Sindh', '75500', 'service_provider', 1),
('carpenter_rwp', 'carpenter@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Hassan', 'Carpenter', '03014234567', 'Ganj Mandi', 'Rawalpindi', 'Punjab', '46000', 'service_provider', 1),
('painter_fsd', 'painter@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Ahmad', 'Painter', '03015234567', 'Iqbal Town', 'Faisalabad', 'Punjab', '38000', 'service_provider', 1),
('hvac_multan', 'hvac@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Ali', 'HVAC Tech', '03016234567', 'Cantonment', 'Multan', 'Punjab', '60000', 'service_provider', 1),
('garden_pesh', 'garden@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Amir', 'Gardener', '03017234567', 'Hayatabad', 'Peshawar', 'KP', '25000', 'service_provider', 1),
('pet_khushab', 'petcare@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Zara', 'Pet Care', '03018234567', 'Main Bazaar', 'Khushab', 'Punjab', '48700', 'service_provider', 1),
('appliance_sargodha', 'appliance@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Nasir', 'Technician', '03019234567', 'Shahr Sultan', 'Sargodha', 'Punjab', '40100', 'service_provider', 1),
('fitness_jauhar', 'fitness@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Karim', 'Trainer', '03020234567', 'Market Road', 'Jauharabad', 'Punjab', '48200', 'service_provider', 1);

-- 4. INSERT SERVICE PROVIDER PROFILES
INSERT INTO service_providers (user_id, business_name, business_description, category_id, business_phone, business_email, business_address, business_city, business_state, business_zip, years_of_experience, average_rating, total_reviews, total_jobs_completed, is_verified, is_active, latitude, longitude) VALUES
(11, 'Pro Plumbing Lahore', 'لاہور میں 24/7 پلمبنگ کی خدمات', 1, '03011234567', 'plumber@example.com', 'Heera Mandi', 'Lahore', 'Punjab', '54000', 8, 4.8, 150, 500, 1, 1, 31.5497, 74.3436),
(12, 'Elite Electrical Islamabad', 'اسلام آباد میں الیکٹریکل خدمات', 2, '03012234567', 'electric@example.com', 'F-6 Sector', 'Islamabad', 'ICT', '44000', 12, 4.9, 200, 680, 1, 1, 33.6844, 73.0479),
(13, 'Sparkle Cleaning Karachi', 'کراچی میں صفائی کی بہترین خدمات', 3, '03013234567', 'cleaning@example.com', 'DHA Phase', 'Karachi', 'Sindh', '75500', 6, 4.7, 120, 400, 1, 1, 24.8607, 67.0011),
(14, 'Master Carpentry Rawalpindi', 'راولپنڈی میں لکڑی کا کام', 4, '03014234567', 'carpenter@example.com', 'Ganj Mandi', 'Rawalpindi', 'Punjab', '46000', 15, 4.9, 180, 520, 1, 1, 33.5731, 73.1898),
(15, 'Artisan Painters Faisalabad', 'فیصل آباد میں رنگ روغن کی خدمات', 5, '03015234567', 'painter@example.com', 'Iqbal Town', 'Faisalabad', 'Punjab', '38000', 10, 4.6, 140, 450, 1, 1, 31.4181, 72.3458),
(16, 'Cool Comfort HVAC Multan', 'ملتان میں ایئر کنڈیشنر کی خدمات', 6, '03016234567', 'hvac@example.com', 'Cantonment', 'Multan', 'Punjab', '60000', 11, 4.8, 160, 480, 1, 1, 30.1575, 71.4289),
(17, 'Green Gardens Peshawar', 'پشاور میں باغ کی ڈیزائن', 7, '03017234567', 'garden@example.com', 'Hayatabad', 'Peshawar', 'KP', '25000', 9, 4.7, 130, 380, 1, 1, 34.0081, 71.5789),
(18, 'Premium Pet Care Khushab', 'خوشاب میں پالتو جانوروں کی دیکھ بھال', 8, '03018234567', 'petcare@example.com', 'Main Bazaar', 'Khushab', 'Punjab', '48700', 7, 4.8, 110, 320, 1, 1, 32.2965, 71.6455),
(19, 'Quick Fix Sargodha', 'ساہیوال میں سامان کی مرمت', 9, '03019234567', 'appliance@example.com', 'Shahr Sultan', 'Sargodha', 'Punjab', '40100', 8, 4.7, 125, 410, 1, 1, 32.0894, 72.6735),
(20, 'FitZone Jauharabad', 'جوہار آباد میں فٹنس ٹریننگ', 12, '03020234567', 'fitness@example.com', 'Market Road', 'Jauharabad', 'Punjab', '48200', 5, 4.9, 95, 280, 1, 1, 32.3223, 71.7778);

-- 5. INSERT SERVICES (Updated pricing in PKR)
INSERT INTO services (provider_id, service_name, service_description, category_id, price, duration_hours, is_available) VALUES
(1, 'Pipe Repair', 'پائپ کی مرمت', 1, 5000, 1, 1),
(1, 'Bathroom Installation', 'بیتھ روم کی تنصیب', 1, 15000, 4, 1),
(1, 'Drain Cleaning', 'نالی کی صفائی', 1, 7000, 1.5, 1),
(2, 'Electrical Wiring', 'الیکٹریکل تاریں', 2, 10000, 2, 1),
(2, 'Circuit Breaker Upgrade', 'سرکٹ برائیکر اپ گریڈ', 2, 20000, 3, 1),
(2, 'Light Installation', 'روشنی کی تنصیب', 2, 3000, 1, 1),
(3, 'House Cleaning', 'گھر کی صفائی', 3, 15000, 4, 1),
(3, 'Office Cleaning', 'دفتر کی صفائی', 3, 10000, 3, 1),
(3, 'Post-Construction Cleaning', 'تعمیر کے بعد صفائی', 3, 18000, 5, 1),
(4, 'Custom Furniture Building', 'کسٹم فرنیچر', 4, 35000, 8, 1),
(4, 'Door Installation', 'دروازے کی تنصیب', 4, 12000, 3, 1),
(4, 'Cabinet Making', 'کابینٹ بنانا', 4, 25000, 6, 1),
(5, 'Interior Painting', 'اندرونی رنگ کاری', 5, 20000, 4, 1),
(5, 'Exterior Painting', 'بیرونی رنگ کاری', 5, 28000, 5, 1),
(5, 'Accent Wall Painting', 'اہم دیوار رنگنا', 5, 10000, 2, 1),
(6, 'AC Repair', 'ایئر کنڈیشنر کی مرمت', 6, 8000, 1.5, 1),
(6, 'Furnace Installation', 'فرنیس کی تنصیب', 6, 80000, 6, 1),
(6, 'HVAC Maintenance', 'HVAC کی دیکھ بھال', 6, 5000, 1, 1),
(7, 'Garden Design', 'باغ کی ڈیزائن', 7, 20000, 3, 1),
(7, 'Lawn Maintenance', 'سبز باغ کی دیکھ بھال', 7, 3500, 1.5, 1),
(7, 'Tree Trimming', 'درختوں کی کتائی', 7, 10000, 2, 1),
(8, 'Dog Grooming', 'کتے کی صفائی', 8, 5000, 2, 1),
(8, 'Cat Grooming', 'بلی کی صفائی', 8, 4000, 1.5, 1),
(8, 'Pet Boarding', 'پالتو جانوروں کو رکھنا', 8, 2500, 8, 1),
(9, 'Refrigerator Repair', 'فریج کی مرمت', 9, 7000, 1, 1),
(9, 'Washing Machine Repair', 'واشنگ مشین کی مرمت', 9, 6000, 1, 1),
(9, 'Dishwasher Service', 'ڈش واشر کی خدمت', 9, 8000, 1.5, 1),
(10, 'Personal Training Session', 'ذاتی ٹریننگ', 12, 3500, 1, 1),
(10, 'Group Fitness Classes', 'گروپ فٹنس کلاسز', 12, 2000, 1, 1),
(10, 'Nutrition Consultation', 'غذائی مشورہ', 12, 5000, 1, 1);

-- 6. INSERT SAMPLE BOOKINGS
INSERT INTO bookings (user_id, service_id, provider_id, booking_date, service_date, service_time, location, service_notes, status, total_amount, discount_amount, final_amount, payment_method, payment_status) VALUES
(1, 1, 1, '2026-03-15 10:30:00', '2026-03-20', '10:00:00', 'Lahore - Mall Road', 'روغن پائپ کی مرمت', 'completed', 5000, 0, 5000, 'card', 'completed'),
(2, 3, 1, '2026-03-18 14:00:00', '2026-03-25', '14:00:00', 'Islamabad - Blue Area', 'نالی بند ہے', 'completed', 7000, 700, 6300, 'card', 'completed'),
(3, 5, 2, '2026-03-20 09:00:00', '2026-03-28', '09:00:00', 'Karachi - DHA', 'الیکٹریکل پینل اپ گریڈ', 'completed', 20000, 0, 20000, 'card', 'completed'),
(4, 7, 3, '2026-03-01 11:00:00', '2026-03-05', '08:00:00', 'Rawalpindi - Cantonment', 'گھر کی گہری صفائی', 'completed', 15000, 1500, 13500, 'card', 'completed'),
(5, 10, 4, '2026-03-05 13:30:00', '2026-03-15', '10:00:00', 'Faisalabad - Iqbal Town', 'ڈائننگ ٹیبل بنائیں', 'in_progress', 35000, 0, 35000, 'card', 'completed'),
(6, 13, 5, '2026-03-10 15:00:00', '2026-03-20', '09:00:00', 'Multan - Cantonment', 'ڈرائنگ روم رنگیں', 'pending', 20000, 0, 20000, 'card', 'pending'),
(7, 16, 6, '2026-03-12 10:30:00', '2026-03-22', '14:00:00', 'Peshawar - Hayatabad', 'ایئر کنڈیشنر کی مرمت', 'completed', 8000, 0, 8000, 'card', 'completed'),
(8, 22, 8, '2026-03-15 11:00:00', '2026-03-25', '15:00:00', 'Khushab - Main Bazaar', 'کتے کی صفائی', 'pending', 5000, 500, 4500, 'card', 'pending'),
(9, 28, 10, '2026-03-18 16:00:00', '2026-03-28', '18:00:00', 'Sargodha - Shahr Sultan', 'ذاتی ٹریننگ سیشن', 'pending', 3500, 0, 3500, 'card', 'pending'),
(10, 1, 1, '2026-03-20 10:00:00', '2026-03-30', '11:00:00', 'Jauharabad - Market Road', 'پائپ کی مرمت', 'pending', 5000, 0, 5000, 'card', 'pending');

-- 7. INSERT PROMO CODES (PKR Amounts)
INSERT INTO promo_codes (code, description, discount_type, discount_value, valid_from, valid_until, max_uses, current_uses, is_active) VALUES
('SAVE10', '10% تمام سروسز پر', 'percentage', 10, '2026-01-01', '2026-12-31', 100, 5, 1),
('SAVE20', '20% الیکٹریکل سروسز پر', 'percentage', 20, '2026-01-01', '2026-12-31', 50, 2, 1),
('FIRST500', 'پہلی بار زیادہ 500 تک', 'fixed', 500, '2026-01-01', '2026-06-30', 75, 10, 1),
('CLEANING50', 'صفائی پر 50% تک', 'percentage', 50, '2026-01-01', '2026-12-31', 30, 3, 1),
('WELCOME1000', 'نئے صارف - 1000 تک', 'fixed', 1000, '2026-01-01', '2026-12-31', 200, 45, 1);

-- Display summary message
SELECT 'Pakistani Data Import Complete!' as Status,
       COUNT(*) as Total_Records
FROM (
    SELECT COUNT(*) FROM users
    UNION ALL
    SELECT COUNT(*) FROM services
    UNION ALL
    SELECT COUNT(*) FROM bookings
) as counts;
