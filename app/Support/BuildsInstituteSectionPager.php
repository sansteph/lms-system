<?php

namespace App\Support;

use App\Models\Institute;
use Illuminate\Http\Request;

trait BuildsInstituteSectionPager
{
    protected function buildInstituteSectionPager(Request $request, string $routeName, array $routeParameters = []): array
    {
        $institutes = Institute::where('status', 1)
            ->orderBy('institute_name')
            ->pluck('institute_name')
            ->values();

        $lastPage = max($institutes->count(), 1);
        $currentPage = min(max((int) $request->input('section_page', 1), 1), $lastPage);
        $currentInstitute = $institutes->get($currentPage - 1);
        $query = $request->except(['section_page', 'page']);

        return [
            'currentInstitute' => $currentInstitute,
            'sectionPager' => [
                'label' => $currentInstitute ?: 'Institutes',
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_url' => $currentPage > 1
                    ? route($routeName, array_merge($routeParameters, $query, ['section_page' => $currentPage - 1]))
                    : null,
                'next_url' => $currentPage < $lastPage
                    ? route($routeName, array_merge($routeParameters, $query, ['section_page' => $currentPage + 1]))
                    : null,
            ],
        ];
    }
}
