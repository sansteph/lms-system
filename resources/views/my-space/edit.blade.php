@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @if(session('user_role') == 'Teacher')
            @include('layouts.teacher-sidebar')
        @else
            @include('layouts.student-sidebar')
        @endif

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2>Edit Submission</h2>
                <p class="text-muted">
                    Update your idea or project submission.
                </p>
            </div>

            <div class="card shadow border-0">
                <div class="card-body p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ session('user_role') == 'Teacher'
                                ? route('teacher.my-space.update', $item->id)
                                : route('student.my-space.update', $item->id) }}"
                          enctype="multipart/form-data">

                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Submission Type</label>

                            <select name="type"
                                    id="mySpaceType"
                                    class="form-select"
                                    required>
                                <option value="">Select Type</option>
                                <option value="Idea" {{ $item->type == 'Idea' ? 'selected' : '' }}>Idea</option>
                                <option value="Project" {{ $item->type == 'Project' ? 'selected' : '' }}>Project</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Title</label>

                            <input type="text"
                                   name="title"
                                   class="form-control"
                                   value="{{ old('title', $item->title) }}"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>

                            <textarea name="description"
                                      class="form-control"
                                      rows="5"
                                      required>{{ old('description', $item->description) }}</textarea>
                        </div>

                        <div class="mb-3" id="blueprintField">
                            <label class="form-label">Blueprint PDF</label>

                            @if($item->blueprint_pdf)
                                <div class="mb-2">
                                    <a href="{{ asset('storage/'.$item->blueprint_pdf) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-primary">
                                        View Current Blueprint
                                    </a>
                                </div>
                            @endif

                            <input type="file"
                                   name="blueprint_pdf"
                                   class="form-control"
                                   accept="application/pdf">

                            <small class="text-muted">
                                Upload a new PDF only if you want to replace the current one.
                            </small>
                        </div>

                        <div class="mb-3" id="repositoryField">
                            <label class="form-label">Repository / GitHub Link</label>

                            <input type="url"
                                   name="repository_link"
                                   class="form-control"
                                   value="{{ old('repository_link', $item->repository_link) }}"
                                   placeholder="https://github.com/username/project">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Update Submission
                        </button>

                        @if(session('user_role') == 'Teacher')
                            <a href="{{ route('teacher.my-space') }}"
                               class="btn btn-secondary">
                                Cancel
                            </a>
                        @else
                            <a href="{{ route('student.my-space') }}"
                               class="btn btn-secondary">
                                Cancel
                            </a>
                        @endif

                    </form>

                </div>
            </div>

        </div>

    </div>
</div>

<script>
    const typeSelect = document.getElementById('mySpaceType');
    const blueprintField = document.getElementById('blueprintField');
    const repositoryField = document.getElementById('repositoryField');

    function toggleFields() {
        if (typeSelect.value === 'Idea') {
            blueprintField.classList.remove('d-none');
            repositoryField.classList.add('d-none');
        } else if (typeSelect.value === 'Project') {
            repositoryField.classList.remove('d-none');
            blueprintField.classList.add('d-none');
        } else {
            blueprintField.classList.add('d-none');
            repositoryField.classList.add('d-none');
        }
    }

    typeSelect.addEventListener('change', toggleFields);
    toggleFields();
</script>

@endsection