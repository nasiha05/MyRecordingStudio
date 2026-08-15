# MyRecordingStudio

A dynamic web application built with **Object-Oriented PHP** and **MySQL**
for ISIT307 Assignment 2. Clients can browse locations and book recording
studio sessions; Administrators manage locations, bookings, and clients.

---
## 1. Requirements

- **XAMPP** (Apache + MySQL/MariaDB + PHP 7.4 or higher) — https://www.apachefriends.org/
- **VS Code** (or any editor) — optional, only needed for viewing/editing code
- A web browser (Chrome, Edge, Firefox)

---
## 2. Setup Steps (XAMPP)

1. **Install XAMPP** if you don't already have it, and open the **XAMPP
   Control Panel**.
2. **Copy the project folder** `MyRecordingStudio` into your XAMPP
   `htdocs` folder, so the path looks like:
   - Windows: `C:\xampp\htdocs\MyRecordingStudio`
   - macOS: `/Applications/XAMPP/htdocs/MyRecordingStudio`
   - Linux: `/opt/lampp/htdocs/MyRecordingStudio`
3. In the XAMPP Control Panel, click **Start** next to **Apache** and
   **MySQL**. Both rows should turn green.
4. **Create the database** — open your browser and go to
   `http://localhost/phpmyadmin`
   - Click the **Import** tab at the top.
   - Click **Choose File** and select `database/database.sql` from the
     project folder.
   - Scroll down and click **Go**.
   - You should see a success message and a new database called
     `myrecordingstudio` in the left sidebar with 3 tables: `users`,
     `locations`, `bookings`. It already contains one Administrator
     account, one sample Client account, and 4 sample locations so you
     can log in and test immediately (see credentials below).
5. **Check the DB connection settings.** Open
   `config/config.php` in VS Code. The defaults already match a
   standard XAMPP install (`DB_USER = 'root'`, `DB_PASS = ''`). Only
   change these if your MySQL uses a different username/password.
6. **Run the site** — open your browser and go to:
   `http://localhost/MyRecordingStudio/`

That's it — no command line / composer / npm install needed. Everything
runs through Apache + PHP + MySQL exactly like a normal XAMPP project.

### Demo accounts (already in the database)
| Role  | Email | Password |
|-------|-------|----------|
| Administrator | admin@myrecordingstudio.com | Admin123! |
| Client 1 | client1@example.com | Client123! |
| Client 2 | client2@example.com | Client123! |
| Client 3 | client3@example.com | Client123! |

You can also click **Register** to create new accounts of either type.

---
## 3. Opening the project in VS Code

1. Open VS Code.
2. **File → Open Folder...** and select the `MyRecordingStudio` folder
   (wherever you copied it, e.g. inside `htdocs`).
3. Recommended (optional) extensions for a nicer experience:
   - **PHP Intelephense** (bmewburn.vscode-intelephense-client) — PHP
     autocomplete/error-checking.
   - **MySQL** (cweijan.vscode-mysql-client2) — browse the database from
     inside VS Code.
4. You do **not** run the project from VS Code's terminal — it must run
   through Apache (via XAMPP) because it's a normal PHP web app, not a
   Node/CLI app. Just keep XAMPP's Apache + MySQL running in the
   background while you edit files in VS Code, then refresh your browser
   at `http://localhost/MyRecordingStudio/` to see your changes (PHP
   files don't need a build step — just save and refresh).

---
## 4. Project / Folder Structure

```
MyRecordingStudio/
├── admin/                  Administrator-only pages
│   ├── dashboard.php
│   ├── locations.php       List all / available / fully-booked locations
│   ├── location_form.php   Create / edit a location
│   ├── bookings.php        List all bookings, cancel
│   ├── booking_form.php    Create / modify a booking on behalf of a client
│   ├── clients.php         List all clients / clients currently in-studio
│   └── search_locations.php
├── auth/                   Shared authentication pages
│   ├── register.php
│   ├── login.php
│   └── logout.php
├── client/                 Client-only pages
│   ├── dashboard.php
│   ├── book.php            Book a studio session
│   ├── booking_confirmation.php
│   ├── booking_edit.php    Modify a booking
│   ├── my_bookings.php     All / upcoming / completed bookings
│   ├── available_locations.php
│   └── search_locations.php
├── classes/                OOP PHP classes (the application's "model" layer)
│   ├── Database.php        PDO singleton connection
│   ├── User.php             Base class: register / login / logout / session guards
│   ├── Client.php           extends User — client-scoped booking actions
│   ├── Administrator.php    extends User — admin-only management actions
│   ├── Location.php         Location CRUD, search, availability queries
│   ├── Booking.php          Booking CRUD, business-rule validation, status logic
│   └── Validator.php        Static input-validation helpers
├── config/
│   └── config.php          DB credentials + business-rule constants
├── includes/
│   ├── bootstrap.php       Loaded by every page: config + autoload + helpers
│   ├── functions.php       Flash messages, formatting helpers
│   ├── header.php          Shared page header / navigation bar
│   └── footer.php          Shared page footer
├── assets/
│   └── css/style.css       All site styling (no external CDN dependency)
├── database/
│   └── database.sql        Full schema + sample users/locations (import this in phpMyAdmin)
├── index.php                Public landing page
└── README.md                 This file
```

---
## 5. Class Overview (OOP design)

| Class | Responsibility |
|---|---|
| `Database` | Singleton PDO wrapper — one shared DB connection for the whole app. |
| `Validator` | Static helper methods for server-side input validation (required, email, phone, date, time, positive numbers, string cleaning). |
| `User` | Base class shared by both user types. Handles registration, login (password hashing/verification), logout, and static session guards (`requireLogin()`, `requireRole()`). |
| `Client` *(extends `User`)* | Represents a logged-in client. Wraps booking a studio, modifying/cancelling own bookings, and viewing completed/upcoming sessions and available locations. |
| `Administrator` *(extends `User`)* | Represents a logged-in administrator. Wraps location management (create/edit), booking management on behalf of any client, and client-listing reports. |
| `Location` | All location data access: create, update, get by ID, list all, partial-match search, "available now" / "fully booked now" queries, and studio-availability counting used by `Booking`. |
| `Booking` | Core business logic: validates the 10am–10pm operating window and 1–12 hour duration rule, calculates total cost, checks studio availability before confirming, enforces the "cannot modify/cancel after start time" rule for clients, and provides all the list views (by client, completed, upcoming, all, currently-active). |

---
## 6. Database Design

**users** — stores both Administrators and Clients (`user_type` column
distinguishes them): `user_id`, `name`, `phone`, `email`,
`password_hash`, `user_type`, `created_at`.

**locations** — each recording-studio location: `location_id`,
`description`, `num_studios`, `cost_per_hour`, `created_at`.

**bookings** — one row per booked session: `booking_id`, `client_id`
(FK → users), `location_id` (FK → locations), `booking_date`,
`start_time`, `duration_hours`, `end_time`, `total_cost`, `status`
(`active`/`cancelled`), `created_at`. A booking's *displayed* status
(Upcoming / In Progress / Completed / Cancelled) is computed live by
comparing the booking's date/time against the current time — this
means a session automatically shows as "Completed" the moment it ends,
with no extra script or cron job required.

---
## 7. Business Rules Implemented

- Studios operate daily **10:00 AM – 10:00 PM**; sessions run **1–12
  hours** and cannot start before opening or extend past closing.
- On booking, the system checks that a studio is actually free at that
  location for the requested date/time range (won't overbook past
  `num_studios`).
- Total cost = duration (hours) × location's cost-per-hour, shown
  immediately in a confirmation note.
- Clients may modify/cancel a booking **only before its start time**;
  administrators may modify/cancel any booking on a client's behalf at
  any time.
- All form input is validated server-side in PHP (required fields,
  email format, phone format, positive numbers, valid dates/times)
  regardless of any client-side HTML5 validation.
- No payment functionality is included, per the assignment spec.
