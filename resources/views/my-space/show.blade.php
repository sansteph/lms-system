@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @if(session('user_role') == 'Teacher')
            @include('layouts.teacher-sidebar')
        @elseif(session('user_role') == 'Admin')
            @include('layouts.sidebar')
        @else
            @include('layouts.student-sidebar')
        @endif

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2>{{ $item->title }}</h2>

                <p class="text-muted mb-0">
                    {{ $item->type }} Submission
                </p>
            </div>

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <div class="row g-4">

                        <div class="col-md-6">

                            <div class="profile-info-item">
                                <span class="profile-label">
                                    Submitted By
                                </span>

                                <h6>
                                    {{ $submitter->name ?? 'Deleted User' }}
                                </h6>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="profile-info-item">
                                <span class="profile-label">
                                    Role
                                </span>

                                <h6>
                                    {{ $item->created_by_type }}
                                </h6>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="profile-info-item">
                                <span class="profile-label">
                                    Status
                                </span>

                                <h6>
                                    {{ $item->status }}
                                </h6>
                            </div>

                        </div>

                        <div class="col-md-12">

                            <div class="profile-info-item">

                                <span class="profile-label">
                                    Description
                                </span>

                                <p class="mb-0">
                                    {{ $item->description }}
                                </p>

                            </div>

                        </div>

                        <div class="col-md-12">

                            <div class="profile-info-item">

                                <span class="profile-label">
                                    Resource
                                </span>

                                @if($item->type == 'Idea')

                                    <a href="{{ asset('storage/'.$item->blueprint_pdf) }}"
                                       target="_blank"
                                       class="btn btn-primary">

                                        View Blueprint PDF

                                    </a>

                                @else

                                    <a href="{{ $item->repository_link }}"
                                       target="_blank"
                                       class="btn btn-success">

                                        Open Repository

                                    </a>

                                @endif

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

@endsection