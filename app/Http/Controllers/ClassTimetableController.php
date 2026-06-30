<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassTimetable;
use App\Models\SchoolClass;
use Carbon\Carbon;
use App\Models\Content;


class ClassTimetableController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->where('status', 1)
            ->orderBy('class_name')
            ->get();

        $weekOffset = (int) request('week', 0);

        $weekStart = now()
            ->startOfWeek()
            ->addWeeks($weekOffset);

        $weekEnd = now()
            ->endOfWeek()
            ->addWeeks($weekOffset);

        $contents = Content::when(session('user_role') == 'InstituteAdmin', function ($query) {
            $query->where('institute', session('user_institute'));
        })
        ->where('status', 1)
        ->orderBy('lesson_order')
        ->get();

        $timetables = ClassTimetable::with([
                'schoolClass',
                'content'
            ])
            ->whereBetween('session_date', [
                $weekStart->format('Y-m-d'),
                $weekEnd->format('Y-m-d')
            ])
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->whereHas('schoolClass', function ($q) {
                    $q->where('institute', session('user_institute'));
                });
            })
            ->orderBy('session_date')
            ->orderBy('from_time')
            ->get();

        $groupedTimetables = $timetables->groupBy('day');

        return view('class-timetable', compact('classes','contents','timetables','groupedTimetables','weekOffset','weekStart','weekEnd'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'session_date' => 'required|date',
            'day_type' => 'required|in:Working Day,Holiday',
            'content_id' => 'nullable|exists:contents,id',
            'from_time' => 'required',
            'to_time' => 'required',
            'status' => 'Scheduled',
        ]);

        $class = SchoolClass::findOrFail($request->class_id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $class->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        ClassTimetable::create([
            'class_id' => $request->class_id,

            'content_id' => $request->content_id,

            'session_date' => $request->session_date,

            'day' => Carbon::parse(
                $request->session_date
            )->format('l'),

            'day_type' => $request->day_type,

            'from_time' => $request->from_time,

            'to_time' => $request->to_time,
        ]);

        return redirect()->back()
            ->with('success', 'Timetable entry created successfully.');
    }

    public function copyWeekToNext()
    {
        $weekOffset = (int) request('week', 0);

        $sourceWeekStart = now()
            ->startOfWeek()
            ->addWeeks($weekOffset);

        $sourceWeekEnd = now()
            ->endOfWeek()
            ->addWeeks($weekOffset);

        $sourceEntries = ClassTimetable::with('schoolClass')
            ->whereBetween('session_date', [
                $sourceWeekStart->format('Y-m-d'),
                $sourceWeekEnd->format('Y-m-d')
            ])
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->whereHas('schoolClass', function ($q) {
                    $q->where('institute', session('user_institute'));
                });
            })
            ->get();

        if ($sourceEntries->count() == 0) {

            return redirect()->back()
                ->with('error', 'No timetable found for the selected week.');

        }

        foreach ($sourceEntries as $entry) {

            $newDate = Carbon::parse($entry->session_date)
                ->addWeek();

            ClassTimetable::updateOrCreate(
                [
                    'class_id' => $entry->class_id,
                    'session_date' => $newDate->format('Y-m-d'),
                ],
                [
                    'content_id' => $entry->content_id,
                    'day' => $newDate->format('l'),
                    'day_type' => $entry->day_type,
                    'from_time' => $entry->from_time,
                    'to_time' => $entry->to_time,

                    // Reset copied sessions
                    'status' => 'Scheduled',
                ]
            );
        }

        return redirect()->back()
            ->with('success', 'Timetable copied successfully to next week.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'content_id' => 'nullable|exists:contents,id',
            'session_date' => 'required|date',
            'from_time' => 'required',
            'to_time' => 'required',
            'day_type' => 'required|in:Working Day,Holiday',
        ]);

        $timetable = ClassTimetable::with('schoolClass')->findOrFail($id);

        $class = SchoolClass::findOrFail($request->class_id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $class->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $timetable->update([
            'class_id' => $request->class_id,
            'content_id' => $request->content_id,
            'session_date' => $request->session_date,
            'day' => Carbon::parse($request->session_date)->format('l'),
            'day_type' => $request->day_type,
            'from_time' => $request->from_time,
            'to_time' => $request->to_time,
            'status' => 'Scheduled',
        ]);

        return redirect()->back()
            ->with('success', 'Timetable entry updated successfully.');
    }

    public function delete($id)
    {
        $timetable = ClassTimetable::with('schoolClass')->findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $timetable->schoolClass->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $timetable->delete();

        return redirect()->back()
            ->with('success', 'Timetable entry deleted successfully.');
    }
}