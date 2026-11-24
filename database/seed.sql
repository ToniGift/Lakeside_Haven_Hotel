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

-- More room types (ids 5-8 on a fresh import), their rooms and extra amenities
INSERT INTO amenities (name, icon) VALUES
('Balcony','balcony'),('Lake view','view'),('Mini bar','bar'),('Work desk','desk');

INSERT INTO room_types (name, slug, description, price_per_night, capacity, beds, size_m2) VALUES
('Twin Room', 'twin-room', 'Two comfortable single beds, ideal for friends or colleagues. Private bathroom and a work desk.', 78.00, 2, '2 single beds', 20),
('Deluxe Double', 'deluxe-double', 'Our largest double room, with a balcony and lake view, a seating corner and a rain shower.', 115.00, 2, '1 king bed', 28),
('Triple Room', 'triple-room', 'Space for three guests with a double bed plus a single bed. Private bathroom and air conditioning.', 120.00, 3, '1 double + 1 single bed', 26),
('Executive Suite', 'executive-suite', 'A top-floor suite with a separate living room, workspace, king bed and panoramic views over the city.', 195.00, 3, '1 king bed + sofa bed', 45);

INSERT INTO rooms (room_type_id, room_number)
SELECT id, '204' FROM room_types WHERE slug='twin-room' UNION ALL
SELECT id, '205' FROM room_types WHERE slug='twin-room' UNION ALL
SELECT id, '206' FROM room_types WHERE slug='twin-room' UNION ALL
SELECT id, '401' FROM room_types WHERE slug='deluxe-double' UNION ALL
SELECT id, '402' FROM room_types WHERE slug='deluxe-double' UNION ALL
SELECT id, '207' FROM room_types WHERE slug='triple-room' UNION ALL
SELECT id, '208' FROM room_types WHERE slug='triple-room' UNION ALL
SELECT id, '501' FROM room_types WHERE slug='executive-suite';

-- Amenities: 1 Wi-Fi, 2 Private bathroom, 3 Air conditioning, 4 Breakfast, 5 TV, 6 Lockers, 7 Balcony, 8 Lake view, 9 Mini bar, 10 Work desk
INSERT INTO room_type_amenities (room_type_id, amenity_id)
SELECT rt.id, a.id FROM room_types rt JOIN amenities a ON
  (rt.slug='twin-room'        AND a.name IN ('Free Wi-Fi','Private bathroom','TV','Air conditioning','Work desk')) OR
  (rt.slug='deluxe-double'    AND a.name IN ('Free Wi-Fi','Private bathroom','Air conditioning','Breakfast included','TV','Balcony','Lake view')) OR
  (rt.slug='triple-room'      AND a.name IN ('Free Wi-Fi','Private bathroom','Air conditioning','TV')) OR
  (rt.slug='executive-suite'  AND a.name IN ('Free Wi-Fi','Private bathroom','Air conditioning','Breakfast included','TV','Mini bar','Work desk'));

-- Three photos for every room type (files in assets/uploads/demo-*.jpg). Looked up by slug, so ids do not matter.
-- Types 1-4 already have their first photo(s) from above; the rest are added here.
INSERT INTO room_images (room_type_id, filename, is_cover, sort_order)
SELECT id, f, c, s FROM room_types JOIN (
  SELECT 'dorm-bed' slug, 'demo-dorm-2.jpg' f, 0 c, 1 s UNION ALL SELECT 'dorm-bed','demo-dorm-3.jpg',0,2 UNION ALL
  SELECT 'private-single','demo-single-2.jpg',0,1 UNION ALL SELECT 'private-single','demo-single-3.jpg',0,2 UNION ALL
  SELECT 'double-room','demo-double-3.jpg',0,2 UNION ALL
  SELECT 'family-suite','demo-suite-2.jpg',0,1 UNION ALL SELECT 'family-suite','demo-suite-3.jpg',0,2 UNION ALL
  SELECT 'twin-room','demo-twin-1.jpg',1,0 UNION ALL SELECT 'twin-room','demo-twin-2.jpg',0,1 UNION ALL SELECT 'twin-room','demo-twin-3.jpg',0,2 UNION ALL
  SELECT 'deluxe-double','demo-deluxe-1.jpg',1,0 UNION ALL SELECT 'deluxe-double','demo-deluxe-2.jpg',0,1 UNION ALL SELECT 'deluxe-double','demo-deluxe-3.jpg',0,2 UNION ALL
  SELECT 'triple-room','demo-triple-1.jpg',1,0 UNION ALL SELECT 'triple-room','demo-triple-2.jpg',0,1 UNION ALL SELECT 'triple-room','demo-triple-3.jpg',0,2 UNION ALL
  SELECT 'executive-suite','demo-exec-1.jpg',1,0 UNION ALL SELECT 'executive-suite','demo-exec-2.jpg',0,1 UNION ALL SELECT 'executive-suite','demo-exec-3.jpg',0,2
) p ON p.slug = room_types.slug;

-- The executive suite photos show a city skyline, so describe the views honestly
UPDATE room_types SET description = 'A top-floor suite with a separate living room, workspace, king bed and panoramic views over the city.' WHERE slug = 'executive-suite';
DELETE x FROM room_type_amenities x JOIN room_types rt ON rt.id = x.room_type_id JOIN amenities a ON a.id = x.amenity_id
 WHERE rt.slug = 'executive-suite' AND a.name = 'Lake view';
