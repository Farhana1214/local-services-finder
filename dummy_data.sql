-- Service Finder Application - Dummy Data Population

USE service_finder;

-- 1. INSERT SERVICE CATEGORIES
INSERT INTO service_categories (category_name, description, icon) VALUES
('Plumbing', 'All plumbing services and repairs', 'plumbing.png'),
('Electrical', 'Electrical installation and repair services', 'electrical.png'),
('Cleaning', 'House and office cleaning services', 'cleaning.png'),
('Carpentry', 'Carpentry and woodwork services', 'carpentry.png'),
('Painting', 'House painting and decoration', 'painting.png'),
('HVAC', 'Heating, ventilation, and air conditioning', 'hvac.png'),
('Gardening', 'Landscaping and gardening services', 'gardening.png'),
('Pet Care', 'Pet grooming and care services', 'petcare.png'),
('Appliance Repair', 'Repair services for home appliances', 'appliance.png'),
('Home Maintenance', 'General home maintenance and repair', 'maintenance.png'),
('Tutoring', 'Educational tutoring services', 'tutoring.png'),
('Personal Training', 'Fitness and personal training', 'fitness.png');

-- 2. INSERT CUSTOMERS (Users)
INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code, user_type, is_verified) VALUES
('john_doe', 'john@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q', 'John', 'Doe', '9876543210', '123 Main St', 'Lahore', 'Punjab', '54000', 'customer', 1),
('jane_smith', 'jane@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Jane', 'Smith', '9876543211', '456 Oak Ave', 'Jauharabad', 'Punjab', '35200', 'customer', 1),
('mike_wilson', 'mike@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Mike', 'Wilson', '9876543212', '789 Pine Rd', 'Faisalabad', 'Punjab', '38000', 'customer', 1),
('sarah_jones', 'sarah@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Sarah', 'Jones', '9876543213', '321 Elm St', 'Multan', 'Punjab', '60000', 'customer', 1),
('emma_brown', 'emma@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Emma', 'Brown', '9876543214', '654 Maple Ave', 'Gujranwala', 'Punjab', '52250', 'customer', 1),
('robert_davis', 'robert@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Robert', 'Davis', '9876543215', '987 Cedar Ln', 'Sialkot', 'Punjab', '51310', 'customer', 1),
('lisa_miller', 'lisa@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Lisa', 'Miller', '9876543216', '147 Birch Ct', 'Sargodha', 'Punjab', '40100', 'customer', 1),
('james_taylor', 'james@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'James', 'Taylor', '9876543217', '258 Walnut St', 'Bahawalpur', 'Punjab', '63100', 'customer', 1),
('olivia_anderson', 'olivia@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Olivia', 'Anderson', '9876543218', '369 Spruce Rd', 'Sheikhupura', 'Punjab', '39100', 'customer', 1),
('william_thomas', 'william@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'William', 'Thomas', '9876543219', '741 Oak St', 'Jhang', 'Punjab', '35200', 'customer', 1);

-- 3. INSERT SERVICE PROVIDERS (with user accounts)
INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code, user_type, is_verified) VALUES
('plumber_pro', 'plumber@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Peter', 'Plumber', '8765432100', '100 Service St', 'Lahore', 'Punjab', '54000', 'service_provider', 1),
('electric_expert', 'electric@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Eric', 'Electric', '8765432101', '200 Power Ave', 'Jauharabad', 'Punjab', '35200', 'service_provider', 1),
('clean_sweep', 'cleaning@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Claire', 'Clean', '8765432102', '300 Shine Rd', 'Faisalabad', 'Punjab', '38000', 'service_provider', 1),
('carpenter_king', 'carpenter@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Carl', 'Carpenter', '8765432103', '400 Wood St', 'Multan', 'Punjab', '60000', 'service_provider', 1),
('painter_perfect', 'painter@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Paul', 'Painter', '8765432104', '500 Color Ave', 'Gujranwala', 'Punjab', '52250', 'service_provider', 1),
('hvac_master', 'hvac@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Henry', 'HVAC', '8765432105', '600 Cool St', 'Sialkot', 'Punjab', '51310', 'service_provider', 1),
('garden_guru', 'garden@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'George', 'Gardener', '8765432106', '700 Green Rd', 'Sargodha', 'Punjab', '40100', 'service_provider', 1),
('pet_paradise', 'petcare@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Patricia', 'Pet', '8765432107', '800 Paws Ave', 'Bahawalpur', 'Punjab', '63100', 'service_provider', 1),
('appliance_ace', 'appliance@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Andy', 'Appliance', '8765432108', '900 Tech St', 'Sheikhupura', 'Punjab', '39100', 'service_provider', 1),
('fitness_force', 'fitness@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Frank', 'Fit', '8765432109', '1000 Gym Ave', 'Jhang', 'Punjab', '35200', 'service_provider', 1),
('tutor_plus', 'tutor@example.com', '$2y$10$O5/qxqfBGk.nq6R3hfh/Zev7QP8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8Q8', 'Sarah', 'Tutor', '8765432110', '1100 Learn Ave', 'Lahore', 'Punjab', '54000', 'service_provider', 1);

-- 4. INSERT SERVICE PROVIDER PROFILES
INSERT INTO service_providers (user_id, business_name, business_description, category_id, business_phone, business_email, business_address, business_city, business_state, business_zip, years_of_experience, average_rating, total_reviews, total_jobs_completed, is_verified, is_active, latitude, longitude) VALUES
(11, 'Pro Plumbing Solutions', 'Expert plumbing services available 24/7. We fix leaks, install fixtures, and provide maintenance.', 1, '8765432100', 'plumber@example.com', '100 Service St', 'Lahore', 'Punjab', '54000', 8, 4.8, 150, 500, 1, 1, 31.5497, 74.3436),
(12, 'Elite Electrical Works', 'Licensed electricians specializing in residential and commercial electrical services.', 2, '8765432101', 'electric@example.com', '200 Power Ave', 'Jauharabad', 'Punjab', '35200', 12, 4.9, 200, 680, 1, 1, 30.3093, 72.7849),
(13, 'Sparkle & Shine Cleaning', 'Professional cleaning services for homes and offices with eco-friendly products.', 3, '8765432102', 'cleaning@example.com', '300 Shine Rd', 'Faisalabad', 'Punjab', '38000', 6, 4.7, 120, 400, 1, 1, 31.4181, 72.3456),
(14, 'Master Carpentry Co.', 'Custom woodwork and carpentry for furniture, doors, and renovations.', 4, '8765432103', 'carpenter@example.com', '400 Wood St', 'Multan', 'Punjab', '60000', 15, 4.9, 180, 520, 1, 1, 30.1575, 71.4432),
(15, 'Artisan Painters', 'Interior and exterior painting with premium finishes and quick turnaround.', 5, '8765432104', 'painter@example.com', '500 Color Ave', 'Gujranwala', 'Punjab', '52250', 10, 4.6, 140, 450, 1, 1, 32.1812, 74.1889),
(16, 'Cool Comfort HVAC', 'HVAC installation, repair, and maintenance for all seasons.', 6, '8765432105', 'hvac@example.com', '600 Cool St', 'Sialkot', 'Punjab', '51310', 11, 4.8, 160, 480, 1, 1, 32.4916, 74.5270),
(17, 'Green Gardens Landscaping', 'Professional landscaping, garden design, and maintenance services.', 7, '8765432106', 'garden@example.com', '700 Green Rd', 'Sargodha', 'Punjab', '40100', 9, 4.7, 130, 380, 1, 1, 32.0859, 72.6707),
(18, 'Premium Pet Care', 'Dog grooming, cat care, and pet boarding with professional staff.', 8, '8765432107', 'petcare@example.com', '800 Paws Ave', 'Bahawalpur', 'Punjab', '63100', 7, 4.8, 110, 320, 1, 1, 29.5941, 71.6869),
(19, 'Quick Fix Appliances', 'Fast and reliable repair services for all home appliances.', 9, '8765432108', 'appliance@example.com', '900 Tech St', 'Sheikhupura', 'Punjab', '39100', 8, 4.7, 125, 410, 1, 1, 31.8044, 73.9737),
(20, 'FitZone Training', 'Personal training, fitness coaching, and gym memberships available.', 12, '8765432109', 'fitness@example.com', '1000 Gym Ave', 'Jhang', 'Punjab', '35200', 5, 4.9, 95, 280, 1, 1, 31.2707, 72.3181),
(21, 'Expert Tutoring Academy', 'Professional tutoring services for all subjects and grade levels.', 11, '8765432110', 'tutor@example.com', '1100 Learn Ave', 'Lahore', 'Punjab', '54000', 6, 4.8, 75, 200, 1, 1, 31.5497, 74.3436);

-- 5. INSERT SERVICES
INSERT INTO services (provider_id, service_name, service_description, category_id, price, duration_hours, is_available) VALUES
(1, 'Pipe Repair', 'Quick repair of broken or leaking pipes', 1, 75.00, 1, 1),
(1, 'Bathroom Installation', 'Complete bathroom fixture installation', 1, 250.00, 4, 1),
(1, 'Drain Cleaning', 'Professional drain cleaning and unclogging', 1, 100.00, 1.5, 1),
(2, 'Electrical Wiring', 'Safe electrical wiring installation and repair', 2, 150.00, 2, 1),
(2, 'Circuit Breaker Upgrade', 'Update your home electrical system', 2, 300.00, 3, 1),
(2, 'Light Installation', 'Install ceiling lights and fans', 2, 50.00, 1, 1),
(3, 'House Cleaning', 'Deep clean entire house', 3, 200.00, 4, 1),
(3, 'Office Cleaning', 'Professional office cleaning service', 3, 150.00, 3, 1),
(3, 'Post-Construction Cleaning', 'Clean up after renovation work', 3, 250.00, 5, 1),
(4, 'Custom Furniture Building', 'Build custom wooden furniture', 4, 500.00, 8, 1),
(4, 'Door Installation', 'Installation of doors and frames', 4, 200.00, 3, 1),
(4, 'Cabinet Making', 'Design and build kitchen cabinets', 4, 400.00, 6, 1),
(5, 'Interior Painting', 'Paint interior walls and ceilings', 5, 300.00, 4, 1),
(5, 'Exterior Painting', 'Professional exterior house painting', 5, 400.00, 5, 1),
(5, 'Accent Wall Painting', 'Create stunning accent walls', 5, 150.00, 2, 1),
(6, 'AC Repair', 'Air conditioning repair and service', 6, 120.00, 1.5, 1),
(6, 'Furnace Installation', 'New furnace installation', 6, 1200.00, 6, 1),
(6, 'HVAC Maintenance', 'Regular HVAC system maintenance', 6, 80.00, 1, 1),
(7, 'Garden Design', 'Professional garden and landscape design', 7, 300.00, 3, 1),
(7, 'Lawn Maintenance', 'Regular lawn mowing and care', 7, 50.00, 1.5, 1),
(7, 'Tree Trimming', 'Professional tree and hedge trimming', 7, 150.00, 2, 1),
(8, 'Dog Grooming', 'Complete dog grooming service', 8, 80.00, 2, 1),
(8, 'Cat Grooming', 'Cat bathing and grooming', 8, 60.00, 1.5, 1),
(8, 'Pet Boarding', 'Safe pet boarding and care', 8, 40.00, 8, 1),
(9, 'Refrigerator Repair', 'Fix refrigerator issues and maintenance', 9, 100.00, 1, 1),
(9, 'Washing Machine Repair', 'Repair all types of washing machines', 9, 90.00, 1, 1),
(9, 'Dishwasher Service', 'Dishwasher repair and installation', 9, 120.00, 1.5, 1),
(10, 'Personal Training Session', 'One-on-one fitness training', 12, 50.00, 1, 1),
(10, 'Group Fitness Classes', 'Join group fitness classes', 12, 30.00, 1, 1),
(10, 'Nutrition Consultation', 'Get personalized nutrition advice', 12, 75.00, 1, 1),
(11, 'Math Tutoring', 'Expert mathematics tutoring for all levels', 11, 40.00, 1, 1),
(11, 'English Literature', 'English language and literature assistance', 11, 40.00, 1, 1),
(11, 'Science Tutoring', 'Physics, Chemistry, and Biology tutoring', 11, 45.00, 1, 1),
(11, 'Urdu Language', 'Fluent Urdu language and writing lessons', 11, 35.00, 1, 1),
(11, 'Test Preparation', 'SAT, GRE, and entrance exam prep courses', 11, 60.00, 2, 1);

-- 6. INSERT BOOKINGS
INSERT INTO bookings (user_id, service_id, provider_id, booking_date, service_date, service_time, location, service_notes, status, total_amount, discount_amount, final_amount, payment_method, payment_status) VALUES
(1, 1, 1, '2024-01-15 10:30:00', '2024-01-20', '10:00:00', '123 Main St, Lahore', 'Fix leaking kitchen sink', 'completed', 75.00, 0, 75.00, 'card', 'completed'),
(2, 3, 1, '2024-01-18 14:00:00', '2024-01-25', '14:00:00', '456 Oak Ave, Jauharabad', 'Drain backing up', 'completed', 100.00, 10, 90.00, 'upi', 'completed'),
(3, 5, 2, '2024-01-20 09:00:00', '2024-01-28', '09:00:00', '789 Pine Rd, Faisalabad', 'Upgrade electrical panel', 'completed', 300.00, 0, 300.00, 'card', 'completed'),
(4, 7, 3, '2024-02-01 11:00:00', '2024-02-05', '08:00:00', '321 Elm St, Multan', 'Deep clean entire 3 bedroom house', 'completed', 200.00, 20, 180.00, 'card', 'completed'),
(5, 10, 4, '2024-02-05 13:30:00', '2024-02-15', '10:00:00', '654 Maple Ave, Gujranwala', 'Build custom dining table', 'in_progress', 500.00, 0, 500.00, 'card', 'completed'),
(6, 13, 5, '2024-02-10 15:00:00', '2024-02-20', '09:00:00', '987 Cedar Ln, Sialkot', 'Paint living room and bedroom', 'pending', 300.00, 0, 300.00, 'upi', 'pending'),
(7, 16, 6, '2024-02-12 10:30:00', '2024-02-22', '14:00:00', '147 Birch Ct, Sargodha', 'AC unit repair and check', 'completed', 120.00, 0, 120.00, 'card', 'completed'),
(8, 19, 7, '2024-02-15 12:00:00', '2024-02-25', '11:00:00', '258 Walnut St, Bahawalpur', 'Design and layout backyard', 'confirmed', 300.00, 30, 270.00, 'card', 'completed'),
(9, 22, 8, '2024-02-18 16:00:00', '2024-02-28', '10:00:00', '369 Spruce Rd, Sheikhupura', 'Dog grooming and bath', 'completed', 80.00, 0, 80.00, 'card', 'completed'),
(10, 25, 9, '2024-02-20 09:30:00', '2024-03-02', '10:00:00', '741 Oak St, Jhang', 'Refrigerator not cooling', 'confirmed', 100.00, 0, 100.00, 'card', 'pending'),
(1, 28, 10, '2024-02-22 14:00:00', '2024-03-05', '18:00:00', '123 Main St, Lahore', 'Personal training session', 'confirmed', 50.00, 5, 45.00, 'upi', 'completed'),
(2, 2, 1, '2024-03-01 10:00:00', '2024-03-10', '09:00:00', '456 Oak Ave, Jauharabad', 'Install new bathroom fixtures', 'pending', 250.00, 0, 250.00, 'card', 'pending'),
(3, 4, 2, '2024-03-05 13:00:00', '2024-03-15', '10:00:00', '789 Pine Rd, Faisalabad', 'Install new light fixtures', 'pending', 50.00, 0, 50.00, 'card', 'pending'),
(4, 8, 3, '2024-03-08 11:30:00', '2024-03-18', '08:00:00', '321 Elm St, Multan', 'Commercial office cleaning', 'pending', 150.00, 15, 135.00, 'upi', 'pending'),
(5, 11, 4, '2024-03-10 14:30:00', '2024-03-20', '09:00:00', '654 Maple Ave, Gujranwala', 'Frame and door installation', 'pending', 200.00, 0, 200.00, 'card', 'pending'),
(1, 31, 11, '2024-03-15 10:00:00', '2024-03-25', '14:00:00', '123 Main St, Lahore', 'Mathematics tutoring sessions', 'completed', 40.00, 0, 40.00, 'card', 'completed'),
(2, 32, 11, '2024-03-18 15:30:00', '2024-03-28', '16:00:00', '456 Oak Ave, Jauharabad', 'English literature lessons', 'completed', 40.00, 4, 36.00, 'upi', 'completed');

-- 7. INSERT REVIEWS
INSERT INTO reviews (booking_id, user_id, provider_id, rating, review_title, review_text, cleanliness_rating, professionalism_rating, punctuality_rating, value_for_money_rating, helpful_count, is_verified) VALUES
(1, 1, 1, 5, 'Excellent Plumbing Work', 'Peter fixed my leaking sink quickly and professionally. Highly recommended!', 5, 5, 5, 5, 15, 1),
(2, 2, 1, 5, 'Great Service', 'Very responsive and the drain cleaning was done efficiently.', 5, 5, 5, 5, 12, 1),
(3, 3, 2, 5, 'Professional Electrician', 'Completed the electrical upgrade safely and on time. Very knowledgeable!', 5, 5, 5, 5, 18, 1),
(4, 4, 3, 4, 'Good Cleaning Service', 'The house is sparkling clean after their work. Minor issues with some areas.', 4, 5, 4, 4, 8, 1),
(5, 5, 4, 5, 'Master Craftsman', 'Built exactly what I wanted. The quality is outstanding!', 5, 5, 5, 5, 20, 1),
(7, 7, 6, 5, 'Efficient AC Repair', 'Fixed my AC unit quickly and explained everything clearly.', 5, 5, 5, 5, 14, 1),
(9, 9, 8, 5, 'Happy with Pet Grooming', 'My dog looks amazing! Staff is very caring and professional.', 5, 5, 5, 4, 11, 1),
(16, 1, 11, 5, 'Excellent Tutor', 'Sarah is an amazing tutor! My math grades improved significantly.', 5, 5, 5, 5, 10, 1),
(17, 2, 11, 4, 'Very Helpful', 'Great English lessons. Well-structured and very helpful for exams.', 4, 5, 4, 4, 8, 1);

-- 8. INSERT PAYMENTS
INSERT INTO payments (booking_id, user_id, provider_id, amount, payment_method, transaction_id, payment_status, notes) VALUES
(1, 1, 1, 75.00, 'card', 'TXN001', 'completed', 'Payment successful'),
(2, 2, 1, 90.00, 'upi', 'TXN002', 'completed', '10% discount applied'),
(3, 3, 2, 300.00, 'card', 'TXN003', 'completed', 'Payment successful'),
(4, 4, 3, 180.00, 'card', 'TXN004', 'completed', '10% discount applied'),
(5, 5, 4, 500.00, 'card', 'TXN005', 'completed', 'Payment successful'),
(7, 7, 6, 120.00, 'card', 'TXN007', 'completed', 'Payment successful'),
(9, 9, 8, 80.00, 'card', 'TXN009', 'completed', 'Payment successful'),
(11, 1, 10, 45.00, 'upi', 'TXN011', 'completed', '10% discount applied'),
(12, 2, 1, 250.00, 'card', 'TXN012', 'pending', 'Awaiting completion'),
(13, 3, 2, 50.00, 'card', 'TXN013', 'pending', 'Scheduled for service'),
(14, 4, 3, 135.00, 'upi', 'TXN014', 'pending', '10% discount applied'),
(15, 5, 4, 200.00, 'card', 'TXN015', 'pending', 'Awaiting service'),
(16, 1, 11, 40.00, 'card', 'TXN016', 'completed', 'Payment successful'),
(17, 2, 11, 36.00, 'upi', 'TXN017', 'completed', '10% discount applied');

-- 9. INSERT FAVORITES
INSERT INTO favorites (user_id, provider_id) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 3),
(3, 2),
(3, 4),
(4, 3),
(4, 5),
(5, 4),
(5, 6);

-- 10. INSERT PROMO CODES
INSERT INTO promo_codes (code, description, discount_type, discount_value, max_uses, current_uses, valid_from, valid_until, is_active) VALUES
('SAVE10', '10% discount on all services', 'percentage', 10.00, 100, 8, '2024-01-01', '2024-12-31', 1),
('SAVE20', '20% discount on services over $100', 'percentage', 20.00, 50, 5, '2024-02-01', '2024-12-31', 1),
('FIRST50', '$50 off on first booking', 'fixed', 50.00, 500, 45, '2024-01-01', '2024-12-31', 1),
('SUMMER25', '25% summer special discount', 'percentage', 25.00, 75, 12, '2024-06-01', '2024-08-31', 0),
('WELCOME5', '$5 welcome bonus', 'fixed', 5.00, 1000, 234, '2024-01-01', '2024-12-31', 1);

-- Display summary of inserted data
SELECT 'Users Created' AS DataType, COUNT(*) AS Count FROM users
UNION ALL
SELECT 'Service Categories', COUNT(*) FROM service_categories
UNION ALL
SELECT 'Service Providers', COUNT(*) FROM service_providers
UNION ALL
SELECT 'Services', COUNT(*) FROM services
UNION ALL
SELECT 'Bookings', COUNT(*) FROM bookings
UNION ALL
SELECT 'Reviews', COUNT(*) FROM reviews
UNION ALL
SELECT 'Payments', COUNT(*) FROM payments
UNION ALL
SELECT 'Favorites', COUNT(*) FROM favorites
UNION ALL
SELECT 'Promo Codes', COUNT(*) FROM promo_codes;
