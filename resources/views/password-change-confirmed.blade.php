@extends('layouts.app')

@section('content')

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow border-0">
                <div class="card-body p-5 text-center">
                    <div class="display-5 {{ ($status ?? 'success') == 'success' ? 'text-success' : 'text-danger' }} mb-3">
                        <i class="fa {{ ($status ?? 'success') == 'success' ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                    </div>

                    <h3 class="mb-2">
                        {{ ($status ?? 'success') == 'success' ? 'Password Changed Successfully' : 'Password Change Not Completed' }}
                    </h3>
                    <p class="text-muted mb-4">
                        {{ $message ?? 'Your InnovatEdge LMS password has been updated after email confirmation.' }}
                    </p>

                    <a href="{{ $dashboardRoute ?? route('admin.login') }}" class="btn btn-primary">
                        Continue to LMS
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
