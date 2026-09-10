# Deploying the Principal, Manager and student fixes

There are two separate deployments: the Laravel backend at tinkedge.tech and the Flutter app. Updating only one leaves some fixes unavailable. These changes have been made locally; they have not been published to the live server.

## 1. Publish the source changes

Commit and push the reviewed changes in both repositories to their deployment branches. Include new files: untracked Flutter screens are not included in a commit until they are added. Check `git status` in each repository before committing. The mobile repository already contains changes from earlier work, so review those as part of the app release.

- Backend: `C:\Users\sanju\Desktop\lms-system-main`
- Flutter: `C:\Users\sanju\Desktop\lms-app`

Pushing sends source code to GitHub. It does not update tinkedge.tech or an installed phone app by itself.

## 2. Update the live backend

On the server, from the existing application directory:

```sh
cd /var/www/tinkedge-lms
git status --short
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan optimize:clear
php artisan migrate:status
```

If the pull reports local changes or a branch conflict, resolve those before continuing. Do not discard server edits. Preserve the live `.env` and uploaded storage files.

Back up the database through your normal server backup process before running pending migrations. Then run:

```sh
php artisan migrate --force
php artisan config:cache
php artisan view:cache
php artisan queue:restart
```

This fix adds no new migrations. However, Component Mastery depends on existing migrations for `ai_component_content_profiles`, the mastery fields in `assessments`, and `student_id` / `component_key` in `lms_notifications`. They must show as run. Migration commands update the database; a Git pull does not.

If PHP OPcache is configured not to check file timestamps, reload the PHP service using your server's existing deployment procedure. Keep the queue worker supervisor and scheduler running as already configured.

## 3. Update Flutter

In the Flutter repository:

```powershell
flutter pub get
```

For your local Chrome test, stop the previous Flutter run, then start:

```powershell
flutter run -d chrome --dart-define=API_BASE_URL=https://tinkedge.tech
```

For an Android installation:

```powershell
flutter build apk --release --dart-define=API_BASE_URL=https://tinkedge.tech
```

Install the new `build\app\outputs\flutter-apk\app-release.apk` on the test device using the same signing setup as the installed app. For a Play Store release, build an app bundle and distribute an updated version through the existing release process. Pushing Flutter source does not update installed apps.

If the Flutter web app is hosted separately:

```powershell
flutter build web --release --dart-define=API_BASE_URL=https://tinkedge.tech
```

Publish the complete `build\web` output to the Flutter site's configured location. Do not replace Laravel's `public` directory with it. Reload the hosted app after deployment.

## 4. Check the released behavior

- Principal: All Features opens Principal reports and updates; no Admin management or monitoring menu. Reports remain restricted to the assigned institute.
- Manager: Reports, updates, feedback and approvals open correctly. Daily, weekly and monthly student-performance reports are available alongside session and engineer reports.
- Student dashboard: assessment counts and upcoming assessments match the same student's web dashboard.
- Component Mastery: open Student Dashboard > All Features > Component Mastery. A component appears after five eligible practical topics have passed their AI reviews. Only a prepared paper for the student's class is offered. Prepare, take and result states follow the web workflow.

If a page still returns Server Error, reproduce once and check the corresponding newest exception in `storage/logs/laravel.log`. Check the exception message preceding the stack trace, along with migration status and the server's deployed Git commit.
