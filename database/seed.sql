USE hotel_db;

-- Test accounts:  admin@hotel.local / Admin123!   |   guest@hotel.local / User1234!
INSERT INTO users (name, email, password_hash, role) VALUES
('Site Admin', 'admin@hotel.local', '$2y$10$PX0s9wzxeCpHO2MaB28WAeCSEaPVA.NLxzd9OTM8kmaj79y7T3QxC', 'admin'),
('Test Guest', 'guest@hotel.local', '$2y$10$DRstrpx0TPqkWv8hvIwxGebtlB2FRTVJ1VdcMzi6mpJRzMU437Qci', 'user');

INSERT INTO room_types (name, slug, description, price_per_night, capacity, beds, size_m2) VALUES
('Dorm Bed (6-bed mixed)', 'dorm-bed', 'A bed in a bright shared dorm with lockers, reading lights and USB sockets at every bed.', 25.00, 1, '1 bunk bed', 22),
('Private Single', 'private-single', 'A quiet private room for one traveller with a desk and private bathroom.', 55.00, 1, '1 single bed', 12),
('Double Room', 'double-room', 'A comfortable double room with a private bathroom and city view.', 85.00, 2, '1 double bed', 18),
('Family Suite', 'family-suite', 'A spacious suite with a small living area, ideal for families or small groups.', 140.00, 4, '1 double + 2 single beds', 32);

INSERT INTO rooms (room_type_id, room_number) VALUES
(1,'D1'),(1,'D2'),(1,'D3'),(1,'D4'),
(2,'101'),(2,'102'),
(3,'201'),(3,'202'),(3,'203'),
(4,'301'),(4,'302');

INSERT INTO amenities (name, icon) VALUES
('Free Wi-Fi','wifi'),('Private bathroom','bath'),('Air conditioning','snow'),
('Breakfast included','cup'),('TV','tv'),('Lockers','lock');

INSERT INTO room_type_amenities (room_type_id, amenity_id) VALUES
(1,1),(1,6),
(2,1),(2,2),(2,5),
(3,1),(3,2),(3,3),(3,5),(3,4),
(4,1),(4,2),(4,3),(4,5),(4,4);

INSERT INTO pages (slug, title, content) VALUES
('home', 'Welcome', 'A friendly small hotel and hostel in the heart of the city. Clean rooms, fair prices and a warm welcome.'),
('about', 'About Us', 'We are a family-run hotel and hostel that opened in 2015. Whether you are travelling solo, as a couple or with the family, we have a room for you.');

INSERT INTO offers (title, description, discount_percent, valid_from, valid_to) VALUES
('Weekend Escape', 'Book any Double Room or Suite and save on your stay.', 10, NULL, NULL);

-- Demo photos (files live in assets/uploads/demo-*.jpg). Room types: 1 Dorm, 2 Single, 3 Double, 4 Suite.
INSERT INTO room_images (room_type_id, filename, is_cover, sort_order) VALUES
(1, 'demo-dorm-1.jpg',   1, 0),
(2, 'demo-single-1.jpg', 1, 0),
(3, 'demo-double-1.jpg', 1, 0),
(3, 'demo-double-2.jpg', 0, 1),
(4, 'demo-suite-1.jpg',  1, 0);
