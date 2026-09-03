# Mobile Parity Sweep

Status (2026-09-02): the development gaps identified in the preceding comparison are implemented and regression-tested. This is closure of the listed gaps, not a claim that every possible device, dataset or production integration has been acceptance-tested. Deployment was excluded from this continuation.

## Identified Gap Closure

| Identified gap | Implemented behavior and verification |
| --- | --- |
| AI question-paper service namespace | Correct AI service imports; mobile generates an actual web-format PDF and submits it for approval. |
| STEM result AI and PDF | Generate insights, render structured output, copy and download PDF with current filters; teacher and institute scope enforced. |
| STEM student CSV | Web CSV output available from the mobile roster; All classes and selected class/section/search/status filters respected. |
| Login notification popups | Mobile login returns relevant notices, shares web per-login display limits and suppresses already-seen deliveries locally. |
| Capped compatibility lists | Older engineer lists and the combined approvals API now expose stable pagination with named page parameters and totals. Current cards retain their existing paginated workflow APIs. |
| Expired protected PDF retry | Retrying re-resolves lesson metadata and obtains a fresh signed URL; request failures remain visible and retryable. |
| Plaintext saved tokens | Native tokens moved to Keychain/Keystore-backed secure storage; old preferences removed. Browser tokens stay in memory. Sessions remain bound to the API origin. |
| AI prep/review helpers | Missing passing-score helpers, student attempt type and assigned-content access checks restored; teacher quiz generation and student review submission tested. |
| Feedback | Mobile uses the web category validation, mail template and recipients for STEM Engineers and students. |
| Admin deletion cleanup | Student, class, teacher and institute deletion delegate to web cleanup; tests check related records/files and preservation of other institutes/shared files. |
| Admin topic completion | Released teaching-plan topics can be marked complete; the selected plan item, role and week state are checked. |
| Missing filters | Admin institute/teacher/student/class/course filters and STEM student/result/certificate controls wired; dependent options reset correctly and search uses actual web columns. |
| Dashboard differences | Web-scoped counts, STEM class roster and student upcoming assessments exposed in the mobile dashboard. |
| Monitoring state/route names | Named routes distinguish lists from previews; active activity is not marked completed by heartbeats; stale mobile activity shows Disconnected in both clients. |
| Dead assessment-start route | Removed the unimplemented engineer route. Student assessment/session start workflows are unchanged; all registered API controller methods are checked. |
| Remaining public/session views | Newsroom, logged public certificate verification and manual session-completion feedback added using existing web services and media. |

Additional safeguards: signed exports expire after five minutes, recheck the account/institute and become invalid when the issuing token is revoked. New refresh handlers use synchronous state updates and surface failures in-place.

### Fresh Verification

- PHP: **76 tests, 766 assertions**, using isolated in-memory SQLite with the PDO driver explicitly enabled; no skipped tests. JUnit output: `storage/logs/parity-tests.xml`.
- PHP syntax: **107 files** checked across application, routes and tests.
- Flutter: **67 tests passed**, including dependent-filter clearing, ordered monitoring events, secure storage, PDF URL retry, AI output and public screens at 320/390/900 widths with enlarged text.
- Flutter analysis: **no issues found**.
- Android: fresh release-mode APK compiled with `API_BASE_URL=https://tinkedge.tech`; APK v2 signature verified. Existing debug signing remains appropriate for internal testing only.
- APK: `C:\Users\sanju\Desktop\lms-app\build\app\outputs\flutter-apk\app-release.apk`, 86,606,345 bytes, built 2026-09-02 21:44 local time.
- APK SHA-256: `0ba630235354aab370ff7ebddf50d125e95e37e9f32adb234b9b5b9a2c35b006`.
- Flutter web: fresh standard JavaScript release build passed with the HTTPS origin. The optional WebAssembly compatibility check reports unsupported `dart:html`/`dart:js_util` in the cached `flutter_secure_storage_web` dependency; a Wasm build is not claimed to work. Native token storage and the standard Chrome build compile successfully.

AI responses and mail transport are mocked in automated tests; the tests do render the real mail templates and real generated PDF outputs. They do not prove live Google API availability or email delivery.

### Prior Sweep Coverage

- [x] Token role, institute, profile-owner and AI-context isolation, with regression tests
- [x] In-app PDF previews, same-origin token transport and native protection bridge
- [x] Teaching-plan creation/templates/deployment/sync, week controls, AI-training dates and catch-up actions
- [x] Scheduled release dates and previous-week completion rules, including future-start protection
- [x] Student assessment papers, server deadlines, saved drafts, violation handling and idempotent submission
- [x] Engineer assessment authoring, question-paper generation, grading and result workflows
- [x] Session restoration, 30/50-minute rules, completion choices and item-specific AI-prep context
- [x] Session routes keep teaching-plan item IDs separate from content IDs; invalid references cannot open the wrong resource
- [x] Certificate approval/rejection/revocation/reissue, eligibility checks and separate student badge list
- [x] Approval hub opens paginated question-paper review with protected previews and certificate controls with web institute/class/section/status filters
- [x] Engineer roster editing/import and profile photos
- [x] Mobile monitoring capture visible in web and mobile, bounded heartbeat time and scoped notification targeting
- [x] Pagination for workflow lists, admin student/engineer rosters and teaching plans, monitoring, notifications, engineer session/content lists and student assessment availability/results
- [x] Hybrid administration and community submission/moderation/comment/like workflows
- [x] Three-column feature grids and large-text regression tests; session class cards now open class content
- [x] Refresh callbacks use synchronous state changes; management deletion stays on the current page and failed refreshes can retry
- [x] Assessment windows are filtered before pagination, repeated starts preserve the timer and expired attempts submit their saved draft
- [x] Protected raster image/video previews and engineer audio previews, matching the inspected web format allowlists; signed links recheck access, support byte ranges and refuse paths outside private storage
- [x] Live HTTPS origin set to https://tinkedge.tech; saved sessions are bound to their API origin and invalid/legacy sessions are cleared
- [x] PHP suites and route-method validation, with current counts recorded above
- [x] Flutter suite, including router ID checks, 320/390/900-width approval/assessment/session/media navigation, large-text forms, dependent filters, teaching-plan pagination, server-bound auth and five management delete/refresh/retry flows
- [x] Final Flutter analysis: no issues found; deprecated widgets and unused code cleaned up
- [x] Manual deployment package, integrity/source-conflict checker, public contract marker and read-only production preflight; no production deployment performed
- [x] Fresh live-HTTPS Android release-mode APK built; APK signature verified
- [x] Fresh live-HTTPS standard Flutter web release build passed; optional Wasm limitation recorded above
- [ ] Physical-device acceptance and iOS compilation/signing

## API Mapping

The explicit web-action adapter runs legacy actions with the token holder's temporary role/session context, then restores the original web session. It does not accept arbitrary controller or method names.

| Path | Scope |
| --- | --- |
| `GET /api/workflows/{area}` | Paginated records, filters, actions and edit-form defaults |
| `POST /api/workflows/{area}/{action}/{id?}` | Whitelisted web actions with legacy validation and authorization |
| `GET /api/workflows/{area}/{id}/document` | Authorized assessment/question-paper/result/certificate PDF |
| `GET /api/engineer/sessions/state` | Active session restoration and recent history |
| `POST /api/engineer/sessions/start` and `/end/{id}` | Server-timed session lifecycle |
| `GET /api/student/assessment-sessions/{id}` | Owned exam state, draft and deadline |
| `POST /api/student/assessment-sessions/{id}/draft`, `/violation`, `/submit` | Exam lifecycle and persisted results |
| `POST /api/activity` | Start, bounded heartbeat and stop for teacher/student activity |
| `/api/students/import*`, `/api/{audience}/{kind}`, `/api/admin/courses/{id}/lessons*` | CSV import, file submissions and course authoring from the earlier sweep |
| `GET /api/mobile-status` | Public non-sensitive API contract marker; does not query the database |
| `GET /api/admin/management-filters/{area}` | Role-scoped management filter metadata and dependent options |
| `POST /api/admin/content/{id}/complete` | Super-admin completion of a specific released teaching-plan topic |
| `POST /api/workflows/results/insights` | STEM result AI with the selected roster/result filters |
| `GET /api/workflows/{area}/export` | Short-lived, token-bound CSV/PDF export ticket |
| `GET /api/workflow-exports/{ticket}` | Signed export download with account and token revalidation |
| `GET /api/public/newsroom` | Cached web Newsroom feed, with throttling |
| `POST /api/public/verify-certificate` | Existing web verification rules and audit log, with throttling |
| `GET /api/session-completion-video/{fileName}` | Signed access to the existing session-completion media |

`php artisan mobile:preflight` checks production configuration, protected routes, schema/migration history, storage and registered scheduler jobs without changing database records. It does not prove actual cron execution, mail delivery, Gemini availability or every application workflow. See [Manual Deployment](manual-mobile-deployment.md).

Workflow areas: teaching-plans, students, assessments, question-papers, results, certificates, badges, awards, profile, hybrid-learners and community. Available areas/actions depend on the authenticated role.

Question-paper approvals use the web controller's review actions and reviewer audit fields. Certificate filters use the web's `institute`, `student_class`, `student_section`, and `certificate_status` keys, including `Pending Approval` and `pending_admin_approval`. Changing an institute clears dependent class/section selections, and clearing filters resets pagination. The approval hub no longer opens the legacy pending-only summary screen.

## Capture And Scheduling

- Android protected screens use `FLAG_SECURE`. Flutter does not reveal a protected document until the native bridge acknowledges protection. Actual device screenshot/recording behavior still needs acceptance testing.
- iOS covers protected windows during screen recording and app switching. Public iOS APIs do not guarantee prevention of individual screenshots. No claim of absolute iOS screenshot blocking is made.
- Web browsers cannot provide equivalent screenshot prevention.
- Teaching-plan releases are checked daily at 08:00 in the configured application timezone, against each week's saved release date. A completion-gated plan waits for its previous week. The VPS must run Laravel's scheduler; this sweep did not change VPS cron.
- Gemini calls remain on the server. The inspected assistant uses complete JSON responses, not SSE; the client does not simulate token streaming.

## Remaining Acceptance And Audit Work

- Build/sign/run iOS on macOS and validate privacy covers on a real device. Windows cannot run this gate.
- Verify real Android capture behavior, document rendering, authenticated flows and sustained session/background behavior against the deployed backend.
- The device listing during this continuation returned no attached phone/emulator, so the fresh APK is not device-acceptance-tested.
- Exercise production mail, Gemini configuration, PDF conversion dependencies and the VPS scheduler without using production data for automated test writes.
- Automated responsive checks cover representative forms, grids and media controls, not every possible dataset. A complete loaded-data visual/filter audit on physical devices remains.
- Native image/video and engineer audio players are implemented and widget-tested, but codecs and long-session playback still need Android/iOS device testing. Unsupported office/3D formats are not claimed as native previews. No fabricated QR/hardware/offline attendance workflows were added where no corresponding web engine was identified.
- Store distribution is not configured: Android still uses the example application ID and debug signing for release builds, and iOS signing must be set up on macOS. Build success alone is not a store-ready release.

No production database writes are performed by the automated tests; test databases use isolated in-memory SQLite.

## Deployment Unchanged

- Website: `https://tinkedge.tech`; server application: `/var/www/tinkedge-lms`.
- The user chose manual deployment through their hosting terminal. No SSH keys were generated, no remote files changed and no production database writes performed.
- The earlier archive `dist/mobile-deployment/tinkedge-mobile-api-2026-09-02-165226.tar.gz` predates these gap fixes and has not been regenerated. Do not treat it as the current source bundle.
- No new live-server checks, uploads, configuration changes or database changes were performed. The current local development changes still require a separate deployment and authenticated acceptance pass.
- App ID and signing remain unchanged. The fresh APK above is an internal-testing artifact, not a store release.
