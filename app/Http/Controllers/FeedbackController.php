<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    private const RECIPIENT_EMAIL = 'tinkedgemain@gmail.com';
    private const CC_EMAILS = [
        'support@tinkedge.com',
        'shah@tinkedge.com',
    ];

    public function teacherCreate()
    {
        return view('feedback.create', [
            'audience' => 'teacher',
            'submitRoute' => route('teacher.feedback.store'),
            'backRoute' => route('teacher.dashboard'),
        ]);
    }

    public function teacherStore(Request $request)
    {
        $teacher = User::whereIn('role', ['Teacher', 'STEM Engineer'])->findOrFail(session('user_id'));

        return $this->sendFeedback($request, 'STEM Engineer', [
            'Name' => $teacher->name,
            'ID' => $teacher->user_id,
            'Email' => $teacher->email ?: 'Not provided',
            'Phone' => $teacher->phone ?: 'Not provided',
            'Institute' => $teacher->institute ?: 'Not assigned',
        ], route('teacher.feedback'));
    }

    public function studentCreate()
    {
        return view('feedback.create', [
            'audience' => 'student',
            'submitRoute' => route('student.feedback.store'),
            'backRoute' => route('student.dashboard'),
        ]);
    }

    public function studentStore(Request $request)
    {
        $student = Student::findOrFail(session('student_id'));

        return $this->sendFeedback($request, 'Student', [
            'Name' => $student->name,
            'ID' => $student->student_id,
            'Email' => $student->email ?: 'Not provided',
            'Contact' => $student->contact ?: 'Not provided',
            'Class' => trim(($student->class ?: '') . ' ' . ($student->section ?: '')) ?: 'Not assigned',
            'Institute' => $student->institute ?: 'Not assigned',
        ], route('student.feedback'));
    }

    public function panelCreate(Request $request)
    {
        $audience = $request->route('audience', 'manager');

        return view('feedback.create', [
            'audience' => 'admin',
            'submitRoute' => route($audience . '.feedback.store'),
            'backRoute' => route($audience . '.dashboard'),
        ]);
    }

    public function panelStore(Request $request)
    {
        $user = User::findOrFail(session('user_id'));
        $audience = $request->route('audience', 'manager');

        return $this->sendFeedback($request, $user->role ?: 'Admin Panel', [
            'Name' => $user->name,
            'ID' => $user->user_id,
            'Email' => $user->email ?: 'Not provided',
            'Phone' => $user->phone ?: 'Not provided',
            'Institute' => $user->institute ?: 'All Institutes',
        ], route($audience . '.feedback'));
    }

    private function sendFeedback(Request $request, string $senderType, array $senderDetails, string $redirectRoute)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:General,Learning Content,Assessment,Session,Technical Issue,Other',
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:5000',
        ]);

        $replyTo = filter_var($senderDetails['Email'] ?? null, FILTER_VALIDATE_EMAIL)
            ? $senderDetails['Email']
            : null;

        try {
            Mail::send('emails.feedback-submitted', [
                'senderType' => $senderType,
                'senderDetails' => $senderDetails,
                'category' => $validated['category'],
                'feedbackSubject' => $validated['subject'],
                'feedbackMessage' => $validated['message'],
                'submittedAt' => now()->format('d M Y, h:i A'),
            ], function ($message) use ($validated, $replyTo) {
                $message->to(self::RECIPIENT_EMAIL)
                    ->cc(self::CC_EMAILS)
                    ->subject('InnovatEdge Feedback: ' . Str::limit($validated['subject'], 110));

                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Feedback could not be sent right now. Please try again later.');
        }

        return redirect($redirectRoute)
            ->with('success', 'Feedback submitted successfully.');
    }
}
