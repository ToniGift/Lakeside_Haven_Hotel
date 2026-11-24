# Lakeside Haven: hotel / hostel website with CMS

A small hotel website built with plain PHP 8 and MySQL. Visitors can browse rooms,
registered users can book, and an admin manages everything in a CMS.

## Features

**Public**
- Home page with date search, featured rooms and a live special offer
- Rooms list with filters (dates, adults and children, max price) and sorting; live availability and total price
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

---

# Project plans

## Plan 1: Lakeside Haven, hotel / hostel website with CMS (finished)

**Goal:** the website of one small hotel/hostel with its own booking system and a CMS.
Visitors can only look; only registered users can book; an admin manages everything.

**Stack:** plain PHP 8, MySQL (PDO), Bootstrap 5, a little JavaScript. Runs on XAMPP.

**Roles**

| Role | Can do |
|---|---|
| Visitor | Browse home, rooms, gallery, about, contact. See prices and availability. Cannot book. |
| Registered user | Everything above, plus book a room, "My bookings", cancel before check-in, edit profile. |
| Admin | CMS: rooms, photos, bookings, users, page texts, offers, messages. |

**Phases**

| Phase | Work | Status |
|---|---|---|
| 1 | Database schema, seed data, config, layout | Done |
| 2 | Register, login, logout, roles | Done |
| 3 | Public pages: home, rooms, room detail, gallery, about, contact | Done |
| 4 | Admin CMS: dashboard, room types, rooms, photo upload | Done |
| 5 | Availability search and booking logic (transaction, no double booking) | Done |
| 6 | Admin bookings (confirm / cancel) and users list | Done |
| 7 | Admin editors for pages, offers and contact messages | Done |
| 8 | Hardening, profile page, 404 page, README | Done |
| 9 | Photos: lake hero, room photos, gallery lightbox, About photo | Done |
| 10 | Responsive layout from 320 px to 2560 px | Done |
| 11 | More room types (8 types, 19 rooms), three photos per room | Done |

**Core booking rule:** two stays overlap when `existing.check_in < new.check_out AND existing.check_out > new.check_in`.
A stay may start on the day another one ends.

---

## Plan 2: CRM (Customer Relationship Management), planned

**Goal:** a small web app for a company to keep track of its customers, the projects it does for them,
and the files, comments and people that belong to each project.

**Stack:** plain PHP 8, MySQL (PDO), Bootstrap 5. Separate folder (`crm`) and database (`crm_db`),
independent of the hotel site.

### Grading levels (each level includes the one before it)

| Level | Name | What it adds |
|---|---|---|
| 3.0 | dst | Customers, Projects, Files (one invoice per project). Full CRUD. |
| 4.0 | db | Comments on projects. Several files per project (pdf, jpg, png). |
| 5.0 | bdb | Employees, with several employees assigned to each project. |

The plan is to build all three levels, one after the other, and tag each finished level in git
(`v3.0-dst`, `v4.0-db`, `v5.0-bdb`) so every level can be checked on its own.

### Level 3.0 (dst): the base

**Tables**

| Table | Fields |
|---|---|
| customers | id, name, contact_person, email, address (full address), phone |
| projects | id, name, created_at, description, income, expenditures, customer_id (foreign key) |
| files | id, project_id (unique), filename |

- Every table has: list all, create, edit, delete, view a single record.
- The **Projects list shows only** `created_at`, `name` and the **customer's e-mail address**
  (the e-mail comes from the customers table with a JOIN). Everything else is on the single-project page.
- `files.project_id` is UNIQUE, so one project has exactly one file (the invoice).
- Deleting a customer who still has projects is refused. Deleting a project deletes its file.

### Level 4.0 (db): comments and many files

**New table**

| Table | Fields |
|---|---|
| comments | id, project_id (foreign key), title, content, created_at |

**Changes**
- `files.project_id` is no longer unique: one project can have many files.
  Allowed types: pdf, jpg, png (checked by content, not only by name), up to 5 MB each.
- One project has many comments. A comment can be added **on the project's own page**
  (form under the project details); the page lists the comments newest first.
- Comments also get list / edit / delete.

### Level 5.0 (bdb): employees

**New tables**

| Table | Fields |
|---|---|
| employees | id, name, surname, address, phone, email, role |
| project_employees | project_id, employee_id (primary key on the pair) |

- Employees get full CRUD (list, create, edit, delete, view).
- One project has many employees, and one employee can work on many projects
  (many-to-many through `project_employees`).
- Employees are assigned and removed on the project page and on the project form.
  The employee page lists the projects they work on.

### Data model (final, level 5.0)

```
customers 1 ----< projects >----< employees      (projects >--< employees via project_employees)
                     |
                     +----< files      (many files per project from level 4.0)
                     +----< comments   (many comments per project from level 4.0)
```

### Build phases

| Phase | Work |
|---|---|
| 1 | Folder, database schema, seed data, config, layout, login |
| 2 | Customers: list, create, edit, delete, view |
| 3 | Projects: CRUD, list with date / name / customer e-mail only. **Tag v3.0-dst after files.** |
| 4 | Files: upload (one per project), view, download, replace, delete |
| 5 | Level 4.0: many files per project, comments on the project page. **Tag v4.0-db.** |
| 6 | Level 5.0: employees CRUD, assign employees to projects. **Tag v5.0-bdb.** |
| 7 | Search and paging, responsive layout, security review, tests, README |

### Rules shared by all levels

- Prepared statements for all queries, escaped output, a CSRF token on every form.
- Login required for every page; passwords stored hashed.
- Uploaded files are stored outside the public pages under random names and served only
  through a script that checks the login.
- Validation on every form (required fields, valid e-mail and phone, money not negative).
- Works from phone to desktop (tables turn into cards on small screens).

### Test checklist

- The Projects list shows exactly three columns: date, name, customer e-mail.
- A customer with projects cannot be deleted.
- Level 3.0: a second file for the same project is refused (or replaces the first).
- Level 4.0: add three files and two comments to one project; both show on the project page.
- Level 5.0: assign two employees to a project and one employee to two projects.
- A visitor who is not logged in is redirected to the login page on every page.
