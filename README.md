# Lakeside Haven: hotel / hostel website with CMS

A small hotel website built with plain PHP 8 and MySQL. Visitors can browse rooms,
registered users can book, and an admin manages everything in a CMS.

## Features

**Public**
- Home page with date search, featured rooms and a live special offer
- Rooms list with filters (dates, guests, max price) and sorting; live availability and total price
- Room page with photo gallery, amenities and an availability checker
- Gallery, About (editable) and Contact (messages saved to the CMS)

**Registered users**
- Register, log in, log out, edit profile, change password
- Book a room (visitors who are not logged in can only look; "Book" sends them to login)
- "My bookings": upcoming and past bookings, cancel before check-in, printable confirmation

**Admin CMS (`/admin`)**
- Dashboard: counts, confirmed revenue, upcoming check-ins
- Room types: add, edit, delete, amenities, visibility
- Individual rooms: add, maintenance mode, remove
- Photos: multi-upload, cover photo, delete
- Bookings: filter, view, confirm, cancel
- Users list, Pages editor (Home / About), Offers (with date window), Contact messages

## Setup (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\hotel`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Import the database (phpMyAdmin, or the command line):
   ```
   C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql
   C:\xampp\mysql\bin\mysql.exe -u root < database\seed.sql
   ```
4. Open <http://localhost/hotel/>.

If you use a different folder name or port, change `BASE_URL` (and the database
settings if needed) in `config/config.php`.

## Test accounts (from `database/seed.sql`)

| Role  | Email             | Password    |
|-------|-------------------|-------------|
| Admin | admin@hotel.local | Admin123!   |
| User  | guest@hotel.local | User1234!   |

Change these before putting the site anywhere public.

## How booking works

- A room can never be booked twice for overlapping nights. A stay may start on the day
  another one ends (check-out day is free).
- Booking runs in a database transaction that locks the rooms of that type while it
  re-checks availability, so two people booking at the same moment cannot get the same room.
- Prices are calculated on the server, with the highest live offer applied.
- New bookings are "pending" until the admin confirms them. Payment is at the hotel.

## Security notes

- Passwords hashed with `password_hash()`; sessions regenerated on login; cookie is HttpOnly + SameSite
- PDO prepared statements everywhere; all output escaped
- CSRF token on every form that changes data
- Role checks on every admin and user page
- Uploads: type and size checked, random file names, scripts blocked in the uploads folder
- `config/`, `includes/` and `database/` are blocked from direct web access (Apache `.htaccess`)

## Folder structure

```
config/     settings            includes/   shared code (db, auth, helpers, layout)
admin/      CMS pages           assets/     css, js, uploads
database/   schema + seed SQL   *.php       public pages
```
