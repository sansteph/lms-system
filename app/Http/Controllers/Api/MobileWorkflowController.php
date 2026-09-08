<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\{Controller, PageController, TeachingPlanController, AssessmentController, AssessmentResultController, IndependentLearnerController, CommunityFeedController};
use App\Models\{User, Student, Institute, SchoolClass, Course, CourseContent, Content, TeachingPlan, Assessment, AssessmentResult, Certificate, IndependentLearner};
use App\Services\{MobileWebContext, TeachingPlanReleaseService, TeachingPlanTemplateDeploymentService};
use App\Services\Ai\{GeminiAiService, PdfTextExtractionService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MobileWorkflowController extends Controller
{
    private function authorizeArea(Request $request, string $area): void
    {
        $a = $request->user();
        $admin = $a instanceof User && in_array($a->role, ['Admin', 'InstituteAdmin'], true);
        $manager = $a instanceof User && $a->role === 'Manager';
        $teacher = $a instanceof User && in_array($a->role, ['Teacher', 'STEM Engineer'], true);
        abort_unless(match ($area) {
            'teaching-plans' => $admin,
            'question-papers' => $admin || $manager,
            'students', 'assessments' => $teacher,
            'results', 'certificates' => $teacher || $admin || $manager,
            'hybrid-learners' => $a instanceof User && $a->role === 'Admin',
            'awards', 'badges' => $a instanceof Student,
            'profile' => $teacher || $a instanceof Student,
            'community' => $teacher || $admin || $manager || $a instanceof Student,
            default => false,
        }, 403);
    }

    public function index(Request $request, string $area)
    {
        $this->authorizeArea($request, $area);
        return match ($area) {
            'teaching-plans' => $this->plans($request),
            'students' => $this->students($request),
            'assessments' => $this->assessments($request),
            'question-papers' => $this->questionPapers($request),
            'results' => $this->results($request),
            'certificates', 'awards' => $this->certificates($request, $area),
            'badges' => $this->badges($request),
            'hybrid-learners' => $this->learners($request),
            'profile' => $this->profile($request),
            'community' => $this->community($request),
        };
    }

    public function act(Request $request, string $area, string $action, ?int $id = null)
    {
        $this->authorizeArea($request, $area);
        $a = $request->user();
        if ($area === 'profile' && $action === 'edit') {
            return $this->updateProfile($request);
        }
        if ($area === 'profile' && $action === 'remove-photo') {
            return $this->removeProfilePhoto($request);
        }
        if ($area === 'results' && $action === 'insights') {
            abort_unless($a instanceof User && in_array($a->role, ['Teacher', 'STEM Engineer'], true), 403);
            try {
                return response()->json(app(MobileWebContext::class)->run($request,
                    fn () => app(PageController::class)->teacherResultsAiPayload($request, app(GeminiAiService::class))));
            } catch (\Throwable $error) {
                report($error);
                return response()->json(['message' => 'AI insights could not be generated. Please try again.'], 503);
            }
        }
        if ($area === 'students' && in_array($action, ['create', 'edit'], true)) {
            $request->validate(['class_id' => 'required|integer']);
            $class = SchoolClass::where('institute', $a->institute)->where('status', 1)->findOrFail($request->class_id);
            $request->merge(['class' => $class->class_name, 'section' => $class->section, 'institute' => $a->institute]);
        }
        if ($area === 'teaching-plans' && $action === 'create') {
            $request->validate(['course_id' => 'required|integer', 'class_id' => 'required|integer']);
            $course = $this->scope(Course::where('status', 1), $request)->findOrFail($request->course_id);
            abort_if($course->is_template_source, 422, 'Use a live institute course when creating a teaching plan.');
            $class = SchoolClass::where('institute', $course->institute)->where('status', 1)->findOrFail($request->class_id);
            $request->merge(['class' => $class->class_name, 'section' => $class->section]);
        }
        if ($area === 'certificates') {
            $certificate = Certificate::with('student')->findOrFail($id);
            abort_unless(in_array($a->role, ['Admin', 'Manager'], true) || $certificate->student?->institute === $a->institute, 403);
            if (in_array($action, ['revoke', 'reissue'], true)) {
                abort_unless(in_array($a->role, ['Admin', 'InstituteAdmin', 'Manager'], true), 403);
            }
        }
        if ($area === 'results' && $action === 'disqualify') {
            $result = AssessmentResult::with('assessment', 'student')->findOrFail($id);
            abort_unless($a->role === 'Admin' || ($result->student?->institute === $a->institute
                && ($a->role === 'InstituteAdmin' || (int) $result->assessment?->teacher_id === (int) $a->id)), 403);
        }
        return app(MobileWebContext::class)->run($request, function () use ($request, $area, $action, $id) {
            $page = app(PageController::class);
            $plans = app(TeachingPlanController::class);
            $assessments = app(AssessmentController::class);
            $feed = app(CommunityFeedController::class);
            return match ("$area/$action") {
                'students/create' => $page->storeStudent($request),
                'students/edit' => $page->updateStudent($request, $id),
                'students/delete' => $page->deleteStudent($id),
                'assessments/create' => $assessments->store($request),
                'assessments/edit' => $assessments->update($request, $id),
                'assessments/delete' => $assessments->delete($id),
                'assessments/ai-create' => $assessments->generateAiQuestionPaper($request, app(GeminiAiService::class), app(PdfTextExtractionService::class)),
                'question-papers/approve' => $assessments->approveQuestionPaper($id),
                'question-papers/reject' => $assessments->rejectQuestionPaper($id),
                'results/review' => app(AssessmentResultController::class)->reviewAnswer($request, $id),
                'results/disqualify' => $page->disqualifyResult($id),
                'certificates/approve' => $page->approveCertificate($id),
                'certificates/reject' => $page->rejectCertificate($request, $id),
                'certificates/revoke' => $page->revokeCertificate($id),
                'certificates/reissue' => $page->reissueCertificate($id),
                'teaching-plans/create' => $plans->store($request),
                'teaching-plans/template' => $plans->storeTemplate($request),
                'teaching-plans/deploy' => $plans->deployTemplates($request, app(TeachingPlanTemplateDeploymentService::class)),
                'teaching-plans/edit' => $plans->update($request, $id),
                'teaching-plans/delete' => $plans->delete($id),
                'teaching-plans/release-next' => $plans->releaseNext($id, app(TeachingPlanReleaseService::class)),
                'teaching-plans/ai-training' => $plans->deployAiTraining($request, $id),
                'teaching-plans/lagged-content' => $plans->storeLaggedContent($request, $id),
                'teaching-plans/release-check' => $plans->runReleaseCheck(app(TeachingPlanReleaseService::class)),
                'hybrid-learners/toggle' => app(IndependentLearnerController::class)->toggleStatus($id),
                'community/create' => $feed->store($request),
                'community/comment' => $feed->storeComment($request, $id),
                'community/edit-comment' => $feed->updateComment($request, $id),
                'community/delete-comment' => $feed->deleteComment($id),
                'community/approve' => $feed->approve($id),
                'community/reject' => $feed->reject($id),
                'community/delete' => $feed->delete($id),
                'community/like' => $this->like($feed, $id),
                default => abort(404, 'Unknown workflow action.'),
            };
        });
    }

    public function export(Request $request, string $area)
    {
        $this->authorizeArea($request, $area);
        $actor = $request->user();
        abort_unless(in_array($area, ['students', 'results'], true) && $actor instanceof User
            && in_array($actor->role, ['Teacher', 'STEM Engineer'], true), 403);
        $ticket = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\Cache::put('mobile-export:'.$ticket, [
            'account_id' => $actor->id, 'institute' => $actor->institute, 'area' => $area,
            'token_id' => $actor->currentAccessToken() instanceof \Laravel\Sanctum\PersonalAccessToken ? $actor->currentAccessToken()->getKey() : null,
            'filters' => $request->only(['student_class', 'student_section', 'search', 'badge', 'status', 'sort']),
        ], now()->addMinutes(5));
        return response()->json(['download_url' => \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'mobile.workflow-export', now()->addMinutes(5), ['ticket' => $ticket])]);
    }

    public function downloadExport(Request $request, string $ticket)
    {
        $data = \Illuminate\Support\Facades\Cache::get('mobile-export:'.$ticket);
        abort_unless($data, 403, 'This download has expired. Generate it again.');
        $actor = User::findOrFail($data['account_id']);
        abort_unless((bool) $actor->status && $actor->institute === $data['institute']
            && in_array($actor->role, ['Teacher', 'STEM Engineer'], true), 403);
        if ($data['token_id']) abort_unless($actor->tokens()->whereKey($data['token_id'])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists(), 403);
        $request->setUserResolver(fn () => $actor);
        $request->merge($data['filters']);
        return app(MobileWebContext::class)->run($request, fn () => $data['area'] === 'students'
            ? app(\App\Http\Controllers\TeacherStudentProfileController::class)->export($request, true)
            : app(PageController::class)->downloadTeacherResultsAiInsights($request, app(GeminiAiService::class)));
    }

    public function document(Request $request, string $area, int $id)
    {
        if ($area === 'question-papers') {
            $this->authorizeArea($request, $area);
            $assessment = Assessment::findOrFail($id);
            $variant = strtolower(pathinfo($assessment->file_path ?? '', PATHINFO_EXTENSION)) === 'pdf' ? 'file' : 'preview';
            return app(MobileWebContext::class)->run($request, fn () => app(AssessmentController::class)->showQuestionPaper($assessment, $variant));
        }
        if ($area === 'awards') {
            abort_unless($request->user() instanceof Student, 403);
            $certificate = Certificate::with('student')->where('student_id', $request->user()->id)
                ->whereIn('status', ['approved', 'Approved', 'Issued'])->findOrFail($id);
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('student.student-certificate', ['certificate' => $certificate, 'student' => $request->user()])
                ->setPaper('a4', 'landscape')->stream('certificate.pdf');
        }
        abort_unless(in_array($area, ['assessments', 'results'], true), 404);
        return app(MobileWebContext::class)->run($request, fn () => $area === 'assessments'
            ? app(AssessmentController::class)->showQuestionPaper(Assessment::findOrFail($id))
            : app(AssessmentResultController::class)->showAnswerFile(AssessmentResult::findOrFail($id)));
    }

    private function field(string $name, string $label, string $type = 'text', bool $required = false, array $options = []): array
    {
        return compact('name', 'label', 'type', 'required', 'options');
    }

    private function action(string $id, string $label, array $fields = [], array $values = [], bool $confirm = false): array
    {
        return ['id' => $id, 'label' => $label, 'fields' => $fields,
            'values' => (object) $values, 'confirm' => $confirm];
    }

    private function choices($values): array
    {
        return collect($values)->map(fn ($v, $k) => ['value' => (string) $k, 'label' => (string) $v])->values()->all();
    }

    private function enums(array $values): array
    {
        return $this->choices(array_combine($values, $values));
    }

    private function scope($query, Request $request, string $column = 'institute')
    {
        return $query->when($request->user()->role !== 'Admin', fn ($q) => $q->where($column, $request->user()->institute))
            ->when($request->user()->role === 'Admin' && $request->filled('institute'), fn ($q) => $q->where($column, $request->institute));
    }

    private function page(Request $r, string $title, $query, callable $map, array $actions = [], array $filters = [])
    {
        $page = $query->paginate(25);
        return response()->json(['title' => $title, 'records' => collect($page->items())->map($map)->values(),
            'actions' => $actions, 'filters' => $filters,
            'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]]);
    }

    private function search($query, Request $r, array $columns)
    {
        return $query->when($r->filled('search'), fn ($q) => $q->where(function ($q) use ($r, $columns) {
            foreach ($columns as $column) $q->orWhere($column, 'like', '%'.$r->string('search').'%');
        }));
    }

    private function badges(Request $r)
    {
        $query = AssessmentResult::with('assessment')->where('student_id', $r->user()->id)->whereNotNull('badge')
            ->when($r->filled('badge'), fn ($q) => $q->where('badge', $r->badge))
            ->when($r->filled('search'), fn ($q) => $q->whereHas('assessment', fn ($a) => $a->where('assessment_title', 'like', '%'.$r->search.'%')))->latest();
        return $this->page($r, 'Assessment badges', $query, fn ($result) => [
            'id' => $result->id, 'title' => $result->assessment?->assessment_title, 'status' => $result->badge.' Badge',
            'details' => $result->only(['score', 'total_marks', 'percentage', 'feedback', 'created_at']), 'actions' => [],
        ], filters: [$this->field('badge', 'Badge', 'select', options: $this->enums(['Gold', 'Silver', 'Bronze']))]);
    }

    private function students(Request $r)
    {
        $classes = SchoolClass::where('institute', $r->user()->institute)->where('status', 1)->get();
        $fields = [$this->field('student_id', 'Student ID', required: true), $this->field('name', 'Name', required: true),
            $this->field('class_id', 'Class and section', 'select', true, $this->choices($classes->mapWithKeys(fn ($c) => [$c->id => "$c->class_name $c->section"]))),
            $this->field('contact', 'Contact', required: true), $this->field('email', 'Email', 'email'), $this->field('guardian_name', 'Guardian'),
            $this->field('is_robotics_club_member', 'Robotics club', 'select', true, $this->choices([0 => 'No', 1 => 'Yes'])),
            $this->field('status', 'Status', 'select', true, $this->choices([1 => 'Active', 0 => 'Inactive'])),
            $this->field('password', 'Password', 'password')];
        $query = Student::where('institute', $r->user()->institute)->when($r->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$r->search.'%')->orWhere('student_id', 'like', '%'.$r->search.'%')->orWhere('class', 'like', '%'.$r->search.'%')->orWhere('section', 'like', '%'.$r->search.'%')))->orderBy('name');
        foreach (['student_class' => 'class', 'student_section' => 'section', 'status' => 'status'] as $filter => $column) {
            $value = $r->input($filter, $r->input($column));
            if ($value !== null && $value !== '') $query->where($column, $value);
        }
        return $this->page($r, 'Students', $query, function ($s) use ($fields, $classes) {
            $values = $s->only(['student_id', 'name', 'contact', 'email', 'guardian_name', 'status', 'is_robotics_club_member']);
            $values['class_id'] = $classes->first(fn ($c) => $c->class_name === $s->class && $c->section === $s->section)?->id;
            return ['id' => $s->id, 'title' => $s->name, 'subtitle' => "$s->student_id | $s->class $s->section", 'status' => $s->status ? 'Active' : 'Inactive',
                'details' => $values, 'actions' => [$this->action('edit', 'Edit student', $fields, $values), $this->action('delete', 'Delete student', confirm: true)]];
        }, [$this->action('create', 'Add student', array_map(fn ($f) => $f['name'] === 'password' ? array_replace($f, ['required' => true]) : $f, $fields), ['status' => 1, 'is_robotics_club_member' => 0])], [
            $this->field('student_class', 'Class', 'select', options: $this->enums($classes->pluck('class_name')->unique()->all())),
            $this->field('student_section', 'Section', 'select', options: $this->enums($classes->when($r->filled('student_class'), fn ($list) => $list->where('class_name', $r->student_class))->pluck('section')->filter()->unique()->all())) + ['depends_on' => 'student_class'],
            $this->field('status', 'Status', 'select', options: $this->choices([1 => 'Active', 0 => 'Inactive'])),
        ]);
    }

    private function assessments(Request $r)
    {
        $classes = SchoolClass::where('institute', $r->user()->institute)->where('status', 1)->get()->map(fn ($c) => trim("$c->class_name $c->section"))->unique()->values()->all();
        $fields = [$this->field('assessment_title', 'Title', required: true), $this->field('assessment_type', 'Type', 'select', true, $this->enums(['Student'])),
            $this->field('assigned_class', 'Class', 'select', true, $this->enums($classes)), $this->field('assessment_category', 'Category', 'select', true, $this->enums(['Monthly', 'Annual'])),
            $this->field('assessment_date', 'Date', 'date', true), $this->field('start_time', 'Start time (HH:mm)'), $this->field('end_time', 'End time (HH:mm)'),
            $this->field('total_marks', 'Total marks', 'number', true), $this->field('duration', 'Duration (minutes)', 'number', true),
            $this->field('status', 'Status', 'select', true, $this->choices([1 => 'Active', 0 => 'Inactive']))];
        $pdf = $this->field('file', 'Question paper (PDF)', 'pdf', true);
        $content = $this->field('content_ids', 'Lesson content', 'multi', true, $this->choices(Content::where('institute', $r->user()->institute)->where('status', 1)->pluck('content_title', 'id')));
        $query = $this->search(Assessment::where('teacher_id', $r->user()->id)->where('institute', $r->user()->institute), $r, ['assessment_title'])->latest();
        foreach (['assigned_class', 'assessment_category', 'question_paper_status'] as $column) if ($r->filled($column)) $query->where($column, $r->input($column));
        return $this->page($r, 'Assessments', $query, function ($a) use ($fields, $pdf) {
            $values = $a->only(array_column($fields, 'name'));
            $values['assessment_date'] = $a->assessment_date ? \Carbon\Carbon::parse($a->assessment_date)->format('Y-m-d') : null;
            foreach (['start_time', 'end_time'] as $time) $values[$time] = substr((string) $a->$time, 0, 5);
            return ['id' => $a->id, 'title' => $a->assessment_title, 'subtitle' => "$a->assigned_class | $a->assessment_category", 'status' => $a->question_paper_status,
                'document' => "/api/workflows/assessments/$a->id/document", 'details' => $values,
                'actions' => [$this->action('edit', 'Edit assessment', [...$fields, array_replace($pdf, ['required' => false])], $values), $this->action('delete', 'Delete assessment', confirm: true)]];
        }, [$this->action('create', 'Upload question paper', [...$fields, $pdf], ['status' => 1, 'assessment_type' => 'Student']), $this->action('ai-create', 'Generate AI question paper', [...$fields, $content], ['status' => 1, 'assessment_type' => 'Student'])], [
            $this->field('assigned_class', 'Class', 'select', options: $this->enums($classes)),
            $this->field('assessment_category', 'Category', 'select', options: $this->enums(['Monthly', 'Annual'])),
            $this->field('question_paper_status', 'Question paper status', 'select', options: $this->enums(['Pending Approval', 'Approved', 'Rejected'])),
        ]);
    }

    private function questionPapers(Request $r)
    {
        $query = $this->search($this->scope(Assessment::with('teacher', 'questionPaperReviewer')->whereNotNull('file_path'), $r), $r, ['assessment_title']);
        foreach (['question_class' => 'assigned_class', 'question_status' => 'question_paper_status'] as $filter => $column) {
            if ($r->filled($filter)) $query->where($column, $r->input($filter));
        }
        $classes = $this->scope(SchoolClass::query(), $r)->get()->map(fn ($c) => trim("$c->class_name $c->section"))->filter()->unique()->sort()->values()->all();
        return $this->page($r, 'Question papers', $query->orderBy('institute')->orderBy('assigned_class')->latest()->orderByDesc('id'), function ($a) {
            $actions = $a->question_paper_status === 'Approved' ? [] : [$this->action('approve', 'Approve', confirm: true)];
            $actions[] = $this->action('reject', 'Reject', confirm: true);
            return ['id' => $a->id, 'title' => $a->assessment_title, 'subtitle' => "$a->assigned_class | $a->institute", 'status' => $a->question_paper_status,
                'document' => "/api/workflows/question-papers/$a->id/document",
                'details' => $a->only(['assessment_category', 'assessment_date', 'total_marks', 'duration', 'question_paper_feedback', 'question_paper_reviewed_at']) + [
                    'stem_engineer' => $a->teacher?->name, 'reviewer' => $a->questionPaperReviewer?->name,
                ], 'actions' => $actions];
        }, filters: [
            ...($r->user()->role === 'Admin' ? [$this->field('institute', 'Institute', 'select', options: $this->enums(Institute::where('status', 1)->orderBy('institute_name')->pluck('institute_name')->all()))] : []),
            $this->field('question_class', 'Class', 'select', options: $this->enums($classes)) + ['depends_on' => 'institute'],
            $this->field('question_status', 'Status', 'select', options: $this->enums(['Pending Approval', 'Approved', 'Rejected'])),
        ]);
    }

    private function results(Request $r)
    {
        $a = $r->user();
        $query = AssessmentResult::with('student', 'assessment')->whereHas('assessment', function ($q) use ($r, $a) {
            $this->scope($q, $r);
            if (in_array($a->role, ['Teacher', 'STEM Engineer'], true)) $q->where('teacher_id', $a->id);
        })->whereHas('student', fn ($s) => $this->scope($s, $r))->latest();
        $query->when($r->filled('search'), fn ($q) => $q->where(fn ($q) => $q
            ->whereHas('student', fn ($s) => $s->where('name', 'like', '%'.$r->search.'%')->orWhere('student_id', 'like', '%'.$r->search.'%'))
            ->orWhereHas('assessment', fn ($a) => $a->where('assessment_title', 'like', '%'.$r->search.'%'))
            ->orWhere('badge', 'like', '%'.$r->search.'%')));
        foreach (['status', 'badge'] as $column) if ($r->filled($column)) $query->where($column, $r->input($column));
        foreach (['student_class' => 'class', 'student_section' => 'section'] as $filter => $column) {
            if ($r->filled($filter)) $query->whereHas('student', fn ($s) => $s->where($column, $r->input($filter)));
        }
        $query->reorder();
        match ($r->input('sort')) {
            'highest' => $query->orderByDesc('percentage'), 'lowest' => $query->orderBy('percentage'),
            'oldest' => $query->oldest(), default => $query->latest(),
        };
        $query->orderByDesc('id');
        $roster = $this->scope(Student::query(), $r);
        $classOptions = (clone $roster)->pluck('class')->filter()->unique()->sort()->values()->all();
        $sectionOptions = $roster->when($r->filled('student_class'), fn ($s) => $s->where('class', $r->student_class))->pluck('section')->filter()->unique()->sort()->values()->all();
        return $this->page($r, 'Assessment review', $query, function ($result) {
            return ['id' => $result->id, 'title' => $result->student?->name, 'subtitle' => $result->assessment?->assessment_title,
                'status' => $result->status, 'details' => $result->only(['answer_text', 'score', 'total_marks', 'feedback', 'percentage', 'badge']),
                'document' => $result->answer_file_path ? "/api/workflows/results/$result->id/document" : null,
                'actions' => [$this->action('review', 'Evaluate answer', [$this->field('marks_awarded', 'Marks awarded', 'number', true), $this->field('feedback', 'Feedback', 'textarea'), $this->field('passed', 'Outcome', 'select', true, $this->choices([1 => 'Pass', 0 => 'Fail']))], ['marks_awarded' => $result->score, 'feedback' => $result->feedback, 'passed' => (int) $result->passed]),
                    $this->action('disqualify', 'Disqualify', [$this->field('reason', 'Reason', 'textarea', true)], confirm: true)]];
        }, filters: [
            $this->field('student_class', 'Class', 'select', options: $this->enums($classOptions)),
            $this->field('student_section', 'Section', 'select', options: $this->enums($sectionOptions)) + ['depends_on' => 'student_class'],
            $this->field('badge', 'Badge', 'select', options: $this->enums(['Gold', 'Silver', 'Bronze'])),
            $this->field('status', 'Status', 'select', options: $this->enums(['Pending Review', 'Completed'])),
            $this->field('sort', 'Sort', 'select', options: $this->choices(['highest' => 'Highest', 'lowest' => 'Lowest', 'latest' => 'Latest', 'oldest' => 'Oldest'])),
        ]);
    }

    private function certificates(Request $r, string $area)
    {
        $student = $r->user() instanceof Student;
        $admin = !$student && in_array($r->user()->role, ['Admin', 'InstituteAdmin'], true);
        $query = Certificate::with('student', 'course')->when($student, fn ($q) => $q->where('student_id', $r->user()->id))
            ->when(!$student, fn ($q) => $q->whereHas('student', fn ($s) => $this->scope($s, $r)))->latest()->orderByDesc('id');
        $query->when($r->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('certificate_code', 'like', '%'.$r->search.'%')
            ->orWhereHas('student', fn ($s) => $s->where('name', 'like', '%'.$r->search.'%'))));
        $status = $r->input('certificate_status', $r->input('status'));
        if ($status !== null && $status !== '') $query->where('status', $status);
        foreach (['student_class' => 'class', 'student_section' => 'section'] as $filter => $column) {
            if ($r->filled($filter)) $query->whereHas('student', fn ($s) => $s->where($column, $r->input($filter)));
        }
        $filters = [$this->field('status', 'Status', 'select', options: $this->enums(['Pending', 'Pending Approval', 'pending_admin_approval', 'approved', 'Rejected', 'Revoked', 'Issued']))];
        if (!$student) {
            $roster = $this->scope(Student::query(), $r);
            $classes = (clone $roster)->whereNotNull('class')->orderBy('class')->pluck('class')->filter()->unique()->values()->all();
            $sections = (clone $roster)->when($r->filled('student_class'), fn ($q) => $q->where('class', $r->student_class))
                ->whereNotNull('section')->orderBy('section')->pluck('section')->filter()->unique()->values()->all();
            $filters = [
                ...($r->user()->role === 'Admin' ? [$this->field('institute', 'Institute', 'select', options: $this->enums(Institute::where('status', 1)->orderBy('institute_name')->pluck('institute_name')->all()))] : []),
                $this->field('student_class', 'Class', 'select', options: $this->enums($classes)) + ['depends_on' => 'institute'],
                $this->field('student_section', 'Section', 'select', options: $this->enums($sections)) + ['depends_on' => 'student_class'],
                $this->field('certificate_status', 'Status', 'select', options: $this->choices(['Pending Approval' => 'Pending Approval', 'pending_admin_approval' => 'Pending Approval (legacy)', 'approved' => 'Approved', 'Revoked' => 'Revoked'])),
            ];
        }
        return $this->page($r, $student ? 'Certificates and badges' : 'Certificates', $query, function ($c) use ($student, $admin) {
            $actions = [];
            if ($admin) {
                if (in_array($c->status, ['Pending', 'Pending Approval', 'pending_admin_approval'], true)) $actions[] = $this->action('approve', 'Approve', confirm: true);
                $actions[] = $c->status === 'Revoked' ? $this->action('reissue', 'Reissue', confirm: true) : $this->action('revoke', 'Revoke', confirm: true);
            } elseif (!$student) {
                $actions = [$this->action('approve', 'Approve', confirm: true), $this->action('reject', 'Reject', [$this->field('rejection_reason', 'Reason', 'textarea', true)])];
            }
            return ['id' => $c->id, 'title' => $student ? $c->certificate_code : $c->student?->name, 'subtitle' => $c->certificate_type, 'status' => $c->status,
                'document' => $student && in_array(strtolower($c->status), ['approved', 'issued'], true) ? "/api/workflows/awards/$c->id/document" : null,
                'details' => $c->only(['certificate_code', 'badge_count', 'final_score', 'final_grade', 'final_classification', 'issued_date', 'rejection_reason']) + [
                    'course' => $c->course?->course_title ?? 'Program Completion', 'institute' => $c->student?->institute, 'class' => $c->student?->class, 'section' => $c->student?->section,
                ], 'actions' => $actions];
        }, filters: $filters);
    }

    private function plans(Request $r)
    {
        $admin = $r->user()->role === 'Admin';
        $courses = $this->scope(Course::where('status', 1), $r)->get();
        $institutes = Institute::where('status', 1)->pluck('institute_name', 'id');
        $fields = [$this->field('course_id', 'Course', 'select', true, $this->choices($courses->pluck('course_title', 'id'))), $this->field('class', 'Class', required: true),
            $this->field('section', 'Section'), $this->field('start_date', 'Start date', 'date', true),
            $this->field('release_day', 'Release day', 'select', true, $this->enums(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'])),
            $this->field('contents_per_week', 'Topics per week', 'number', true),
            $this->field('release_policy', 'Release policy', 'select', true, $this->choices(['scheduled_weekly_release' => 'Weekly schedule', 'release_next_only_if_previous_completed' => 'Previous week must be complete'])),
            $this->field('status', 'Status', 'select', true, $this->enums(['active', 'inactive'])), $this->field('remarks', 'Remarks', 'textarea')];
        $defaults = ['release_day' => 'Friday', 'contents_per_week' => 2, 'release_policy' => 'scheduled_weekly_release', 'status' => 'active'];
        $classes = $this->scope(SchoolClass::where('status', 1), $r)->orderBy('class_name')->get();
        $liveCourses = $courses->reject(fn ($c) => (bool) $c->is_template_source);
        $classChoices = fn ($items) => $this->choices($items->mapWithKeys(fn ($c) => [$c->id => trim("$c->class_name $c->section")." | $c->institute"]));
        $liveFields = array_values(array_filter(array_map(function ($f) use ($liveCourses, $classes, $classChoices) {
            if ($f['name'] === 'section') return null;
            if ($f['name'] === 'course_id') return array_replace($f, ['options' => $this->choices($liveCourses->pluck('course_title', 'id'))]);
            if ($f['name'] === 'class') return $this->field('class_id', 'Class and section', 'select', true, $classChoices($classes)) + [
                'depends_on' => 'course_id', 'options_by_value' => $liveCourses->mapWithKeys(fn ($c) => [(string) $c->id => $classChoices($classes->where('institute', $c->institute))])->all(),
            ];
            return $f;
        }, $fields)));
        $actions = [$this->action('create', 'Create teaching plan', $liveFields, $defaults)];
        if ($admin) {
            $templateFields = array_map(fn ($f) => $f['name'] === 'course_id' ? array_replace($f, ['options' => $this->choices($courses->where('is_template_source', true)->pluck('course_title', 'id'))]) : $f,
                array_values(array_filter($fields, fn ($f) => $f['name'] !== 'start_date')));
            $actions[] = $this->action('template', 'Create template', [$this->field('title', 'Template title', required: true), ...$templateFields], $defaults);
            $deploymentFields = [$this->field('selected_institute_ids', 'Institutes', 'multi', true, $this->choices($institutes))];
            foreach ($institutes as $id => $name) {
                $classes = SchoolClass::where('institute', $name)->where('status', 1)->get()->map(fn ($c) => trim("$c->class_name $c->section"))->unique()->values()->all();
                $deploymentFields[] = $this->field("classes_by_institute[$id]", "$name classes", 'multi', false, $this->enums($classes));
            }
            $actions[] = $this->action('deploy', 'Deploy / sync templates', $deploymentFields);
        }
        if (!app()->environment('production')) $actions[] = $this->action('release-check', 'Run release check', confirm: true);
        $query = $this->search($this->scope(TeachingPlan::with('course', 'weeks'), $r), $r, ['title', 'class', 'institute'])->latest();
        foreach (['class', 'status', 'is_template'] as $column) if ($r->filled($column)) $query->where($column, $r->input($column));
        return $this->page($r, 'Teaching plan controls', $query, function ($p) use ($admin, $institutes) {
            $actions = [$this->action('edit', 'Edit plan', [
                $this->field('title', 'Plan title'),
                $this->field('start_date', 'Start date', 'date'),
                $this->field('release_day', 'Release day', 'select', true, $this->enums(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'])),
                $this->field('status', 'Status', 'select', true, $this->enums(['active', 'inactive', 'completed'])),
                $this->field('remarks', 'Remarks', 'textarea'),
            ], [
                'title' => $p->title,
                'start_date' => filled($p->start_date) ? \Carbon\Carbon::parse($p->start_date)->format('Y-m-d') : null,
                'release_day' => $p->release_day ?: 'Friday',
                'status' => $p->status,
                'remarks' => $p->remarks,
            ])];
            if (!$p->is_template) {
                $actions[] = $this->action('release-next', 'Release next week', confirm: true);
                $ai = $admin ? [$this->field('selected_institute_ids', 'Institutes', 'multi', true, $this->choices($institutes))] : [];
                $actions[] = $this->action('ai-training', 'AI training schedule', [...$ai, $this->field('ai_training_start_date', 'Start date (empty disables)', 'date')], ['ai_training_start_date' => $p->ai_training_start_date?->format('Y-m-d')]);
                $lessons = CourseContent::with('content')->where('course_id', $p->course_id)->where('status', 'active')->get()->mapWithKeys(fn ($c) => [$c->id => $c->content?->content_title ?? "Lesson $c->id"]);
                $actions[] = $this->action('lagged-content', 'Release catch-up topics', [$this->field('course_content_ids', 'Topics', 'multi', true, $this->choices($lessons))]);
            }
            $actions[] = $this->action('delete', 'Delete plan', confirm: true);
            return ['id' => $p->id, 'title' => $p->title ?: $p->course?->course_title, 'subtitle' => "$p->institute | $p->class $p->section", 'status' => $p->status,
                'details' => ['template' => (bool) $p->is_template, 'start_date' => $p->start_date, 'release_policy' => $p->release_policy, 'weeks' => $p->weeks->map(fn ($w) => "Week $w->week_number: $w->status ($w->release_date)")->implode("\n")], 'actions' => $actions];
        }, $actions, [$this->field('status', 'Status', 'select', options: $this->enums(['active', 'inactive', 'completed'])),
            $this->field('is_template', 'Plan type', 'select', options: $this->choices([0 => 'Live plans', 1 => 'Templates'])),
            ...($admin ? [$this->field('institute', 'Institute', 'select', options: $this->enums($institutes->values()->all()))] : [])]);
    }

    private function learners(Request $r)
    {
        return $this->page($r, 'Hybrid learners', IndependentLearner::with('enrollments.course', 'certificates.course')->when($r->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$r->search.'%')->orWhere('email', 'like', '%'.$r->search.'%')))->latest(), fn ($l) => [
            'id' => $l->id, 'title' => $l->name, 'subtitle' => $l->email, 'status' => $l->status ? 'Active' : 'Inactive',
            'details' => ['courses' => $l->enrollments->map(fn ($e) => $e->course?->course_title.' - '.($e->is_completed ? 'Completed' : 'In progress'))->implode("\n"), 'certificates' => $l->certificates->map(fn ($c) => $c->certificate_code.' - '.$c->status)->implode("\n")],
            'actions' => [$this->action('toggle', $l->status ? 'Deactivate' : 'Activate', confirm: true)]]);
    }

    private function profile(Request $r)
    {
        $a = $r->user();
        $fields = [$this->field('linkedin_url', 'LinkedIn URL'), $this->field('profile_image', 'Profile photo', 'image')];
        $values = $a->only(['linkedin_url']);
        if ($a instanceof User) {
            foreach (['user_id' => 'Engineer ID', 'name' => 'Name', 'email' => 'Email', 'qualification' => 'Qualification', 'designation' => 'Designation', 'joined_on' => 'Joined on'] as $name => $label) {
                $fields[] = $this->field($name, $label, $name === 'joined_on' ? 'date' : 'text', in_array($name, ['user_id', 'name', 'email', 'qualification'], true));
                $values[$name] = $name === 'joined_on' && $a->$name ? \Carbon\Carbon::parse($a->$name)->format('Y-m-d') : $a->$name;
            }
        }
        return response()->json(['title' => 'Profile', 'records' => [['id' => $a->id, 'title' => $a->name, 'subtitle' => $a->email,
            'image' => $this->publicProfileImageUrl($a->profile_image ?? null), 'details' => $values,
            'actions' => [$this->action('edit', 'Edit profile', $fields, $values), ...($a instanceof Student ? [$this->action('remove-photo', 'Remove photo', confirm: true)] : [])]]], 'actions' => [], 'filters' => []]);
    }

    private function updateProfile(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof User || $account instanceof Student, 403);

        $rules = [
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($account instanceof User) {
            $rules += [
                'user_id' => ['required', 'string', 'max:255', Rule::unique('users', 'user_id')->ignore($account->id)],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account->id)],
                'designation' => ['nullable', 'string', 'max:255'],
                'qualification' => ['required', 'string', 'max:255'],
                'joined_on' => ['nullable', 'date'],
            ];
        }

        $validated = $request->validate($rules);
        $updates = collect($validated)
            ->except('profile_image')
            ->all();

        if ($request->hasFile('profile_image')) {
            $this->deleteProfileFile($account->profile_image ?? null);
            $updates['profile_image'] = $request->file('profile_image')->store('profile-images', 'public');
        }

        $account->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'avatar' => $this->publicProfileImageUrl($account->fresh()->profile_image ?? null),
        ]);
    }

    private function removeProfilePhoto(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof Student, 403);

        $this->deleteProfileFile($account->profile_image);
        $account->update(['profile_image' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Profile image removed successfully.',
        ]);
    }

    private function deleteProfileFile(?string $path): void
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $path = preg_replace('#^/?storage/#', '', $path);
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function publicProfileImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/') || str_starts_with($path, 'storage/')) {
            return url('/' . ltrim($path, '/'));
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }

    private function community(Request $r)
    {
        return app(MobileWebContext::class)->run($r, function () use ($r) {
            $data = app(CommunityFeedController::class)->index($r)->getData();
            $actor = $data['actor'];
            $records = $data['posts']->getCollection()->map(function ($p) use ($actor) {
                $own = $p->author_type === $actor['type'] && (int) $p->author_id === (int) $actor['id'];
                $admin = in_array($actor['type'], ['Admin', 'InstituteAdmin'], true);
                $actions = [];
                if ($p->status === 'Approved') $actions = [$this->action('like', 'Like / unlike'), $this->action('comment', 'Add comment', [$this->field('body', 'Comment', 'textarea', true)])];
                if ($p->status === 'Pending' && ($admin || ($actor['type'] === 'Teacher' && $p->author_type === 'Student'))) $actions = [...$actions, $this->action('approve', 'Approve'), $this->action('reject', 'Reject')];
                if ($admin || ($own && $p->status !== 'Approved')) $actions[] = $this->action('delete', 'Delete post', confirm: true);
                $comments = $p->comments->map(fn ($c) => ['id' => $c->id, 'body' => $c->body, 'editable' => $c->commenter_type === $actor['type'] && (int) $c->commenter_id === (int) $actor['id']]);
                return ['id' => $p->id, 'title' => $p->title, 'subtitle' => $p->body, 'status' => $p->status, 'image' => $p->image_path ? Storage::disk('public')->url($p->image_path) : null,
                    'liked' => $p->isLikedBy($actor['type'], $actor['id']),
                    'attachment' => $p->attachment_path ? Storage::disk('public')->url($p->attachment_path) : null, 'details' => ['type' => $p->post_type, 'likes' => $p->likes->count()], 'comments' => $comments, 'actions' => $actions];
            });
            return response()->json(['title' => 'Community', 'records' => $records, 'filters' => [$this->field('tab', 'View', 'select', false, $this->enums($actor['type'] === 'Student' ? ['feed','profile'] : ['feed','profile','approvals'])), $this->field('type', 'Post type', 'select', false, $this->enums($data['postTypes']))],
                'pagination' => ['page' => $data['posts']->currentPage(), 'last_page' => $data['posts']->lastPage(), 'total' => $data['posts']->total()],
                'actions' => [$this->action('create', 'New post', [$this->field('title', 'Title', required: true), $this->field('post_type', 'Type', 'select', true, $this->enums($data['postTypes'])), $this->field('body', 'Post', 'textarea', true), $this->field('image', 'Photo', 'image'), $this->field('attachment', 'Attachment', 'file')])]]);
        });
    }

    private function like(CommunityFeedController $feed, int $id)
    {
        $response = $feed->toggleLike($id);
        session()->flash('success', 'Reaction updated.');
        return $response;
    }
}
