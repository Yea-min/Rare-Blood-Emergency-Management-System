# RBEMS — Rare Blood Emergency and Matching System

CSE 2302 Database Management System Lab — Open-Ended Experiment
University of Liberal Arts Bangladesh

## What's included

- `sql/rbems_schema.sql` — full MySQL schema: 7 tables, 4 triggers, 4 views, 2 stored
  procedures, plus seed data (3 hospitals, 6 donors including Rh-null/Bombay/Kell+
  profiles, sample inventory and requests).
- PHP pages (procedural, PDO + prepared statements) covering all 8 required features.

## Setup

1. **Create the database**
   ```bash
   mysql -u root -p < sql/rbems_schema.sql
   ```
   This drops/recreates a `rbems` database and loads seed data.

2. **Configure the connection**
   Edit `config/db.php` and set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` to match
   your local MySQL/XAMPP/WAMP setup (defaults: `localhost` / `rbems` / `root` / empty).

3. **Run it**
   Place the `rbems/` folder inside your PHP server's web root (e.g.
   `htdocs/rbems` for XAMPP, or `php -S localhost:8000` from inside the folder)
   and open `index.php` in your browser.

## Fixed admin login (seeded)

| Username        | Password    |
|-----------------|-------------|
| admin1          | 12345678    |

## Feature → file map

| # | Feature | File(s) |
|---|---------|---------|
| 1 | Donor registration & phenotype profile | `donor_register.php`, `donor_list.php` |
| 2 | Automatic donation eligibility (interval rule) | `v_eligible_donors` view, `functions.php::checkEligibility()`, shown in `donor_list.php` |
| 3 | Rare blood compatibility cross-matching | `compatibility_check.php`, `functions.php::isCompatible()`, `sp_find_compatible_inventory` |
| 4 | Inventory shelf-life & expiry alerts | `inventory.php`, `inventory_add.php`, `trg_inventory_expiry`, `v_expiring_soon` |
| 5 | Hospital request submission with priority | `hospital_request.php`, `v_pending_requests` |
| 6 | Admin approval & inter-hospital transfers | `admin_login.php`, `admin_dashboard.php` |
| 7 | Request status tracking & notifications | `request_list.php`, `notifications.php`, `trg_request_notification`, `trg_transfer_completed` |

## Notes on rule assumptions (state these in your report)

- Donation interval: 90 days for male donors, 120 days for female/other — a simplified
  standard blood-bank rule; adjust in `functions.php` / `v_eligible_donors` if your
  report cites a different guideline.
- Compatibility engine layers rare-phenotype overrides on top of standard ABO/Rh/Kell
  rules: Bombay-phenotype recipients can only receive Bombay-phenotype blood; Rh-null
  recipients can only receive Rh-null blood; Kell-negative recipients cannot receive
  Kell-positive blood.
- Shelf life: 42 days for fresh whole blood, 10 years for cryopreserved units.
