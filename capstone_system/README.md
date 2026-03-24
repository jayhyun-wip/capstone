# BayanTap – Water Utility Billing & Receipt Management System
**Marcos Village Water District | Treasurer Portal**

---

## 📋 SYSTEM OVERVIEW

BayanTap is a PHP + MySQL web application for managing water utility billing,
resident accounts, and official receipt generation for Marcos Village Water District.

---

## 🗂 FILE STRUCTURE

```
bayantap/
│
├── config.php          — Database credentials & helper functions
├── auth.php            — Session/authentication guard (include in protected pages)
├── login.php           — Login page
├── logout.php          — Session destroy & redirect
├── index.php           — Main dashboard (Treasurer Portal)
├── print_receipt.php   — Printable receipt page (opens in new tab)
├── dashboard.js        — Frontend interactivity (search debounce, animations)
├── style.css           — Complete stylesheet
│
└── schema.sql          — Database schema + seed data
```

---

## ⚙️ INSTALLATION GUIDE

### 1. Requirements
- PHP 8.0 or higher
- MySQL 5.7+ / MariaDB 10.3+
- A local server stack: XAMPP, WAMP, Laragon, or MAMP

### 2. Set Up the Database

Open **phpMyAdmin** (or your MySQL client) and run `schema.sql`:

```sql
-- Option A: phpMyAdmin → Import → select schema.sql
-- Option B: Command line:
mysql -u root -p < schema.sql
```

This creates the `bayantap_db` database with all tables and sample data.

### 3. Configure Database Connection

Edit `config.php` and update your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Your MySQL username
define('DB_PASS', '');           // Your MySQL password
define('DB_NAME', 'bayantap_db');
```

### 4. Deploy Files

Copy all files into your web server's document root:

- **XAMPP**: `C:/xampp/htdocs/bayantap/`
- **WAMP**: `C:/wamp64/www/bayantap/`
- **Laragon**: `C:/laragon/www/bayantap/`

### 5. Access the System

Open your browser and navigate to:
```
http://localhost/bayantap/
```

---

## 🔐 DEFAULT LOGIN CREDENTIALS

| Username    | Password   | Role      |
|-------------|------------|-----------|
| `treasurer` | `password` | Treasurer |
| `admin`     | `password` | Admin     |

> **Security Note**: Change these passwords immediately in production by running:
> ```php
> echo password_hash('your_new_password', PASSWORD_DEFAULT);
> ```
> Then update the `users` table with the generated hash.

---

## 🗄 DATABASE SCHEMA

### `users`
Stores treasurer/admin login credentials.

### `households`
Each water meter connection — Block and Lot number, address, active status.

### `residents`
Primary contact per household (name, phone, email).

### `bills`
Monthly water bills per household:
- `prev_reading` / `curr_reading` — meter readings in m³
- `consumption` — computed column (curr - prev)
- `amount` — billed amount in PHP
- `status` — `paid` | `unpaid` | `overdue`
- `due_date` — payment deadline

### `payments`
Recorded payments with official receipt numbers (format: MV-YYYY-XXXX).

---

## ✨ FEATURES

| Feature | Description |
|---|---|
| 📊 Dashboard Cards | Total Households, Paid, Pending, Overdue counts with trend indicators |
| 🔍 Search | Real-time search by resident name or household number |
| 🗂 Filters | Filter by status (All / Paid / Unpaid / Overdue) and billing month |
| 📋 Bills Table | Paginated table with color-coded status badges |
| 🧾 Receipt Preview | Inline receipt panel with all billing details |
| 🖨 Print Receipt | Opens print-ready receipt in new tab, auto-triggers print dialog |
| 🔒 Auth | Secure login/logout with session timeout (1 hour) |
| 📱 Responsive | Desktop-first but fully functional on tablets and mobile |

---

## 🎨 DESIGN DECISIONS

- **Color Palette**: Deep navy → sky blue → teal gradient — clean utility-government aesthetic
- **Typography**: DM Serif Display (headings/brand) + DM Sans (body) — modern yet trustworthy
- **Cards**: Glassmorphism on hero gradient; white cards with soft shadows below
- **Animations**: Counter animation on load, staggered row reveals, hover transforms
- **Receipt**: Mimics a real thermal/official receipt with dotted rules and signature lines

---

## 🔧 CUSTOMIZATION

### Change items per page:
```php
// config.php
define('ITEMS_PER_PAGE', 10); // Change to 20, 25, etc.
```

### Change treasurer name:
```php
// config.php
define('TREASURER', 'Carmen Santos');
```

### Adjust session timeout:
```php
// config.php
define('SESSION_TIMEOUT', 3600); // seconds (3600 = 1 hour)
```

---

## 📝 NOTES

- Password hashing uses PHP's `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- All SQL queries use PDO prepared statements to prevent SQL injection
- HTML output is sanitized with `htmlspecialchars()` via the `esc()` helper
- The `consumption` column in `bills` is a MySQL **generated/computed column**
  (automatically calculated as `curr_reading - prev_reading`)

---

## 📞 SUPPORT

BayanTap Water Utility System v1.0  
For inquiries: Barangay Hall, Marcos Village, Dagupan City, Pangasinan
