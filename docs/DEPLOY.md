# Deploy DentAI for free (with a database)

Free hosting changes often — check each provider's page before you commit. As of the sources I checked
(Oct 2026), these are the realistic options:

| Option | Free domain | Database | Notes |
|---|---|---|---|
| **A. Free shared PHP host** (InfinityFree, ByetHost-style) | free subdomain (e.g. `yourname.byet.org`) | free MySQL/MariaDB via phpMyAdmin | Easiest. No SSH, short PHP time limit, no cron. Needs PHP version that Laravel 13 requires (see `composer.json`). Test early that the host allows outgoing HTTPS calls (PayMongo). |
| **B. Wasmer Edge** (free plan) | free subdomain + custom domain | managed MySQL included | Advertises a free Laravel plan with limits (about 100k requests/month). Follow their Laravel guide. |
| **C. Free cloud VM** (e.g. Oracle Cloud "Always Free") | free subdomain via DuckDNS | MySQL or SQLite on the VM | Full control, SSH, cron. You set up nginx/PHP yourself. Needs a card for sign-up. |

Free plans are fine for a school project or demo. For a real clinic with real patient data, use a paid host with backups.

---

## Option A — free shared host, no SSH

### 1. Prepare on your computer
```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan key:generate --show      # copy this value for APP_KEY
```
Create a production `.env`:
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...                  # from above
APP_URL=https://yourname.byet.org
SESSION_DRIVER=database
DB_CONNECTION=mysql
DB_HOST=...  DB_PORT=3306  DB_DATABASE=...  DB_USERNAME=...  DB_PASSWORD=...   # from the host's MySQL page
PAYMONGO_SECRET_KEY=...
PAYMONGO_WEBHOOK_SECRET=...
```

### 2. The database (no SSH → import an SQL file)
1. On the host's control panel, **create a MySQL database** and note host/name/user/password.
2. On your computer create an empty local MySQL database (XAMPP/Laragon), point a temporary `.env` at it, then:
   ```bash
   php artisan migrate:fresh --force
   ADMIN_EMAIL=you@clinic.com ADMIN_PASSWORD='a-long-password' php artisan db:seed --class=ProductionSeeder
   ```
   `ProductionSeeder` adds the price list, the problem-type drop-down and one admin — **not** the fake demo patients.
3. Export that local database from phpMyAdmin (*Export → SQL*) and **Import** it in the host's phpMyAdmin.
4. Log in as admin and add your real doctors/assistants (or insert them before exporting).

### 3. Upload
1. Upload the project (without `node_modules`, `.git`, `tests`) to a folder **outside** the public web folder if the host allows it
   (e.g. `/dentai/`). Put the **contents of `public/`** into the web folder (`htdocs/` or `public_html/`).
2. In `htdocs/index.php` change the three paths so they point at the project folder, e.g.
   `__DIR__.'/../vendor/autoload.php'` → `__DIR__.'/../dentai/vendor/autoload.php'` (same for `bootstrap/app.php` and
   `storage/framework/maintenance.php`).
   *If the host only lets you use `htdocs/`:* put the project in `htdocs/dentai-app/`, add a file
   `htdocs/dentai-app/.htaccess` containing `Require all denied`, and use `__DIR__.'/dentai-app/...'` in `index.php`.
3. Make `storage/` and `bootstrap/cache/` writable (permissions 775 in the file manager).
4. Turn on the host's **free SSL** and open `https://yourname.byet.org`.

Free-host limits to expect: no cron (not needed here), no `php artisan storage:link` (so X-ray image upload won't display —
skip that feature or copy the `storage/app/public` folder into `public/storage`).

---

## Option C — free VM (Ubuntu), the "proper" way
```bash
sudo apt update && sudo apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip unzip git composer nodejs npm
git clone <your-repo> /var/www/dentai && cd /var/www/dentai
composer install --no-dev --optimize-autoloader && npm ci && npm run build
cp .env.example .env && php artisan key:generate      # then edit .env like in step 1
php artisan migrate --force
ADMIN_EMAIL=you@clinic.com ADMIN_PASSWORD='long-password' php artisan db:seed --class=ProductionSeeder
php artisan storage:link && php artisan config:cache && php artisan route:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```
Point nginx `root` at `/var/www/dentai/public`, then get a free subdomain (DuckDNS) and free HTTPS:
`sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx -d yourname.duckdns.org`.
SQLite also works here (`DB_CONNECTION=sqlite`, keep `database/database.sqlite` writable and back it up).

---

## After deploying — checklist
- `APP_DEBUG=false`, strong admin password, no demo accounts in the live database.
- Register the PayMongo **live** webhook for `https://your-domain/webhooks/paymongo` (docs/PAYMONGO.md).
- Book a test visit, schedule it, add medicine, create a bill, switch Online payment ON, pay with a small amount.
- Back up the database regularly (phpMyAdmin export is enough for a start).
