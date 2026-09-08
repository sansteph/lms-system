<?php

namespace App\Services;

use App\Models\{Institute, SchoolClass, Course, Student};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MobileManagementFilters
{
    public function apply(Builder $query, Request $r, string $area): Builder
    {
        if ($area !== 'institutes' && $r->filled('institute')) {
            if ($area === 'courses' && $r->institute === '__template_sources') $query->where('is_template_source', true);
            else $query->where('institute', $r->institute);
        }
        $columns = match ($area) {
            'institutes' => ['location' => 'location', 'status' => 'status'],
            'students' => ['student_class' => 'class', 'student_section' => 'section'],
            'classes' => ['class_name' => 'class_name', 'section_name' => 'section'],
            'courses' => ['course_title' => 'course_title'],
            default => [],
        };
        foreach ($columns as $key => $column) if ($r->filled($key)) $query->where($column, $r->input($key));
        if ($area === 'courses') {
            if ($r->user()?->role === 'InstituteAdmin') {
                $query->where('availability_type', 'Institute');
            }
            if ($r->filled('course_class')) $query->where(fn ($q) => $q->where('assigned_class', $r->course_class)
                ->orWhereHas('courseContents.content', fn ($c) => $c->where('assigned_class', $r->course_class)));
            if ($r->filled('content_section')) $query->where(fn ($q) => $q
                ->whereHas('courseContents.content', fn ($c) => $c->where('section', $r->content_section))
                ->orWhereHas('courseContents.content', fn ($c) => $c->where(fn ($s) => $s->whereNull('section')->orWhere('section', ''))
                    ->when($r->filled('course_class'), fn ($c) => $c->where('assigned_class', $r->course_class))));
        }
        $searchColumns = match ($area) {
            'institutes' => ['institute_id', 'institute_name', 'location', 'contact_person', 'email'],
            'students' => ['student_id', 'name', 'institute', 'class', 'section'],
            'teachers' => ['user_id', 'name', 'email', 'institute'],
            'classes' => ['class_name', 'section', 'academic_year'],
            'courses' => ['course_title'],
        };
        if ($r->filled('search')) $query->where(function ($q) use ($searchColumns, $r) {
            foreach ($searchColumns as $column) $q->orWhere($column, 'like', '%'.$r->search.'%');
        });
        return $query;
    }

    public function fields(Request $r, string $area): array
    {
        abort_unless(in_array($area, ['institutes', 'teachers', 'students', 'classes', 'courses'], true), 404);
        $admin = $r->user()->role === 'Admin';
        $institute = $admin ? $r->input('institute') : $r->user()->institute;
        $scope = fn ($q) => $q->when($institute, fn ($q) => $q->where('institute', $institute));
        $options = fn ($values) => collect($values)->filter(fn ($v) => $v !== null && $v !== '')->unique()->sort()->values()
            ->map(fn ($v) => ['value' => (string) $v, 'label' => (string) $v])->all();
        $field = fn ($name, $label, $values, $depends = null) => [
            'name' => $name, 'label' => $label, 'options' => $values, 'depends_on' => $depends,
        ];
        if ($area === 'institutes') return [
            $field('location', 'Location', $options(Institute::when(!$admin, fn ($q) => $q->where('institute_name', $institute))->pluck('location'))),
            $field('status', 'Status', [['value' => '1', 'label' => 'Active'], ['value' => '0', 'label' => 'Inactive']]),
        ];
        $fields = [];
        if ($admin) {
            $institutes = $options($area === 'classes' ? SchoolClass::pluck('institute') : Institute::where('status', 1)->pluck('institute_name'));
            if ($area === 'courses') array_unshift($institutes, ['value' => '__template_sources', 'label' => 'Template Source Courses']);
            $fields[] = $field('institute', 'Institute', $institutes);
        }
        if ($area === 'teachers') return $fields;
        $classKey = match ($area) { 'students' => 'student_class', 'classes' => 'class_name', default => 'course_class' };
        $sectionKey = match ($area) { 'students' => 'student_section', 'classes' => 'section_name', default => 'content_section' };
        $classes = $scope(SchoolClass::query());
        $classNames = (clone $classes)->pluck('class_name');
        $sections = (clone $classes)->when($r->filled($classKey), fn ($q) => $q->where('class_name', $r->input($classKey)))->pluck('section');
        if ($area === 'students') {
            // Include legacy student values as well as classes with no enrollments yet.
            $students = $scope(Student::query());
            $classNames = $classNames->merge((clone $students)->pluck('class'));
            $sections = $sections->merge($students->when($r->filled($classKey), fn ($q) => $q->where('class', $r->input($classKey)))->pluck('section'));
        }
        $fields[] = $field($classKey, 'Class', $options($classNames), 'institute');
        $fields[] = $field($sectionKey, 'Section', $options($sections), $classKey);
        if ($area === 'courses') {
            $courses = Course::query();
            if (!$admin) {
                $courses->where('institute', $institute)->where('availability_type', 'Institute');
            }
            $copy = $r->duplicate();
            $copy->query->remove('course_title');
            $fields[] = $field('course_title', 'Course', $options($this->apply($courses, $copy, $area)->pluck('course_title')), 'content_section');
        }
        return $fields;
    }
}
