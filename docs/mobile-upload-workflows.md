# Mobile Upload Workflows

This closes the three explicitly outstanding workflows: file-based My Space and
achievement submissions, student CSV import, and course-content authoring. It is
not a certification of every existing application feature or an iOS release sign-off.

## Entry Points

- Students and STEM Engineers: My Space and Achievements now support evidence
  selection, submission details, and the edit/delete restrictions of the web app.
- Admin approvals: the four My Space/achievement queues open the matching role's
  submissions with institute, class/section where applicable, and status filters.
  Approval/feature decisions update the shared community feed.
- Admin and STEM Engineer student lists: Import Students downloads the CSV
  template, accepts a CSV file, and reports created/skipped rows individually.
- Admin course list: selecting a course opens its lessons. Upload a single PDF
  or a batch, supply separate teacher/student PDFs, edit metadata and order,
  filter lessons, preview files, and remove lessons. Template-source courses
  remain restricted to system admins.

## API Contract

Authenticated routes use the existing Sanctum bearer token. Multipart updates use
POST so PHP receives files correctly. There is no new database migration.

| Method | Route | Purpose |
| --- | --- | --- |
| GET, POST | `/api/{student\|engineer}/{my-space\|achievements}` | List or create owned submissions |
| POST, DELETE | Same route plus `/{id}` | Update or delete an owned submission |
| GET | `/api/admin/submissions/{audience}/{kind}` | Scoped moderation queue |
| POST | Same route plus `/{id}/{approve\|reject\|feature}` | Moderate; feature is My Space only |
| GET | `/api/students/import-template` | CSV columns and signed download URL |
| POST | `/api/students/import` | Multipart `students_csv` file |
| GET, POST | `/api/admin/courses/{id}/lessons` | List or upload lessons |
| POST, DELETE | Same route plus `/{lessonId}` | Edit or remove a lesson |
| GET | Same route plus `/{lessonId}/preview/{teacher\|student}` | Signed PDF preview URL |

Lesson uploads contain `lessons[index][field]` values and files, plus an
`expected_uploads` list of dotted file field names. If PHP truncates a batch, the
API rejects the incomplete upload rather than silently omitting selected files.
Failed batches roll back records and newly stored files.

## Validation and Storage

- My Space ideas require a PDF; projects require an HTTP(S) repository URL.
  Approved/featured entries cannot be edited or deleted.
- Student achievements require PDF/JPEG/PNG proof at creation. Teacher proof is
  optional. Proof limits are 5 MB. Existing files survive edits unless replaced.
- CSV imports create new records only, validate existing class/section/institute
  combinations and scoped student-ID uniqueness, and hash passwords. A scoped
  administrator or engineer cannot use the CSV to target another institute.
- Course PDFs are limited to 50 MB per file, at most 20 lessons per batch, with
  unique positive lesson orders. Separate teacher/student files remain private.
- Submission files retain the web app's public-storage convention so web approval
  previews continue working. Signed mobile URLs expire after five minutes; this
  does not make the legacy public storage URLs private.

## Deployment and Device Checks

Deploy the backend and mobile changes together. Refresh Laravel route caches and
ensure the existing storage disks and `public/storage` link are available. Keep
production API URLs on HTTPS, including URLs generated behind a reverse proxy.

The serving PHP process and reverse proxy must accept the intended upload size:
`upload_max_filesize` at least 50 MB, `post_max_size` larger than the combined batch,
and `max_file_uploads` at least 40 for twenty teacher/student pairs. This machine's
CLI configuration reports 200M, 220M, and 100 respectively. Check the actual web
server's PHP configuration separately; smaller batches avoid exceeding its limit.

File selection uses the platform document picker with iOS type identifiers; no
Android-specific filesystem paths or broad storage permission was added. macOS
uses read-only user-selected file access. iOS compilation and picker behavior still
require Xcode and a device/simulator check on macOS.

On this Windows setup, package resolution downloaded the dependencies but reported
the OS symlink/Developer Mode requirement. The Android build and tests succeed
with `--no-pub`. Enable Windows Developer Mode before regenerating desktop plugin
links; this sweep did not change the machine's security settings.

## Verification

Run backend tests without touching the local MySQL database:

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/MobileWorkflowParityTest.php
```

The suite uses isolated in-memory SQLite and fake storage. It passed 14 tests / 91
assertions covering ownership, role/institute isolation, approval restrictions,
signed URL expiry/tampering, invalid/oversized evidence, replacement cleanup,
CSV errors/password hashing, lesson rollback, and deletion cleanup.

In the Flutter directory:

```powershell
flutter test --no-pub
flutter build apk --debug --no-pub --dart-define=API_BASE_URL=http://127.0.0.1:8000
flutter build web --no-pub
```

All 10 Flutter tests pass. The form tests exercise 320, 390, and 900 logical-pixel
widths at normal and doubled text scale, including scrolling and edit prefills.
The new workflow files have no analyzer issues. Existing unrelated application
warnings remain outside this focused completion pass.

The local debug APK uses port 8000 over USB. Keep the backend running and forward
the device port with `adb -s DEVICE_ID reverse tcp:8000 tcp:8000`. A full rebuild,
not hot reload, is needed when adding the new native picker dependency.
