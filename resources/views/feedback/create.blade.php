@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @if($audience === 'teacher')
            @include('layouts.teacher-sidebar')
        @elseif($audience === 'student')
            @include('layouts.student-sidebar')
        @else
            @include('layouts.sidebar')
        @endif

        <div class="col-md-10 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Feedback</h2>
                    <p class="text-muted mb-0">Share your feedback with the InnovatEdge team.</p>
                </div>

                <a href="{{ $backRoute }}" class="btn btn-outline-secondary">
                    Back
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">Please fill all feedback fields correctly.</div>
            @endif

            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-9">
                    <div class="card shadow border-0">
                        <div class="card-body p-4">
                            <form method="POST" action="{{ $submitRoute }}">
                                @csrf

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Category</label>
                                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                            @foreach(['General', 'Learning Content', 'Assessment', 'Session', 'Technical Issue', 'Other'] as $category)
                                                <option value="{{ $category }}" {{ old('category') === $category ? 'selected' : '' }}>
                                                    {{ $category }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('category')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-7">
                                        <label class="form-label">Subject</label>
                                        <input type="text"
                                               name="subject"
                                               value="{{ old('subject') }}"
                                               class="form-control @error('subject') is-invalid @enderror"
                                               maxlength="150"
                                               required>
                                        @error('subject')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Feedback</label>
                                        <textarea name="message"
                                                  rows="8"
                                                  class="form-control @error('message') is-invalid @enderror"
                                                  placeholder="Write your feedback, issue, or suggestion here..."
                                                  required>{{ old('message') }}</textarea>
                                        @error('message')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <a href="{{ $backRoute }}" class="btn btn-outline-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            Submit Feedback
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
