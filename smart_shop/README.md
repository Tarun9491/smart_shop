# 🪵 Guru Woodworks / Smart Shop

A modern, production-hardened PHP & MySQL e-commerce application featuring a **customer storefront** and **two independent administrative portals** (Store Admin & Super Admin). Fully containerized and configured for deployment on **Render**.

---

## 🌟 Portals & Responsibilities

| Portal | URL Route | Default Role / Session | Responsibilities |
| :--- | :--- | :--- | :--- |
| **Customer Storefront** | `/` (`index.php`) | `$_SESSION['user_id']` | Browse collection, 1-5 star ratings, add to cart, adjust quantity, checkout, view order history. |
| **Store Admin** | `/admin` | `$_SESSION['admin_id']` (`role=admin`) | Product catalog CRUD, stock management, view customer orders, update order status (`pending`, `processing`, `completed`, `cancelled`). |
| **Super Admin** | `/superadmin` | `$_SESSION['superadmin_id']` (`role=superadmin`) | Executive sales analytics, provision/disable/reset store admin accounts, customer directory, audit trail. |

---

## 🔑 Default Credentials (Development & Testing)

| Account Type | Username / Email | Password | Role |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin` | `Admin@123` | Platform Super Administrator |
| **Store Admin** | `admin` | `Admin@123` | Store Operations Admin |
| **Customer** | `tarunlakkoju3@gmail.com` | `Tarun@123` | Registered Customer |

*(All passwords are automatically hashed with Bcrypt `password_hash()` upon migration/registration).*

---

## 🛡️ Security Hardening Applied

* **Prepared Statements Everywhere:** All queries across customer, admin, superadmin, and API endpoints use parameterized MySQLi prepared statements to eliminate SQL Injection.
* **Bcrypt Password Hashing:** Stored and validated via `password_hash()` and `password_verify()`.
* **Cross-Site Request Forgery (CSRF):** All state-changing POST forms and mutations are protected with cryptographic tokens validated via `verify_csrf_token()`.
* **Stored XSS Prevention:** All dynamic variables and database fields are escaped using `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`.
* **Atomic Transactions:** Order creation and cart clearing in `save_order.php` are wrapped in an ACID transaction with stock validation and row locking.
* **Strict Sessions:** Session cookies set with `HttpOnly`, `SameSite=Strict`, and dynamic `Secure` detection. Session IDs are regenerated on login.
* **Audit Logging:** Administrative events (logins, logouts, product creation/editing/deletion, status changes) are recorded in `audit_logs` with timestamps and IP addresses.

---

## 🖼️ Product Image Strategy & Render Note

Render's container disks are **ephemeral** (any files saved directly to the local disk vanish upon redeployment or automatic restarts). 

* **Implementation:** Product creation and editing accepts **hosted Image URLs** (e.g. Unsplash, Imgur, Cloudinary, AWS S3).
* **Tradeoff:** Storing URLs ensures that product images never disappear when Render containers restart, avoiding complex local file sync issues. For direct uploads, a cloud storage service like Cloudinary or AWS S3 can be integrated.

---

## 💻 Local Development Setup

### 1. Requirements
* PHP 8.1+ with `mysqli` extension enabled.
* MySQL 8.0+ or MariaDB 10.5+.

### 2. Database Import
Create a database named `smart_shop` and import the schema:
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS smart_shop;"
mysql -u root -p smart_shop < database/schema.sql
```

Run the migration script to ensure all columns and password hashes are synchronized:
```bash
php database/migrate.php
```

### 3. Environment Setup
Copy `.env.example` to `.env` (optional for local if using default `127.0.0.1` / `root`):
```bash
cp .env.example .env
```

### 4. Start Local Server
Run PHP's built-in development web server:
```bash
php -S 127.0.0.1:8000
```
Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser.

---

## 🚀 Deploying to Render (Option A: External MySQL)

Render provides web services with Docker runtimes. Since Render does not provide a managed MySQL service, connect to an external free or managed MySQL instance (such as **Aiven for MySQL**, **Railway**, **PlanetScale**, or **TiDB Cloud**).

### Step 1: Provision External MySQL
1. Create a free MySQL database on [Aiven.io](https://aiven.io/), [Railway.app](https://railway.app/), or [TiDB Cloud](https://tidbcloud.com/).
2. Obtain your connection parameters:
   * Host (e.g. `mysql-xxxx.aivencloud.com`)
   * User (e.g. `avnadmin`)
   * Password
   * Database Name (e.g. `smart_shop` or `defaultdb`)
   * Port (e.g. `3306` or `11223`)
3. Connect via MySQL Workbench or CLI and import `database/schema.sql`.

### Step 2: Push Repository to GitHub / GitLab
```bash
git init
git add .
git commit -m "Production-ready Smart Shop with dual admin portals"
git branch -M main
git remote add origin https://github.com/your-username/smart-shop.git
git push -u origin main
```

### Step 3: Create Render Web Service
1. Log in to [Render Dashboard](https://dashboard.render.com/).
2. Click **New +** → **Web Service**.
3. Connect your GitHub repository.
4. Render will automatically detect the `Dockerfile` and `render.yaml`.
5. Under **Environment Variables**, provide your external database credentials:
   * `DB_HOST`: Your external MySQL host
   * `DB_USER`: Your external MySQL user
   * `DB_PASS`: Your external MySQL password
   * `DB_NAME`: Your database name
   * `DB_PORT`: Your MySQL port (e.g. `3306`)
   * `APP_ENV`: `production`
6. Click **Deploy Web Service**.

Render will build the Docker container using PHP 8.2 Apache, bind to `$PORT` via `docker-entrypoint.sh`, verify health status on `/healthz.php`, and go live!

---

## 📑 Complete Route Map

### 🛒 Customer Storefront
* `/index.php` — Product catalog with star ratings and stock indicators.
* `/login.php` — Customer authentication (CSRF, bcrypt, session regeneration).
* `/register.php` — Customer registration.
* `/cart.php` — Interactive shopping cart with item quantity updates.
* `/checkout.php` — Order summary and checkout confirmation.
* `/save_order.php` — ACID transactional order placement.
* `/payment_success.php` — Order receipt and confirmation.
* `/profile.php` — Customer account & detailed order history.
* `/logout.php` — Session termination.

### 🏢 Store Admin (`/admin`)
* `/admin/login.php` — Independent store admin login.
* `/admin/dashboard.php` — Inventory metrics & product catalog list.
* `/admin/add_product.php` — Add product (title, price, image URL, stock).
* `/admin/edit_product.php` — Edit product details and stock level.
* `/admin/delete_product.php` — POST-only CSRF product deletion.
* `/admin/orders.php` — Manage customer orders & status updates.
* `/admin/logout.php` — Store admin session termination.

### ⚡ Super Admin (`/superadmin`)
* `/superadmin/login.php` — Independent superadmin login.
* `/superadmin/dashboard.php` — Executive sales totals & analytics.
* `/superadmin/admins.php` — Provision, disable, and reset store admin accounts.
* `/superadmin/customers.php` — View registered customer accounts & spend.
* `/superadmin/audit_logs.php` — Real-time security & administrative audit trail.
* `/superadmin/logout.php` — Super admin session termination.

### 🔌 API Endpoints
* `POST /api/add_cart.php` — Adds product to user cart (validates stock).
* `POST /api/remove_cart.php` — Removes item from user cart.
* `POST /api/update_qty.php` — Adjusts item quantity (respects stock).
* `POST /api/rate_product.php` — Records 1-5 star user rating.
* `GET /healthz.php` — HTTP 200 health check endpoint for Render.
