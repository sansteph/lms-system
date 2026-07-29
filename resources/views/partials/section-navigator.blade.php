@if(!empty($sectionPager))
    @php
        $sectionLabel = $sectionPager['current_label'] ?? $sectionPager['label'] ?? 'Section';
        $sectionDescription = $sectionDescription ?? null;
        $previousLabel = $sectionPager['previous_label'] ?? 'Start of list';
        $nextLabel = $sectionPager['next_label'] ?? 'End of list';
    @endphp

    <div class="lms-section-navigator mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="lms-section-pill mb-2">
                    Section {{ $sectionPager['current_page'] }} of {{ $sectionPager['last_page'] }}
                </div>

                <h5 class="mb-1">{{ $sectionLabel }}</h5>

                @if($sectionDescription)
                    <p class="text-muted mb-0">
                        {{ $sectionDescription }}
                    </p>
                @else
                    <p class="text-muted mb-0">
                        Browse one focused section at a time to keep this page fast and stable.
                    </p>
                @endif
            </div>

            <div class="d-flex gap-2 flex-wrap">
                @if($sectionPager['previous_url'])
                    <a href="{{ $sectionPager['previous_url'] }}" class="btn btn-outline-primary lms-section-nav-button">
                        Previous
                        <small>{{ $previousLabel }}</small>
                    </a>
                @else
                    <button type="button" class="btn btn-outline-secondary lms-section-nav-button" disabled>
                        Previous
                        <small>Start of list</small>
                    </button>
                @endif

                @if($sectionPager['next_url'])
                    <a href="{{ $sectionPager['next_url'] }}" class="btn btn-primary lms-section-nav-button">
                        Next
                        <small>{{ $nextLabel }}</small>
                    </a>
                @else
                    <button type="button" class="btn btn-outline-secondary lms-section-nav-button" disabled>
                        Next
                        <small>End of list</small>
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
