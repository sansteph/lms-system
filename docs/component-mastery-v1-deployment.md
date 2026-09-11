# Component Mastery V1 Update

Students qualify after completing every lesson in an assigned course, including lessons that have not yet been released. Existing completed lesson records and passed student AI quizzes count. Completing five projects alone no longer qualifies.

The course scan identifies named microcontrollers and microprocessors, including controller boards. Sensors, motors, and general robotics do not create assessments. Each course revision is scanned once after completion; the stored scan is shared, but completion is checked separately for each student. Updated course material triggers a new scan.

The scheduler prepares assessments every five minutes. The existing Prepare Assessment action also works. Both assessment and certificate titles use `Basics in [device name]`. STEM Engineers can review AI scores through Assessment Review in the web app and the assessment review workflow in the mobile app. Pending certificates follow corrected scores and pass/fail outcomes.

## Existing Records

The migration retires old mastery papers and keeps submissions and issued certificates. It updates component titles where the component can be identified. A previously approved certificate whose component name was overwritten can be repaired automatically when the student has only one historical mastery component. Ambiguous historical certificates require manual identification; the migration does not guess.

Already completed courses qualify without students repeating lessons. Run the generation command below to process them immediately. Successful scans and generated papers are reused on repeated runs. Previously submitted component assessments are not generated again.

## VPS Deployment

Commit and push the backend changes first. On the VPS:

```bash
cd /var/www/tinkedge-lms
git pull --ff-only origin main
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan view:cache
php artisan queue:restart
php artisan component-mastery:generate
php artisan schedule:list
```

The existing Laravel scheduler must run every minute. Confirm that `component-mastery:generate` appears in `schedule:list`. Generation requires the configured Gemini API key and writable assessment storage. Failed scans or papers are retried on the next scheduled run and recorded in `storage/logs/laravel.log`.

## Android Update

From the mobile repository:

```powershell
flutter build apk --release --dart-define=API_BASE_URL=https://tinkedge.tech
```

Upload `build/app/outputs/flutter-apk/app-release.apk` to `/var/www/tinkedge-lms/public/downloads/InnovatEdge.apk`, then install the updated APK on the phone.

Verify an incomplete student cannot start mastery, a completed student sees the appropriate device assessments, and an engineer can review an AI result. Approve a passing certificate and check its title in Achievements and in its PDF download.
