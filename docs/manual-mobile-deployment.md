# Manual Mobile API Deployment

Website: https://tinkedge.tech
Existing server application: `/var/www/tinkedge-lms`
Hosting terminal user: `root`

This updates the existing Laravel application. The app talks to its HTTPS API, which uses the website's existing database. Do not open MySQL to the internet, put database passwords in Flutter, replace the live `.env`, regenerate `APP_KEY`, or import the laptop database over the live database.

The package has not been deployed. A public API check returned 404 before deployment. Local test/build results and remaining gaps are in the package's `VERIFICATION.md` (source: `docs/mobile-parity-sweep.md`); they are not evidence of production acceptance or 100% feature parity.

## 1. Upload Outside The Website

Upload the generated `.tar.gz` and matching `.sha256` file to `/root/mobile-deploy/` using your hosting file manager. If your VPS panel does not offer file upload, use SFTP with the VPS credentials managed in your hosting panel. Do not paste a VPS password into chat. This does not require granting the assistant SSH access.

Alternatively, if password-based SSH is configured, run this on the laptop, substituting the actual local package filename:

```powershell
scp "C:\Users\sanju\Desktop\lms-system-main\dist\mobile-deployment\__BUNDLE_NAME__.tar.gz" "C:\Users\sanju\Desktop\lms-system-main\dist\mobile-deployment\__BUNDLE_NAME__.tar.gz.sha256" root@200.97.164.180:/root/mobile-deploy/
```

Create the upload folder in the hosting terminal first:

```bash
install -d -m 700 /root/mobile-deploy
```

In the hosting terminal, verify and unpack:

```bash
cd /root/mobile-deploy
sha256sum -c __BUNDLE_NAME__.tar.gz.sha256
tar -xzf __BUNDLE_NAME__.tar.gz
php __BUNDLE_NAME__/check.php /var/www/tinkedge-lms
```

**Stop if any command fails.** `check.php` is read-only. It checks package integrity and every included source file against the known baseline or updated version. `CONFLICT` means the live website has another version; do not bypass the check. Compare/merge those files first. Share only conflicting filenames or non-sensitive error output, never `.env` or database contents.

## 2. Back Up Before Replacing Files

Keep the same hosting terminal open so `$BACKUP` remains set. Stop on any failure. Schedule a maintenance window; visitors cannot use the site while it is down.

```bash
umask 077
BACKUP="/root/lms-backups/$(date +%Y%m%d-%H%M%S)"
install -d -m 700 "$BACKUP"
cd /var/www/tinkedge-lms
php artisan down --retry=60
mysqldump --single-transaction --quick --routines --triggers -u root -p tinkedge_lms > "$BACKUP/database.sql"
test -s "$BACKUP/database.sql"
tar --exclude=.git --exclude=vendor --exclude=node_modules --exclude=storage/logs -czf "$BACKUP/application-and-uploads.tar.gz" -C /var/www tinkedge-lms
tar -tzf "$BACKUP/application-and-uploads.tar.gz" > /dev/null
printf 'Backup folder: %s\n' "$BACKUP"
```

Enter the existing MySQL password privately at the prompt. If the live database name is not `tinkedge_lms`, use its actual name from the live configuration. A successful dump is not a tested restore; retain the VPS provider snapshot/backup too. The archive includes the existing `.env` and uploaded files, so keep it private in `/root`, never `public/`.

## 3. Apply The Checked Source

Recheck immediately before copying. These commands preserve uploads, `.env`, the database and files not listed in the package; they do not run migrations.

```bash
cd /root/mobile-deploy
php __BUNDLE_NAME__/check.php /var/www/tinkedge-lms
```

Only after a PASS:

```bash
umask 022
cp -R __BUNDLE_NAME__/payload/. /var/www/tinkedge-lms/
cd /var/www/tinkedge-lms
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate:status
```

The `umask 022` step makes new application files readable by the PHP worker; backups already created with private permissions stay private. Use `composer install`, not `update` or `composer setup`. The latter can generate a new application key and run migrations. Keep the site's normal code ownership and PHP worker permissions; do not use `chmod 777`.

**Do not run all pending migrations blindly.** This repository contains historical cleanup/drop migrations. If anything is pending, review its PHP file and intended effect against the live database and the verified backup. Only run an approved migration by its exact path with `php artisan migrate --path=database/migrations/APPROVED_FILE.php --force`. Never use `migrate:fresh`, `db:wipe`, `migrate:reset`, or `db:seed` on production. Schema differences are a stop condition, not an invitation to force past errors.

## 4. Verify Production Configuration

Edit only the needed values in the existing live `.env` using the hosting editor. Keep the database credentials and `APP_KEY` unchanged:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tinkedge.tech
```

Verify the existing `GEMINI_API_KEY`, supported `GEMINI_MODEL`, mail configuration, and `AI_PDFTOTEXT_PATH`. API keys remain on the server. `pdftotext -v` checks whether the PDF text extractor exists. PDF conversion and protected storage must continue using the same working server setup and paths as the web app.

Then:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan mobile:preflight
php artisan schedule:list
crontab -l
```

`mobile:preflight` only inspects configuration, routes, schema, storage and scheduled task registration. It never sends mail, calls Gemini, runs migrations or writes database records. Any FAIL needs resolution before opening the site. Run it under the PHP worker account as well (commonly `sudo -u www-data php artisan mobile:preflight`) to verify actual storage permissions; root-only success does not prove worker access.

Check the existing scheduler in the hosting panel, crontab or system timer. If absent, add **one**, using the site's existing application worker account:

```cron
* * * * * cd /var/www/tinkedge-lms && /usr/bin/php artisan schedule:run >> /var/www/tinkedge-lms/storage/logs/scheduler.log 2>&1
```

Do not duplicate an existing scheduler. Teaching-plan release checks run daily at 08:00 in the **application timezone**, respecting each release date, plan start date and previous-week completion policy. AI preparation runs every five minutes. A task in `schedule:list` alone does not prove cron is running. Do not manually run release/AI-generation jobs just to test the connection; those jobs change live data and can make paid API calls.

## 5. Bring Up And Verify The API

Only after all required checks pass:

```bash
php artisan up
curl --fail --silent --show-error https://tinkedge.tech/api/mobile-status
curl --silent --show-error -o /dev/null -w '%{http_code}\n' -H 'Accept: application/json' https://tinkedge.tech/api/profile
```

Expected marker: `{"service":"innovatEdge-mobile","api_version":1,"release":"2026-09-02"}`. Unauthenticated `/api/profile` must return **401**, not 200, HTML, a redirect or 404. A marker response is a version check, not a full health check. If 404 persists, verify Nginx serves `/var/www/tinkedge-lms/public` and routes non-file requests through Laravel's `public/index.php`. Do not replace a working Nginx configuration blindly. Use the hosting panel's normal PHP reload procedure if OPcache prevents new code loading.

## 6. Test The App

Install the new live-HTTPS APK on your phone. Use internet normally: no `adb reverse`, local server or shared Wi-Fi is required for the live URL. Old locally saved sessions are rejected on a server change; sign in again. Students use their Student ID, not email.

Use designated test accounts, not real student records, for acceptance:

- Login/logout and password updates for each role; institute A must not access institute B.
- Create/edit/delete and refresh without leaving the page; pagination and filter resets.
- Teaching-plan assignment, future release visibility and prior-week gates.
- AI prep/review, session resume/end and server timing; mail delivery and PDFs.
- Protected image/video/audio/PDF on a real phone, background pause, expired-link retry and screenshot/recording handling.
- Student assessments, saved drafts/deadlines, result and certificate access; Hybrid course progress.
- Upload/import/authoring, approval decisions, notifications and report exports.

The Android release-mode APK is for **internal testing**: the existing project still uses the example application ID and debug signing. It is not a Play Store release. iOS needs a macOS build, signing and real-device acceptance. iOS cannot guarantee prevention of every still screenshot. Passing local automated tests is not 100% parity certification.

## Recovery

If a source/dependency/cache step fails, keep maintenance mode on and preserve the backup path and error. Restore the backed-up application source with its original ownership, `.env` and `composer.lock`, then reinstall its locked dependencies and rebuild its caches before bringing it up. Do not restore the database unless a reviewed migration actually changed it and you have a separate restore plan: a database restore can discard new records. New API files may need to be removed during a reviewed source rollback; do not run a broad delete command. Ask for a targeted rollback using the backup path and failed step.

Nothing in this package grants remote access or performs an unattended production deployment.
