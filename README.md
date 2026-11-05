# Restaurant POS & ERP SaaS — RestoPro

Production-oriented, multi-tenant Restaurant POS & ERP SaaS built exactly to the specified stack:
**HTML5, CSS3, Bootstrap 5, JavaScript (AJAX/Fetch), Core PHP 8+ (no Laravel/React/Vue/Node), MySQL via PDO, Chart.js, Bootstrap Icons.**

Multi-tenant: every tenant is a **restaurant**; almost all operational data is also **branch-scoped** (`branch_id`). Users bound to a branch only see that branch's orders, tables, kitchen tickets and expenses.

## Roles
`owner`, `branch_manager`, `cashier`, `waiter`, `kitchen` (Kitchen Staff), `inventory_manager`, `accountant` — plus platform `super_admin`. Role guards via `require_role()` + `includes/roles.php` role sets (ROLE_POS, ROLE_KITCHEN, ROLE_STOCK, ROLE_MONEY…).

## Features
- **Auth:** register (creates restaurant tenant + Owner admin + 14-day trial + default **Main Branch** + starter categories & tables), login/logout, remember-me (hashed token cookie), forgot/reset password (hashed, expiring tokens), suspended account/tenant checks, `password_hash`, secure sessions, CSRF on every POST, XSS escaping via `e()`.
- **Dashboard:** today's sales & orders, total revenue, pending orders, product count, low-stock alerts (products + ingredients), best sellers, monthly revenue Chart.js, branch performance table.
- **POS (`pos.php` + `api/place_order.php`):** product grid by category with live search, JS cart (qty +/−), item discount %, order discount, tax %, dine-in/takeaway/delivery, table & customer & branch selection, cash/card/online/**split** payments. Placing an order runs in a **DB transaction**: server-side price recomputation, order + items + payments + kitchen ticket created, product **and** ingredient stock decremented with `stock_movements` audit rows, table marked occupied, loyalty points credited — and a printable receipt (`receipt.php?id=`).
- **Orders:** status flow pending → preparing → ready → served/completed, cancelled; **refund action restores stock** (products + ingredients), writes a negative refund payment row and frees the table.
- **Kitchen Display (`kitchen.php` + `api/kitchen_orders.php`):** live pending/preparing/ready tickets, JS polling every 15s, Start cooking / Mark ready actions updating orders + kitchen tickets.
- **Tables:** CRUD + floor status grid (available/occupied/reserved) with Free/Occupy/Reserve actions.
- **Menu:** categories CRUD; products CRUD with image URL, price/cost, stock tracking, variations (e.g. `Small:0, Large:4`) and add-ons (e.g. `Extra cheese:1.5`), combo flag, availability toggle, search.
- **Inventory:** product & ingredient stock with low-stock alerts and stock valuation, adjustments & **wastage** (all as stock movements), suppliers CRUD, purchase orders with **Receive action that adds stock** in a transaction.
- **Customers:** CRUD, search, membership levels, loyalty points (auto-earned on POS orders, manually adjustable), per-customer order history.
- **Staff:** CRUD with role, branch, shift, salary; plan staff-limit enforcement; one-click "Create login" (temporary password `password`).
- **Branches:** CRUD (plan branch-limit enforced, main branch protected), per-branch orders/revenue/staff/tables stats.
- **Finance:** expenses CRUD, **daily cash closing** (order payments grouped by method for any date), profit/loss summary (sales − expenses − received purchases).
- **Reports:** daily & monthly sales (chart), product sales, payments by method, date filters, **CSV export**.
- **Settings & Subscription:** restaurant settings (incl. default tax %, receipt footer), profile/password, support tickets, announcements; plans Free trial / Starter / Professional / Enterprise with monthly & yearly billing, branch + staff limits, coupon check (`WELCOME10`), invoices, gateway-ready payment architecture.
- **Super Admin (`super-admin/`):** platform dashboard, restaurants (tenants) with suspend/reactivate, plans CRUD (branch/staff/order limits), subscriptions + payments, coupons, support tickets, announcements, platform settings, audit log.

## Installation
1. Requirements: PHP 8.1+, MySQL 8+ (or MariaDB 10.4+), Apache/Nginx or PHP built-in server.
2. Import schema + demo data:
   ```bash
   mysql -u root -p < database.sql
   ```
   Creates database `resto_saas` — 36 tables, foreign keys, indexes, demo seeds.
3. Configure DB via env vars (or edit `config/config.php` defaults): `DB_HOST`, `DB_NAME` (default `resto_saas`), `DB_USER`, `DB_PASS`.
4. Serve: `php -S localhost:8000 -t .` → open `http://localhost:8000/auth/login.php`.

## Demo logins (password for all: `password`)
| Role | Email | Lands on |
|---|---|---|
| Super Admin | `super@restopro.test` | `super-admin/index.php` |
| Restaurant Owner (Demo Restaurant) | `admin@demo.com` | `dashboard.php` |
| Cashier (Main Branch) | `cashier@demo.com` | `dashboard.php` / POS |
| Kitchen Staff | `kitchen@demo.com` | Kitchen Display |
| Branch Manager (Downtown) | `manager@demo.com` | `dashboard.php` |
| Waiter | `waiter@demo.com` | POS / Orders |

Demo coupon: `WELCOME10` (10% off). Demo data: Main + Downtown branches, 8 tables, 4 categories, 10 products (with variations, add-ons, ingredients & recipe mapping), 2 suppliers, 3 customers, 5 orders today across pending/preparing/ready/completed, expenses and a received purchase order.

## Project structure
```
config/        DB + app config (PDO, env-based, secure session)
includes/      functions (CSRF, e() escaping, audit/activity, tenant + branch helpers, plan limits), auth guards, roles, header/footer/sidebar
auth/          login, register, logout, forgot/reset password
api/           place_order.php (transactional POS checkout), kitchen_orders.php (KDS polling)
super-admin/   platform dashboard, restaurants, plans, subscriptions, coupons, tickets, announcements, settings
assets/        CSS + JS (theme toggle, charts)
pos.php orders.php kitchen.php tables.php menu.php inventory.php customers.php staff.php branches.php finance.php reports.php receipt.php settings.php subscription.php dashboard.php
database.sql   complete schema + demo seed data
vercel-demo/   static in-browser demo (see below)
```

## Deployment notes
- **PHP/MySQL hosting (production app):** any PHP 8 + MySQL host / VPS (Apache/Nginx + PHP-FPM). Set env vars, import `database.sql`, point web root at this folder, enable HTTPS (Secure session cookie activates automatically).
- **Payment integration:** subscription payments/invoices are recorded with a gateway-ready architecture (plug Stripe/PayPal into `subscription.php` where the payment row is created); POS order payments are already recorded per method incl. split payments in `order_payments`.
- **Vercel:** Vercel cannot run Core PHP/MySQL, so `vercel-demo/index.html` is a single self-contained static demo (Bootstrap 5 + Chart.js via CDN, data in localStorage) with the **POS screen as the hero** — working cart/tax/discount/checkout, dashboard stats + chart, orders with status changes, kitchen view, tables grid, menu and plans. Deploy that folder to Vercel for the live demo link.

## Security notes
PDO prepared statements everywhere (incl. `FOR UPDATE` stock locking in POS checkout), `password_hash`/`password_verify`, CSRF tokens (POST forms + POS JSON API), output escaping, secure + HttpOnly + SameSite session cookies, tenant + branch scoped queries, plan-limit checks, audit logging. Change demo passwords immediately in production.
