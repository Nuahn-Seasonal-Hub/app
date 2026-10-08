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

**Tables:** `users`, `jobs`, `applications`, `saved_jobs`, `audit_logs`, `subscriptions`, `plans`, `activities`, `products`, `categories`, `cart`, `sales`, `sale_details`

---

## 🤝 Contributing

1. Fork the repo
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes and open a pull request

---

## 📄 License

MIT
