<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LessonProgress;

class LessonProgressController extends Controller
{
    public function markComplete($contentId)
    {
        $studentId = session('student_id');

        $progress = LessonProgress::where('student_id', $studentId)
            ->where('content_id', $contentId)
            ->first();

        if (!$progress) {

            LessonProgress::create([

                'student_id' => $studentId,

                'content_id' => $contentId,

                'is_completed' => true,

                'completed_at' => now()

            ]);

        } else {

            $progress->update([

                'is_completed' => true,

                'completed_at' => now()

            ]);
        }

        return redirect()->back()
            ->with('success', 'Lesson marked as completed');
    }
}