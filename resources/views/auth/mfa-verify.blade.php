@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="mb-2">Verify your login</h3>
                    <p class="text-muted">Enter the 6-digit code sent to your registered email{{ $emailHint ? ' (' . $emailHint . ')' : '' }}. The code expires in 5 minutes.</p>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first('code') }}</div>
                    @endif

                    <form method="POST" action="{{ route('mfa.verify.submit') }}" class="mb-3">
                        @csrf
                        <label class="form-label" for="code">Verification code</label>
                        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="form-control form-control-lg text-center" required autofocus>
                        <button class="btn btn-primary w-100 mt-3">Verify and continue</button>
                    </form>

                    <form method="POST" action="{{ route('mfa.resend') }}">
                        @csrf
                        <button class="btn btn-link w-100">Resend code</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
