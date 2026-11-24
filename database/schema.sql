-- Hotel / Hostel website - database schema
CREATE DATABASE IF NOT EXISTS hotel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS offers, messages, pages, bookings, room_type_amenities, amenities,
                     room_images, rooms, room_types, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE room_types (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL,
  slug            VARCHAR(120) NOT NULL UNIQUE,
  description     TEXT,
  price_per_night DECIMAL(8,2) NOT NULL,
  capacity        TINYINT UNSIGNED NOT NULL DEFAULT 2,
  beds            VARCHAR(60) DEFAULT NULL,
  size_m2         SMALLINT UNSIGNED DEFAULT NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rooms (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_type_id INT UNSIGNED NOT NULL,
  room_number  VARCHAR(10) NOT NULL UNIQUE,
  status       ENUM('active','maintenance') NOT NULL DEFAULT 'active',
  FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE room_images (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_type_id INT UNSIGNED NOT NULL,
  filename     VARCHAR(255) NOT NULL,
  is_cover     TINYINT(1) NOT NULL DEFAULT 0,
  sort_order   INT NOT NULL DEFAULT 0,
  FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE amenities (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  icon VARCHAR(40) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE room_type_amenities (
  room_type_id INT UNSIGNED NOT NULL,
  amenity_id   INT UNSIGNED NOT NULL,
  PRIMARY KEY (room_type_id, amenity_id),
  FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE,
  FOREIGN KEY (amenity_id)   REFERENCES amenities(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  room_id     INT UNSIGNED NOT NULL,
  check_in    DATE NOT NULL,
  check_out   DATE NOT NULL,
  guests      TINYINT UNSIGNED NOT NULL DEFAULT 1,   -- total people = adults + children
  adults      TINYINT UNSIGNED NOT NULL DEFAULT 1,
  children    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  total_price DECIMAL(10,2) NOT NULL,
  status      ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  notes       TEXT,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  INDEX idx_room_dates (room_id, check_in, check_out)
) ENGINE=InnoDB;

CREATE TABLE pages (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug    VARCHAR(60) NOT NULL UNIQUE,
  title   VARCHAR(150) NOT NULL,
  content TEXT
) ENGINE=InnoDB;

CREATE TABLE messages (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  message    TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE offers (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title            VARCHAR(150) NOT NULL,
  description      TEXT,
  discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  valid_from       DATE DEFAULT NULL,
  valid_to         DATE DEFAULT NULL,
  is_active        TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
