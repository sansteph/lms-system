# Web To Mobile Integration Tracker

Purpose: track every LMS web-app change made after mobile development was paused, so each API contract and workflow can be mirrored safely in the Flutter app later.

## Current Focus

- Web app is the source of truth.
- Flutter app development is paused unless a web change requires a small compatibility update.
- New or changed workflows should expose stable API behavior before mobile work resumes.
- Avoid direct mobile database access; mobile should continue to use the website API.

## Change Log

| Date | Web change | API or data impact | Mobile follow-up |
| --- | --- | --- | --- |
| 2026-09-07 | Started web-first tracking pass. | No code/API changes yet. | Use this file as the checklist before the next Flutter integration sweep. |
| 2026-09-07 | Monitoring section table UI enhancement and replacement of button-based segregation with proper section/filter-driven controls. | `GET /admin/activity-monitoring` accepts `institute`, `date`, `viewer_type`. `GET /admin/assessment-monitoring` accepts `institute`, `student_class`, `student_section`, `status`, `date`, `search`. `GET /admin/assessment-review-monitoring` keeps `institute`, `student_class`, `student_section`, `status`, `search`. Mobile API `GET /api/admin/monitoring/assessments` now also accepts `status`, `date`, and `search`. | Mirror the finalized Monitoring filter model and action grouping in Flutter. Use section filters as filters, not navigation/action buttons. |
| 2026-09-07 | Removed remaining Monitoring section navigator/button segregation and replaced table `N/A` fallbacks with real class/institute/context labels. | Monitoring pages now default to explicit filters instead of auto-selected pager scope. Learning content class column uses student class/section or activity section context; assessment tables use student class/section, assessment assigned class, and assessment/user institute fallbacks. | Flutter Monitoring should avoid pager-like segmented buttons and should display class/institute/context labels from the same API fields/fallback logic. |
| 2026-09-07 | Fixed STEM Engineer rows in Learning Content Access Log showing generic `Learning Content` / `Learning Content Preview` as class. | Preview rows now derive class/section from the content ID in `page_url`; general teacher content-page rows derive a compact class scope from the STEM Engineer institute classes. Mobile API activity monitoring uses the same fallback. | Flutter Learning Content Monitoring should render the API subtitle/context as class scope rather than deriving class from activity section names. |
| 2026-09-07 | Updated STEM Engineer `My Classes` defaults so tables show useful current data before filters are applied. | Web `GET /teacher/my-classes` now defaults Released Teaching Plan Content to the current teaching-plan week and Today's Session Execution to today's date. Mobile `GET /api/engineer/sessions` now keeps `pending_sessions`, scopes `learning_content` to the current week, and adds backward-compatible `today_sessions` plus `pagination.today_sessions`. | Flutter My Classes should use `today_sessions` for Today's Session Execution and `learning_content` for current-week released content. Class filters remain optional narrowing controls. |
| 2026-09-07 | Added global scroll safety for long form modals, including AI question paper generation and management add/edit forms. | No API/data changes. Modal content now caps itself to the viewport, keeps headers/footers visible, and scrolls the form body. Long-form assessment, content, class, institute, student, user, teaching-plan, badge, and profile dialogs explicitly opt into scrollable modal behavior. | Flutter forms should use scrollable bodies with fixed action areas where possible, especially AI question paper, uploads, student/user edits, and teaching-plan forms. |
| 2026-09-07 | Optimized landing-page motion to reduce learner/feature card scroll lag. | No API/data changes. Disabled expensive per-frame homepage particle DOM animation and added CSS performance guardrails for homepage sections/cards. Follow-up restored the original assistant scene flow: hero slide, feature-card jumps/backflips, and footer sleep, while keeping duplicate hero/floating assistant behavior controlled by the scene engine. | Flutter landing page should avoid per-frame widget rebuilds for decorative particles. Prefer static/composited background layers and keep animated AI entry lightweight, with only one visible assistant entry per view. |
| 2026-09-07 | Restricted student AI access to homepage chatbot plus AI quiz only. | Student-auth web routes no longer render the shared chatbot. Student homepage chatbot remains available as public/general context only. Student content no longer exposes AI summary buttons or the AI summary view; the legacy AI review URL redirects to the quiz. Mobile `POST /api/ai-chat/ask` rejects Student accounts, and student learning/quiz payloads no longer include AI summary text. | Flutter student module should remove AI chatbot/drawer and any AI summary display. Keep only AI quiz flows using `requires_ai_quiz`; ignore `ai_summary`/`has_ai_summary` for students. Other roles keep AI features. |
| 2026-09-07 | Added Principal Feedback feature using the shared panel feedback workflow. | New web routes: `GET /principal/feedback` and `POST /principal/feedback`, protected by `principal.auth`. Feedback email payload includes Principal name, user ID, email, phone, and institute; submit redirects back to Principal feedback instead of Manager feedback. | Flutter Principal module should include a Feedback screen/action matching Manager/Admin panel feedback fields: category, subject, message. |
| 2026-09-07 | Simplified Manager and Principal dashboards into report-access summary dashboards. | Manager/Principal dashboard routes now render summary-only report access views instead of duplicating feature cards. Actual workflows remain available through sidebar sections and report hubs. | Flutter Manager/Principal dashboards should show report scope/availability summaries and avoid duplicating feature tiles already present in module sections. |
| 2026-09-07 | Added optional AI Component Mastery assessments for students. | New table `ai_component_content_profiles`; new assessment fields `component_key`, `component_label`, `certificate_eligible`; new route `POST /student/component-assessments/{componentKey}/generate`. Student Assessment now shows AI-recommended component assessments only after 5+ passed practical AI quizzes in the same component family. Generated papers use the proper assessment/session/submission/manual-evaluation flow and can create pending `Component Mastery` certificate requests using existing certificate templates. Monthly/Annual assessments are untouched. | Flutter Student Assessment should show eligible component mastery offers, call a future mobile API generate endpoint, hide generated component assessments unless eligibility is met, and reuse the normal assessment taking/submission UI. |
| 2026-09-07 | Added a dedicated Student `Component Mastery` section, student-scoped eligibility notifications, and automatic AI evaluation. | New route `GET /student/component-mastery` lists only eligible component families with status and Take/Prepare actions. `lms_notifications` now supports nullable `student_id` and `component_key` so eligibility notifications are private to the eligible student. Component Mastery submissions are evaluated by Gemini against the generated paper blueprint; passing results create the same pending certificate approval record used by normal certification workflows. | Flutter should add a dedicated Component Mastery sidebar/page, register the same student-scoped notification behavior through the mobile API, and show AI-evaluated score/pass/certificate-pending states. Do not expose Component Mastery to students who have not met the five-topic eligibility rule. |
| 2026-09-07 | Expanded Teaching Plan editing beyond status/remarks. | Web `POST /teaching-plans/update/{id}` and mobile `PUT /api/admin/teaching-plans/{id}` now accept `title`, `start_date`, `release_day`, `status`, and `remarks`. Only locked weeks are rescheduled; released/completed weeks and session history remain unchanged. | Flutter Teaching Plan edit forms should expose the same fields and clearly mark course/class/content order as fixed after generation. Preserve locked-week-only scheduling behavior. |
| 2026-09-07 | Added MFA for staff and mandatory Firebase phone verification for students. | New `mfa_challenges` and `mfa_audit_logs` tables plus `users.mfa_enabled`. Staff can opt in from the Two-Factor Authentication sidebar page and receive email challenges. Student web login now verifies the guardian/contact phone through Firebase before creating a session; mobile `/api/login` returns a short-lived `mfa_required` challenge and `/api/student-mfa/verify` issues the Sanctum token only after Firebase verification. | Configure `FIREBASE_WEB_API_KEY`, `FIREBASE_AUTH_DOMAIN`, `FIREBASE_APP_ID`, and `FIREBASE_MESSAGING_SENDER_ID` on the web/API environment, enable Firebase Phone provider, and ensure student contact values use E.164-compatible numbers. Flutter uses `firebase_auth` for the same challenge exchange. FCM push tokens are not proof of identity. |
| 2026-09-07 | Student MFA switched from Firebase Phone Auth to Laravel email OTP because Firebase SMS required billing. | Student web and mobile login now issue/verify the existing email challenge using the student’s stored guardian email. Firebase remains used for FCM push notifications. Phone-auth configuration is no longer part of student login. | Flutter uses `/api/login` `mfa_required` plus `/api/student-mfa/verify` with a six-digit `code`; `firebase_auth` is no longer needed for student MFA. |
| 2026-09-07 | Added initial mobile parity for Manager, Principal, and Component Mastery. | Mobile login accepts `Manager` and `Principal`; `/api/manager/reports` exposes global session/engineer reports, `/api/principal/reports` enforces institute scope, and `/api/student/component-mastery` exposes only eligible component offers and statuses. | Flutter now has Manager/Principal role entry points, role report screens, and a Student Component Mastery screen. Generation/take-assessment action wiring remains the next parity item. |
| 2026-09-07 | Added mobile staff MFA parity and enforced enabled staff MFA during mobile login. | `POST /api/login` now returns an email OTP challenge for enabled `User` accounts; `POST /api/mfa/verify` completes the challenge. Protected MFA settings endpoints expose status, enable, verify-enable, and disable with current-password checks and audit logging. | Flutter now exposes a reusable Two-factor authentication screen for Admin, STEM Engineer, Manager, and Principal roles. Student email OTP remains mandatory. |

## Mobile Parity Audit Snapshot

- [x] Course lesson authoring: list, search/filter, create/edit, delete, protected teacher/student previews.
- [x] Bulk student import: downloadable template and multipart CSV import UI.
- [x] My Space and achievement submissions: create, edit, upload evidence, delete, status, and moderation views.
- [x] Manager and Principal reports, feedback, notifications, and role-scoped approvals.
- [x] Student Component Mastery eligibility, generation, assessment entry, AI evaluation, and certificate workflow.
- [x] Staff mobile MFA settings and MFA-aware mobile login.
- [x] Session parity for the workflows actually present in the web app: start, server-synchronised timer/state, automatic expiry, end status, remarks, history, and completion release.
- [x] Mobile course authoring parity for the attachment/content types currently supported by the web bulk authoring form: PDF teacher file and optional PDF student file.
- [ ] End-to-end FCM delivery verification on the live Firebase project. Invalid and stale tokens are now removed after `UNREGISTERED`, `INVALID_ARGUMENT`, or `NOT_FOUND` responses; a real device token and live notification still need one production smoke test.
- [ ] New product work, not web parity: attendance check-ins, pause/resume, emergency controls, hardware monitoring, and live event streams. These require a web data model and workflow before mobile cloning.
- [ ] Final release APK compile/install smoke test after dependency refresh.

## Pending Web UI/API Work

### Monitoring Section

- [x] Enhance Monitoring table UI so it remains readable and usable across desktop and smaller screens.
- [x] Replace button-based segregation with clear section-specific filters.
- [x] Keep filters tied to actual Monitoring data states and workflows, not generic copied controls.
- [x] Separate contextual actions from filters, so view/export/status-style actions do not behave like filter tabs.
- [x] Confirm whether existing Monitoring API/list endpoints need query parameter changes before coding.
- [x] Document the final filter payloads and response shape for Flutter parity.
- [x] Remove remaining section navigator/button segregation from Monitoring detail pages.
- [x] Replace visible Monitoring table `N/A` fallbacks with actual available data or clear unassigned labels.

Final web filter contracts:

- Learning Content Monitoring: `institute`, `date`, `viewer_type`.
- Assessment Monitoring: `institute`, `student_class`, `student_section`, `status`, `date`, `search`.
- Assessment Review Monitoring: `institute`, `student_class`, `student_section`, `status`, `search`.

Mobile API follow-up:

- `GET /api/admin/monitoring/assessments` supports `student_class`, `student_section`, `status`, `date`, and `search`.
- Response remains additive/backward-compatible; `filters.statuses` was added with `Started`, `Submitted`, and `AutoSubmitted`.

## API Contract Checklist

For every web change, record:

- Route path and HTTP method.
- Required auth role and institute/student scoping.
- Request payload fields and validation rules.
- Response shape for success and error states.
- Related database tables or model relationships.
- Notification behavior, including in-app notification and FCM push behavior.
- File/media behavior, including protected URLs, expiry, MIME type and download/preview rules.
- Mobile follow-up file(s) or screen(s), if already known.

## Push Notification Notes

- FCM tokens are registered through the web API, not directly against the database.
- Rejected Firebase tokens should be treated as stale only when Firebase confirms an unregistered/invalid token condition.
- Content-release notification UX should avoid blocking users with one modal per notification on mobile-sized screens.

## Deployment Notes

- APK hosting is separate from Git push because Flutter build outputs are ignored by Git.
- Public Android APK path currently planned as `/downloads/InnovatEdge.apk`.

## Manager And Principal Modules

- Manager web role added with predefined live credential migration: `support@tinkedge.com` / `Manager@TinkEdgeLMS26`.
- Manager routes use `manager.auth`, global institute scope, shared AI PDF report generation, all-institute session reports, STEM Engineer weekly/monthly performance reports, feedback, notifications, and approval-center access.
- Principal web role added with `principal.auth`; principals are managed by super admin from Principal Management and are institute-scoped for all report queries.
- Principal report routes cover session reports daily/weekly/monthly and student performance daily/weekly/monthly.
- Principal feedback routes cover institute feedback submission through the shared panel feedback mailer.
- Mobile parity needed later: add Manager and Principal login routing, role dashboards, report filter screens, report PDF download actions, principal management only for super admin, and matching RBAC in API endpoints.
