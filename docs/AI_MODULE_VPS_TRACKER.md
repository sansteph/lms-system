# AI Module VPS Tracker

Last updated: 28 Jul 2026

## What Was Integrated

- Gemini AI provider configuration for LMS AI features.
- AI content summary generation from uploaded PDF previews/material.
- Teacher prep summaries and prep quizzes.
- Student AI review summaries and review quizzes.
- Passing rules:
  - STEM Engineer prep quiz: 50% minimum.
  - Student review quiz: 60% minimum.
- Quiz security flow:
  - No sidebar during prep/review quiz.
  - Tab switching, refresh, and redirecting are treated as violations.
  - Third violation auto-submits.
  - Manual or automatic submission redirects to dashboard.
- AI generation automation for upcoming Teaching Plan content only.
- AI report/analytics insights for admin reports, admin analytics, and teacher results.
- AI chatbot endpoints and homepage/module floating assistant UI.
- Chatbot topic guard limited to InnovatEdge/TinkEdge LMS workflows, STEM, ATL, components, projects, and learning content.
- AI Newsroom module that fetches Google News RSS for STEM/ATL topics, deduplicates articles, and uses Gemini to curate summaries and classroom learning angles.

## Main Files Added Or Updated

- `config/ai.php`
- `routes/web.php`
- `routes/console.php`
- `app/Services/Ai/GeminiAiService.php`
- `app/Services/Ai/AiContentSummaryService.php`
- `app/Services/Ai/PdfTextExtractionService.php`
- `app/Http/Controllers/AiContentController.php`
- `app/Http/Controllers/AiChatController.php`
- `app/Http/Controllers/NewsroomController.php`
- `app/Services/Newsroom/NewsroomFeedService.php`
- `app/Http/Controllers/PageController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Models/AiContentSummary.php`
- `app/Models/AiQuiz.php`
- `app/Models/AiQuizQuestion.php`
- `app/Models/AiQuizAttempt.php`
- `app/Models/AiQuizAnswer.php`
- `resources/views/teacher/ai-prep.blade.php`
- `resources/views/teacher/ai-prep-quiz.blade.php`
- `resources/views/student/ai-review.blade.php`
- `resources/views/student/ai-review-quiz.blade.php`
- `resources/views/partials/ai-full-report.blade.php`
- `resources/views/newsroom/index.blade.php`
- `resources/views/pdf/ai-insights-report.blade.php`
- `public/images/ai-assistant-frames/*`

## Database Changes

Migration:

- `database/migrations/2026_07_14_000006_create_ai_learning_tables.php`

Tables created:

- `ai_content_summaries`
- `ai_quizzes`
- `ai_quiz_questions`
- `ai_quiz_attempts`
- `ai_quiz_answers`

Required VPS command after pulling:

```bash
php artisan migrate --force
```

## Required VPS `.env` Values

Add or verify:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-3.1-flash-lite
GEMINI_TIMEOUT=60
AI_MAX_SUMMARY_INPUT_CHARS=24000
AI_PDFTOTEXT_PATH=pdftotext
AI_CONTENT_AUTO_GENERATION_START_DATE=2026-07-31
AI_CONTENT_AUTO_GENERATION_LIMIT=2
AI_TEACHER_PASSING_PERCENTAGE=50
AI_STUDENT_PASSING_PERCENTAGE=60

NEWSROOM_CACHE_MINUTES=120
NEWSROOM_PER_KEYWORD_LIMIT=6
NEWSROOM_DISPLAY_LIMIT=12
NEWSROOM_KEYWORDS="STEM education,ATL lab,robotics for schools,AI in education,IoT projects for students,Arduino robotics,electronics components,sensors actuators microcontrollers,global STEM projects,student robotics competition,STEM innovation competition,science fair projects,hackathons for students,school innovation labs"
NEWSROOM_GOOGLE_NEWS_LOCALE=en-IN
NEWSROOM_GOOGLE_NEWS_COUNTRY=IN
NEWSROOM_GOOGLE_NEWS_CEID=IN:en
```

Notes:

- On Ubuntu VPS, `AI_PDFTOTEXT_PATH=pdftotext` is correct if `pdftotext` is installed globally.
- Do not use a Windows path on VPS.
- Keep `AI_CONTENT_AUTO_GENERATION_START_DATE=2026-07-31` if current/completed week content should remain undisturbed.
- Newsroom uses Google News RSS and requires outbound HTTPS access from the VPS.

## VPS Packages Required

Install Poppler for PDF text extraction:

```bash
sudo apt update
sudo apt install -y poppler-utils
pdftotext -v
```

The final command should print the Poppler/pdftotext version.

## Scheduler / Cron Required

Laravel scheduler must be active on the VPS.

Cron entry:

```bash
* * * * * cd /var/www/tinkedge-lms && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled AI command:

```bash
php artisan ai-content:generate-upcoming
```

Current schedule:

- Teaching Plan weekly release runs every Friday at 08:00.
- AI generation runs every five minutes without overlapping.
- AI generation only targets active non-template Teaching Plan items with week release date on or after `AI_CONTENT_AUTO_GENERATION_START_DATE`.

Manual safe test command:

```bash
php artisan ai-content:generate-upcoming --limit=1
```

Expected output examples:

- `Generated AI prep for: Content Title`
- `AI upcoming content generation completed. Generated 1, skipped 0, failed 0.`
- If already generated: `Generated 0, skipped 1, failed 0.`

## Deployment Commands After Pull

Use this after pushing AI updates to VPS:

```bash
cd /var/www/tinkedge-lms
git pull origin main
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run `npm install` only if `package-lock.json` or frontend dependencies changed. Otherwise `npm run build` is enough.

## Functional Checks On VPS

1. Verify AI config is loaded:

```bash
php artisan config:show ai.provider
php artisan config:show ai.gemini.model
php artisan config:show ai.content.pdftotext_path
```

2. Verify Poppler:

```bash
which pdftotext
pdftotext -v
```

3. Generate one upcoming AI summary:

```bash
php artisan ai-content:generate-upcoming --limit=1
```

4. Teacher flow:

- Login as STEM Engineer.
- Open Learning Content/My Classes.
- For upcoming released content, confirm AI prep is generated.
- Open Prep Quiz.
- Score below 50%: access should remain blocked.
- Score 50% or above: content can be taught, and no more prep attempts are required.

5. Student flow:

- Login as Student.
- Complete a released topic.
- Start AI Review.
- Submit quiz.
- Score below 60%: next content remains blocked.
- Score 60% or above: next content can unlock according to existing sequence rules.

6. Admin reports:

- Open Reports.
- Generate AI insights.
- Download AI report.
- Open Analytics.
- Generate/download AI insights.

7. Chatbot:

- Click floating assistant.
- Ask LMS/STEM/ATL/content-related question.
- Ask unrelated question.
- It should answer relevant learning/LMS questions and politely refuse unrelated topics.

8. Newsroom:

- Open `/newsroom`.
- Confirm STEM/ATL/robotics news cards load.
- Click Refresh News.
- Confirm article links open in a new browser tab.
- If AI is unavailable, the page should still show feed fallback cards instead of a 500 error.

## Common VPS Issues

### Gemini API key missing

Symptom:

- AI generation fails with Gemini API key missing.

Fix:

```bash
nano .env
php artisan optimize:clear
php artisan config:cache
```

### PDF text extraction fails

Symptom:

- `PDF text extraction failed. Install Poppler/pdftotext...`

Fix:

```bash
sudo apt install -y poppler-utils
which pdftotext
php artisan optimize:clear
```

### Scheduler not generating AI automatically

Check cron:

```bash
crontab -l
```

Run manually:

```bash
php artisan schedule:run
php artisan ai-content:generate-upcoming --limit=1
```

### AI generation is too slow

Recommended:

- Keep `AI_CONTENT_AUTO_GENERATION_LIMIT=1` or `2`.
- Let scheduler generate gradually.
- Avoid generating many contents manually during school hours.

### Newsroom is empty

Check outbound access:

```bash
curl -I "https://news.google.com/rss/search?q=STEM%20education&hl=en-IN&gl=IN&ceid=IN:en"
```

Then clear cache:

```bash
php artisan optimize:clear
```

## Current Workflow Reminder

- AI generation should begin from next-week content only.
- Current/completed week content remains untouched unless manually generated.
- Teacher prep quiz is required before conducting AI-enabled content.
- Student review quiz is required before moving to the next AI-enabled content.
- One source template content can serve many class sections, avoiding duplicate quiz generation where the deployed content points back to the same source template.
