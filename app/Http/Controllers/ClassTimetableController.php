<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassTimetable;
use App\Models\SchoolClass;
use Carbon\Carbon;

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

        $timetables = ClassTimetable::with('schoolClass')
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->whereHas('schoolClass', function ($q) {
                    $q->where('institute', session('user_institute'));
                });
            })
            ->latest('session_date')
            ->get();

        return view('class-timetable', compact('classes', 'timetables'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'session_date' => 'required|date',
            'day_type' => 'required|in:Working Day,Holiday',

            'from_time' => 'required',
            'to_time' => 'required',
        ]);

        $class = SchoolClass::findOrFail($request->class_id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $class->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'session_date' => 'required|date',
            'from_time' => 'required',
            'to_time' => 'required',
            'day_type' => 'required|in:Working Day,Holiday',
        ]);

        ClassTimetable::create([
            'class_id' => $request->class_id,

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

    public function copyLastWeek()
    {

        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        $lastWeekEntries = ClassTimetable::with('schoolClass')
            ->whereBetween('session_date', [$lastWeekStart, $lastWeekEnd])
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->whereHas('schoolClass', function ($q) {
                    $q->where('institute', session('user_institute'));
                });
            })
            ->get();

        if ($lastWeekEntries->count() == 0) {
            return redirect()->back()
                ->with('error', 'No timetable found for last week.');
        }


        foreach ($lastWeekEntries as $entry) {
            $newDate = Carbon::parse($entry->session_date)->addWeek();

            ClassTimetable::updateOrCreate(
                [
                    'class_id' => $entry->class_id,
                    'session_date' => $newDate->format('Y-m-d'),
                ],
                [
                    'day' => $newDate->format('l'),
                    'day_type' => $entry->day_type,
                    'from_time' => $entry->from_time,
                    'to_time' => $entry->to_time,
                ]
            );
        }

        return redirect()->back()
            ->with('success', 'Last week timetable copied successfully.');
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