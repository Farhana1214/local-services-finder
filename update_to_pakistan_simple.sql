-- Simple Pakistan Data Update
USE service_finder;

-- Update cities in existing users (customers)
UPDATE users SET city = 'Lahore', state = 'Punjab', zip_code = '54000' WHERE username = 'john_doe';
UPDATE users SET city = 'Islamabad', state = 'ICT', zip_code = '44000' WHERE username = 'jane_smith';
UPDATE users SET city = 'Karachi', state = 'Sindh', zip_code = '75500' WHERE username = 'mike_wilson';
UPDATE users SET city = 'Rawalpindi', state = 'Punjab', zip_code = '46000' WHERE username = 'sarah_jones';
UPDATE users SET city = 'Faisalabad', state = 'Punjab', zip_code = '38000' WHERE username = 'emma_brown';
UPDATE users SET city = 'Multan', state = 'Punjab', zip_code = '60000' WHERE username = 'robert_davis';
UPDATE users SET city = 'Peshawar', state = 'KP', zip_code = '25000' WHERE username = 'lisa_miller';
UPDATE users SET city = 'Khushab', state = 'Punjab', zip_code = '48700' WHERE username = 'james_taylor';
UPDATE users SET city = 'Sargodha', state = 'Punjab', zip_code = '40100' WHERE username = 'olivia_anderson';
UPDATE users SET city = 'Jauharabad', state = 'Punjab', zip_code = '48200' WHERE username = 'william_thomas';

-- Update phone numbers to Pakistani format
UPDATE users SET phone = '03001234567' WHERE username = 'john_doe';
UPDATE users SET phone = '03002234567' WHERE username = 'jane_smith';
UPDATE users SET phone = '03003234567' WHERE username = 'mike_wilson';
UPDATE users SET phone = '03004234567' WHERE username = 'sarah_jones';
UPDATE users SET phone = '03005234567' WHERE username = 'emma_brown';
UPDATE users SET phone = '03006234567' WHERE username = 'robert_davis';
UPDATE users SET phone = '03007234567' WHERE username = 'lisa_miller';
UPDATE users SET phone = '03008234567' WHERE username = 'james_taylor';
UPDATE users SET phone = '03009234567' WHERE username = 'olivia_anderson';
UPDATE users SET phone = '03010234567' WHERE username = 'william_thomas';

-- Update service provider locations
UPDATE users SET city = 'Lahore', state = 'Punjab', zip_code = '54000', phone = '03011234567' WHERE username = 'plumber_pro';
UPDATE users SET city = 'Islamabad', state = 'ICT', zip_code = '44000', phone = '03012234567' WHERE username = 'electric_expert';
UPDATE users SET city = 'Karachi', state = 'Sindh', zip_code = '75500', phone = '03013234567' WHERE username = 'clean_sweep';
UPDATE users SET city = 'Rawalpindi', state = 'Punjab', zip_code = '46000', phone = '03014234567' WHERE username = 'carpenter_king';
UPDATE users SET city = 'Faisalabad', state = 'Punjab', zip_code = '38000', phone = '03015234567' WHERE username = 'painter_perfect';
UPDATE users SET city = 'Multan', state = 'Punjab', zip_code = '60000', phone = '03016234567' WHERE username = 'hvac_master';
UPDATE users SET city = 'Peshawar', state = 'KP', zip_code = '25000', phone = '03017234567' WHERE username = 'garden_guru';
UPDATE users SET city = 'Khushab', state = 'Punjab', zip_code = '48700', phone = '03018234567' WHERE username = 'pet_paradise';
UPDATE users SET city = 'Sargodha', state = 'Punjab', zip_code = '40100', phone = '03019234567' WHERE username = 'appliance_ace';
UPDATE users SET city = 'Jauharabad', state = 'Punjab', zip_code = '48200', phone = '03020234567' WHERE username = 'fitness_force';

-- Update service provider profiles with Pakistani cities and PKR pricing
UPDATE service_providers sp 
JOIN users u ON sp.user_id = u.user_id 
SET sp.business_city = u.city, 
    sp.business_state = u.state, 
    sp.business_zip = u.zip_code,
    sp.business_phone = u.phone 
WHERE u.user_type = 'service_provider';

-- Update service prices to PKR
UPDATE services SET price = 5000 WHERE service_name = 'Pipe Repair';
UPDATE services SET price = 15000 WHERE service_name = 'Bathroom Installation';
UPDATE services SET price = 7000 WHERE service_name = 'Drain Cleaning';
UPDATE services SET price = 10000 WHERE service_name = 'Electrical Wiring';
UPDATE services SET price = 20000 WHERE service_name = 'Circuit Breaker Upgrade';
UPDATE services SET price = 3000 WHERE service_name = 'Light Installation';
UPDATE services SET price = 15000 WHERE service_name = 'House Cleaning';
UPDATE services SET price = 10000 WHERE service_name = 'Office Cleaning';
UPDATE services SET price = 18000 WHERE service_name = 'Post-Construction Cleaning';
UPDATE services SET price = 35000 WHERE service_name = 'Custom Furniture Building';
UPDATE services SET price = 12000 WHERE service_name = 'Door Installation';
UPDATE services SET price = 25000 WHERE service_name = 'Cabinet Making';
UPDATE services SET price = 20000 WHERE service_name = 'Interior Painting';
UPDATE services SET price = 28000 WHERE service_name = 'Exterior Painting';
UPDATE services SET price = 10000 WHERE service_name = 'Accent Wall Painting';
UPDATE services SET price = 8000 WHERE service_name = 'AC Repair';
UPDATE services SET price = 80000 WHERE service_name = 'Furnace Installation';
UPDATE services SET price = 5000 WHERE service_name = 'HVAC Maintenance';
UPDATE services SET price = 20000 WHERE service_name = 'Garden Design';
UPDATE services SET price = 3500 WHERE service_name = 'Lawn Maintenance';
UPDATE services SET price = 10000 WHERE service_name = 'Tree Trimming';
UPDATE services SET price = 5000 WHERE service_name = 'Dog Grooming';
UPDATE services SET price = 4000 WHERE service_name = 'Cat Grooming';
UPDATE services SET price = 2500 WHERE service_name = 'Pet Boarding';
UPDATE services SET price = 7000 WHERE service_name = 'Refrigerator Repair';
UPDATE services SET price = 6000 WHERE service_name = 'Washing Machine Repair';
UPDATE services SET price = 8000 WHERE service_name = 'Dishwasher Service';
UPDATE services SET price = 3500 WHERE service_name = 'Personal Training Session';
UPDATE services SET price = 2000 WHERE service_name = 'Group Fitness Classes';
UPDATE services SET price = 5000 WHERE service_name = 'Nutrition Consultation';

-- Update promo codes for PKR
UPDATE promo_codes SET code = 'SAVE10', discount_value = 10, discount_type = 'percentage' WHERE code = 'SAVE10' LIMIT 1;
UPDATE promo_codes SET code = 'SAVE20', discount_value = 20, discount_type = 'percentage' WHERE code = 'SAVE20' LIMIT 1;
UPDATE promo_codes SET discount_value = 500, discount_type = 'fixed' WHERE code = 'FIRST50' LIMIT 1;
UPDATE promo_codes SET discount_value = 50, discount_type = 'percentage' WHERE code = 'CLEANING50' LIMIT 1;
UPDATE promo_codes SET discount_value = 1000, discount_type = 'fixed' WHERE code = 'WELCOME1000' LIMIT 1;

SELECT 'Pakistan Data Update Complete!' as Status;
