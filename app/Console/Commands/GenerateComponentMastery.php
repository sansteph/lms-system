<?php

namespace App\Console\Commands;

use App\Http\Controllers\PageController;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Student;
use App\Services\Ai\GeminiAiService;
use App\Services\ComponentMasteryService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class GenerateComponentMastery extends Command
{
    protected $signature = 'component-mastery:generate {--student= : Process one student ID}';
    protected $description = 'Scan completed courses and prepare microcontroller/microprocessor assessments, including existing students';

    public function handle(ComponentMasteryService $mastery, GeminiAiService $ai, PageController $page): int
    {
        $failed = 0;
        Student::where('status', 1)->when($this->option('student'), fn ($q) => $q->whereKey($this->option('student')))
            ->chunkById(100, function ($students) use ($mastery, $ai, $page, &$failed) {
                foreach ($students as $student) {
                    try {
                        $offers = $mastery->offers($student, $ai);
                        foreach ($offers as $offer) {
                            $submitted = AssessmentResult::where('student_id', $student->id)
                                ->whereHas('assessment', fn ($q) => $q->where('assessment_category', 'Component Mastery')
                                    ->where('component_key', $offer['component_key']))->exists();
                            if ($submitted) continue;
                            $page->generateStudentComponentAssessment(new Request(), $offer['component_key'], $ai, $student);
                            $ready = Assessment::where('institute', $student->institute)
                                ->whereIn('assigned_class', $this->studentClassOptions($student))
                                ->where('assessment_category', 'Component Mastery')->where('component_key', $offer['component_key'])
                                ->where('status', 1)->where('question_paper_status', 'Approved')->whereNotNull('file_path')->exists();
                            if (!$ready) throw new \RuntimeException('Assessment generation is not ready; see the application log.');
                        }
                        $page->notifyStudentComponentMasteryOffers($student, $offers);
                    } catch (\Throwable $error) {
                        report($error);
                        $failed++;
                        $this->warn('Student '.$student->id.': '.$error->getMessage());
                    }
                }
            });
        $this->info('Component mastery processing finished. Failures: '.$failed);
        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function studentClassOptions(Student $student): array
    {
        return collect([
            $student->class,
            trim((string) $student->class.' '.(string) $student->section),
        ])
            ->map(fn ($value) => preg_replace('/\s+/', ' ', trim((string) $value)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
