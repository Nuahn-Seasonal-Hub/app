# 🌿 Nuahn Seasonal Hub

A PHP-based seasonal job marketplace connecting **clients** (who post jobs) with **providers** (who apply for them), with admin and manager oversight.

---

## 📋 Features

- **Multi-role auth** — superadmin, manager, client, provider
- **Job board** — post, browse, apply, save, and manage seasonal jobs with geo-location
- **Applications management** — track provider applications per job
- **Admin dashboard** — analytics, user management, audit logs, subscriptions
- **Subscription plans** — client plan management
- **Activity tracking** — full audit trail of all actions
- **Dark/light theme** — per-user theme preference

---

## 🗂️ Project Structure

```
nuahn/
├── actions/          # Form action handlers (create_job, login_action, etc.)
├── assets/           # JS libraries
├── config/
│   ├── db.example.php  # ← copy to db.php and configure
│   └── init.php
├── includes/         # Shared PHP includes (header, footer, flash, auth)
├── public/           # All front-end pages
│   ├── admin/        # Admin-only pages
│   ├── css/          # Stylesheets (Bootstrap, Font Awesome, custom)
│   └── jobs/
├── uploads/jobs/     # User-uploaded job images (not tracked in git)
└── index.php         # App entry point
```

---

## ⚙️ Local Setup

### Requirements
- PHP 8.x
- MySQL 8.x (or MariaDB)
- A local server (XAMPP, Laragon, WAMP, etc.)

### Steps

1. **Clone the repo**
   ```bash
   git clone https://github.com/Nuahn-Seasonal-Hub/app.git
   cd app
   ```

2. **Create the database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE nuahseasonalapp_db;"
   mysql -u root -p nuahseasonalapp_db < database/nuahseasonalapp_db.sql
   ```

3. **Configure the database connection**
   ```bash
   cp config/db.example.php config/db.php
   # Edit config/db.php with your local MySQL credentials
   ```

4. **Create the uploads directory** (not tracked in git)
   ```bash
   mkdir -p uploads/jobs
   ```

5. **Serve the app**
   - Point your local server document root to this folder, or
   - Use PHP's built-in server: `php -S localhost:8000 -t public`

6. **Login with seed accounts** (password: `password123` for all)

   | Role        | Email                    |
   |-------------|--------------------------|
   | superadmin  | carol@example.com        |
   | manager     | david@example.com        |
   | client      | alice@example.com        |
   | provider    | bob@example.com          |

---

## 🗄️ Database

The schema is in [`database/nuahseasonalapp_db.sql`](database/nuahseasonalapp_db.sql).

**Tables:** `users`, `jobs`, `applications`, `saved_jobs`, `audit_logs`, `subscriptions`, `plans`, `activities`, `activity_logs`, `notifications_log`, `products`, `categories`, `cart`, `sales`, `sale_details`

Idempotent schema updates live in [`database/migrations.sql`](database/migrations.sql). The Docker entrypoint applies it on every start (it also resets the demo accounts above to `password123`); on an existing local database run:

```bash
mysql -u root -p nuahseasonalapp_db < database/migrations.sql
```

---

## 🤝 Contributing

1. Fork the repo
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes and open a pull request

---

## 📄 License

MIT

## UI v2 (client proposed design) and the sample-data screens

The UI follows the client's proposed design: teal/pine palette, serif headlines, photo service cards, and the mobile Book and Track flows.
Screens whose backend doesn't exist yet (bookings, plans, weather/calendar, tracking, chat, snow queue, completion evidence)
read **sample data** from `fixtures/*.php`, one file per feature, and are marked with a "Sample data" tag.
**Hook-up notes for each screen (data shapes and the endpoints to supply): [`docs/UI-DATA-HOOKUP.md`](docs/UI-DATA-HOOKUP.md).**
Photo sources and licences: `public/assets/img/photos/CREDITS.md`.

### UI v3 (client guides doc1 + doc2, all six services)

UI only: every screen below is presentational, with sample data and preview states. The functionality (APIs, payments,
dispatch, weather, notifications) belongs to Sir Paul and is listed per screen in `docs/UI-DATA-HOOKUP.md` sections 10-22.

| Screen | URL |
|---|---|
| Service pages (bins, mechanics, snow, fall cleanup, lawn, car wash) | `/public/service.php?s=garbage-bin-cleaning` (etc.; `&as=basic` shows the Basic view) |
| Booking review | `/public/book.php` (from a service page) |
| Basic vs Premium / care plans | `/public/plans.php`, `/public/care_plans.php` |
| Change appointment | `/public/change_appointment.php` (`?state=on_route`) |
| Snow event, come later | `/public/snow_event.php` (`?state=forecast/published/pending/confirmed/declined`, `?weather=unavailable`) |
| Live tracking (live data for Snow Removal and Mobile Mechanics) | `/public/track.php?id=` |
| Activity + notifications | `/public/activity.php`, `?tab=notifications` |
| Mechanic menu, request, quote (live) | `/public/mechanics.php`, `/public/mechanic_request.php`, `/public/quote.php?task=` |
| Provider dashboard (live) | `/public/provider_dashboard.php` (provider) |
| Operations (live: vetting, disputes, payouts, audit) | `/public/operations.php` (manager / superadmin) |
| Service settings | `/public/service_settings.php` (manager / admin) |
| States, timing and exception reference | `/public/status.php` (public) |

### Bookable services (Snow Removal and Mobile Mechanics)

These two services run on real database tables, with escrow payments (a sandbox provider by default, Stripe test or live mode once keys are supplied),
photo-gated completion, disputes, vetting and an operations console. The other four services stay on the Coming Soon gate.
Full description of the tables, state machines, endpoints, payment setup, cron and pending items: **[`docs/BOOKING-BACKEND.md`](docs/BOOKING-BACKEND.md)**.
Quick start: `mysql ... < database/migrations.sql`, open the site, sign in with a demo account (see the docs), post a snow task.
Auto-release cron: `*/10 * * * * php scripts/auto_release.php`. Tests: `php tests/stripe_provider_test.php`, `tests/e2e/`.

---

## Credits

UI designed and developed by Christo Innocent (xristoinno@gmail.com, +256783335335). See `docs/CREDITS.md`.
