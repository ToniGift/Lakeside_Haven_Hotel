<?php
// Site configuration
define('SITE_NAME', 'Lakeside Haven');
define('BASE_URL', '/hotel');          // URL path of the project under Apache

// Database (XAMPP defaults)
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/');
define('CURRENCY', '€');

date_default_timezone_set('Europe/Warsaw');
