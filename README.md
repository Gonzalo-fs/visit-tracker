# ​💻👀​​ Visit Tracker

A lightweight, self-hosted, monolithic website analytics and visitor tracking system built with **PHP**, **MySQL (PDO)**, **Vanilla JavaScript**, and an interactive dashboard powered by **Vue 3**, **Chart.js**, and **Tailwind CSS** (via CDN).

Designed with **GDPR privacy by design**: visits are logged using a non-reversible cryptographic hash (`IP + User-Agent + Secret Salt`), eliminating the need for invasive tracking cookies or GDPR consent banners.

---

## 🎬 Video Guide

Watch the full walkthrough:
[![Visit Tracker - Video Guide](https://cdn.loom.com/sessions/thumbnails/a2fe795a61f14e449a0f878027b349c9-with-play.gif)](https://www.loom.com/share/a2fe795a61f14e449a0f878027b349c9)

---

## 📑 Table of Contents

- [Features](#-features)
- [Project Structure](#-project-structure)
- [System Requirements](#-system-requirements)
- [Installation & Setup](#-installation--setup)
- [How to Use the Components](#-how-to-use-the-components)
  - [1. Analytics Dashboard (`dashboard.php`)](#1-analytics-dashboard-dashboardphp)
  - [2. Client Tracking Script (`tracker.js`)](#2-client-tracking-script-trackerjs)
  - [3. Interactive Test Site (`demo.php`)](#3-interactive-test-site-demophp)
  - [4. Adding a New Website](#4-adding-a-new-website)
- [API Reference](#-api-reference)
  - [Ingestion Endpoint (`track.php`)](#ingestion-endpoint-trackphp)
  - [Analytics & Management Endpoint (`stats_api.php`)](#analytics--management-endpoint-stats_apiphp)
- [Database Architecture](#-database-architecture)
- [GDPR Privacy & Security](#-gdpr-privacy--security)
- [License](#-license)

---

## ✨ Features

- **No Cookies Required**: 100% compliant with privacy regulations without irritating consent banners.
- **Ultra-lightweight Tracking Script (`tracker.js`)**: Under 4 KB Vanilla JS with zero external dependencies.
- **Dynamic Origin Resolution**: The tracker automatically targets the host server even when embedded across different domains or ports.
- **Interactive Single-Page Dashboard**: Built with Vue 3 (CDN), Chart.js (CDN), and Tailwind CSS (CDN).
- **Hourly & Daily Granularity**:
  - Selecting **Today** displays an hourly 24-hour timeline (`00:00` to `23:00`).
  - Selecting multi-day ranges displays continuous daily trend lines.
- **Top Pages & Device Breakdown**: Track pageviews and unique visitors sorted by popularity.
- **GUI Site Registration**: Add new domains and generate tracking snippets directly from the dashboard modal.
- **Pre-configured Demo Playground (`demo.php`)**: Test simulated traffic across multiple routes and devices with a single click.

---

## 📁 Project Structure

```plaintext
visit-tracker/
├── config.php       # Database PDO connection settings & secret GDPR salt
├── track.php        # Ingestion API endpoint (receives tracking POST requests with CORS)
├── tracker.js       # Client tracking script embedded on target websites
├── stats_api.php    # Analytics JSON endpoint (stats, time series, top pages, site management)
├── dashboard.php    # Interactive web analytics dashboard (Vue 3 + Chart.js + Tailwind CSS)
├── demo.php         # Interactive client website demo for live testing
├── schema.sql       # Database schema creation script with tables, indexes, and default site
└── README.md        # Comprehensive setup and usage documentation
```

---

## 💻 System Requirements

- **Web Server**: Apache or Nginx (e.g. via [XAMPP](https://www.apachefriends.org/), WampServer, or native installation).
- **PHP**: Version 8.0 or higher with `pdo_mysql` and `json` extensions enabled.
- **MySQL / MariaDB**: Version 5.7+ or MariaDB 10.3+.

---

## ⚙️ Installation & Setup

### 1. Place the Project in Your Web Root
Ensure the project folder is placed inside your web server's public root:
- **XAMPP (Windows)**: `C:/xampp/htdocs/visit-tracker/`
- **Linux**: `/var/www/html/visit-tracker/`

### 2. Configure Database Credentials (`config.php`)
Open `config.php` and verify the database connection settings. By default, it is configured for standard local development:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'visit_tracker_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Change this secret salt in production to ensure anonymous visitor hashes
define('SECRET_SALT', 'vt_salt_9a8f4c2e6b1d8a3c5e7f0b2d4e6a8c1e3f5b7d9');
```

### 3. Import the Database Schema (`schema.sql`)
You can import the database using your preferred method:

#### Option A: Using MySQL Command Line
```bash
mysql -u root -p < schema.sql
```

#### Option B: Using phpMyAdmin
1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Click on the **Import** tab.
3. Browse and select `C:/xampp/htdocs/visit-tracker/schema.sql`.
4. Click **Go** / **Import**.

This will create:
- The database `visit_tracker_db`.
- Tables: `sites`, `pages`, and `visits` with foreign keys and performance indexes.
- A default test site (`domain: localhost`, `site_token: demo_token_visit_tracker`).

---

## 📖 How to Use the Components

### 1. Analytics Dashboard (`dashboard.php`)
Open your browser and navigate to:
```
http://localhost/visit-tracker/dashboard.php
```

#### Features in the Dashboard:
- **Site Selector**: Switch between different registered domains using the top-right dropdown.
- **Date Range Filters**:
  - **Today**: Displays a detailed **24-hour hourly curve** (`00:00` to `23:00`).
  - **Last 7 Days**, **Last 30 Days**, **This Month**, or custom **From / To** date inputs.
- **KPI Cards**: View total unique visitors, pageviews (hits), distinct pages tracked, and dominant device.
- **Interactive Chart**: Chart.js line graph featuring hover tooltips and metric toggles.
- **Most Visited Pages Table**: Displays ranking, unique visits, pageviews, and relative traffic share.
- **Device Distribution**: Breakdown of traffic by Desktop, Mobile, and Tablet.
- **Embed Snippet Card**: One-click button to copy the tracking script tag for the currently active site.

---

### 2. Client Tracking Script (`tracker.js`)

To track visits on any website, embed this `<script>` tag right before the closing `</body>` tag:

```html
<script src="http://localhost/visit-tracker/tracker.js" data-token="YOUR_SITE_TOKEN" defer></script>
```

#### How it works:
1. It reads its own `data-token` attribute.
2. It automatically determines the `track.php` endpoint relative to where the script is hosted.
3. It detects the device type (`Mobile`, `Tablet`, or `Desktop`).
4. It reads the current path (`window.location.pathname`).
5. It dispatches an asynchronous `POST` request with `keepalive: true` so the request completes even if the user immediately navigates away.

---

### 3. Interactive Test Site (`demo.php`)
To see the tracker in action without having to set up an external website, open:
```
http://localhost/visit-tracker/demo.php
```

- Each page reload automatically fires a visit via `tracker.js`.
- Use the built-in simulation buttons to trigger simulated visits to `/home`, `/products`, `/pricing`, and `/contact` across Desktop and Mobile devices.
- Click **"View Real-time Dashboard"** to watch the new visits reflected immediately.

---

### 4. Adding a New Website

#### Via Dashboard GUI (Recommended):
1. Open `http://localhost/visit-tracker/dashboard.php`.
2. Click the **"+ Add Site"** button in the header.
3. Enter the website domain (e.g. `myportfolio.com` or `blog.mydomain.io`).
4. Click **"Register Site"**.
5. The system generates a unique 32-character token and presents your custom embed `<script>` snippet with a one-click copy button.

#### Via SQL:
```sql
USE `visit_tracker_db`;

INSERT INTO `sites` (`domain`, `site_token`)
VALUES ('client-website.com', 'unique_token_hex_or_string');
```

---

## 🔌 API Reference

### Ingestion Endpoint (`track.php`)

Receives tracking events from `tracker.js`. Supports cross-origin requests (`CORS: *`).

- **Method**: `POST`
- **URL**: `http://localhost/visit-tracker/track.php`
- **Content-Type**: `application/json`

#### Request Body:
```json
{
  "site_token": "demo_token_visit_tracker",
  "url": "/products/shoes",
  "device": "Desktop"
}
```

#### Successful Response (`201 Created`):
```json
{
  "success": true,
  "message": "Visit logged successfully",
  "data": {
    "visit_id": 105,
    "page_id": 4,
    "url": "/products/shoes",
    "device": "Desktop",
    "visit_time": "2026-09-08 20:30:00"
  }
}
```

---

### Analytics & Management Endpoint (`stats_api.php`)

Provides statistical data and site management for the dashboard.

#### 1. Fetch Analytics
- **Method**: `GET`
- **URL**: `http://localhost/visit-tracker/stats_api.php?site_token=TOKEN&start_date=YYYY-MM-DD&end_date=YYYY-MM-DD`

**Key Response Fields**:
- `period.time_unit`: Returns `"hour"` when querying a single day, or `"day"` for multi-day ranges.
- `totals`: Global counters (`unique_visits`, `total_pageviews`, `pages_tracked_count`).
- `daily_series`: Continuous timeline (hourly or daily) with filled zero-values for gaps.
- `pages`: Pages ranked descending by unique visits.
- `devices`: Breakdown by device type.

#### 2. List Registered Sites
- **Method**: `GET`
- **URL**: `http://localhost/visit-tracker/stats_api.php?action=list_sites`

#### 3. Register New Site
- **Method**: `POST`
- **URL**: `http://localhost/visit-tracker/stats_api.php?action=create_site`
- **Body**: `{"domain": "example.com"}`

---

## 🗄️ Database Architecture

The relational schema implements clean foreign keys and cascade deletions:

```
+--------------------------------+       +--------------------------------+
|             sites              |       |             pages              |
+--------------------------------+       +--------------------------------+
| id (PK)                        |<---\  | id (PK)                        |
| domain (VARCHAR 255, UNIQUE)   |     \-| site_id (FK -> sites.id)       |<---\
| site_token (VARCHAR 64, UNIQUE)|       | url (VARCHAR 255)              |     \
| created_at (DATETIME)          |       | created_at (DATETIME)          |      \
+--------------------------------+       +--------------------------------+       \
                                           UNIQUE(site_id, url)                    \
                                                                                    \
                                         +--------------------------------+         \
                                         |             visits             |          \
                                         +--------------------------------+           \
                                         | id (PK)                        |            \
                                         | page_id (FK -> pages.id)       |-------------/
                                         | visitor_hash (VARCHAR 64)      |
                                         | device (VARCHAR 20)            |
                                         | visit_time (DATETIME)          |
                                         +--------------------------------+
```

### Key Performance Indexes:
- `idx_sites_token` on `sites.site_token` for instant token validation.
- `unique_site_page` on `pages(site_id, url)` preventing URL duplicates per site.
- `idx_visits_page_time` and `idx_visits_time` on `visits` for fast range aggregations.
- `idx_visits_hash` for fast `COUNT(DISTINCT visitor_hash)` queries.

---

## 🔒 GDPR Privacy & Security

Under European Union GDPR and global privacy standards, IP addresses are considered Personal Identifiable Information (PII). Visit Tracker respects user privacy through mathematical irreversibility:

1. **Anonymous Hashing**:
   $$\mathtt{visitor\_hash} = \mathtt{hash('sha256',\ IP + UserAgent + SECRET\_SALT)}$$
2. **Zero Storage of Raw IPs**: Raw IP addresses and full User-Agent strings are **never written** to the database or stored in cookies/localStorage.
3. **Rainbow-table Protection**: The high-entropy `SECRET_SALT` prevents attackers from reversing hashes using precomputed lists of public IP addresses.
4. **No Cookies**: Users visiting tracked websites do not receive persistent tracking cookies or identifiers.

---

## 📝 License

This project is licensed under the **PolyForm Noncommercial License 1.0.0**.

- ✅ **Permitted**: Free for personal, academic, educational, research, and hobbyist projects.
- ❌ **Prohibited**: Any commercial use, deployment within corporations/companies, SaaS monetization, or for-profit operations is strictly forbidden without a separate commercial license from the copyright holder (**Gonzalo Fontana Santibáñez**).

See the [`LICENSE`](./LICENSE) file for the complete legal terms.
