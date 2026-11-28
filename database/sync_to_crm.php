<?php
// One-time backfill of existing hotel guests and bookings into the CRM.
// Run:  C:\xampp\php\php.exe database\sync_to_crm.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/crm_sync.php';

foreach (db()->query("SELECT id FROM users WHERE role = 'user'")->fetchAll(PDO::FETCH_COLUMN) as $id) crm_sync_user((int)$id);
$ids = db()->query('SELECT id FROM bookings ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
foreach ($ids as $id) crm_sync_booking((int)$id);
echo 'Synced ', count($ids), " bookings.\n";
