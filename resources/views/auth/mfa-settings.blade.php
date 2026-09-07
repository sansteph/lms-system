@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <div class="text-uppercase small fw-bold text-primary mb-2">Account security</div>
                            <h3 class="mb-2">Two-Factor Authentication</h3>
                            <p class="text-muted mb-0">Add a verification code from your registered email whenever you sign in.</p>
                        </div>
                        <span class="badge {{ $user->mfa_enabled ? 'bg-success' : 'bg-secondary' }}">
                            {{ $user->mfa_enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    @if(!$user->mfa_enabled)
                        <div class="alert alert-info">A 6-digit code will be sent to your registered email. It expires after 5 minutes.</div>
                        <form method="POST" action="{{ route(session('user_role') === 'Teacher' ? 'teacher.mfa.enable' : (session('user_role') === 'Principal' ? 'principal.mfa.enable' : 'admin.mfa.enable')) }}">
                            @csrf
                            <label class="form-label" for="current_password">Confirm your current password</label>
                            <input id="current_password" type="password" name="current_password" class="form-control" required>
                            <button class="btn btn-primary mt-3">Send verification code</button>
                        </form>
                    @else
                        <div class="alert alert-success">Your account requires email verification after password login.</div>
                        <form method="POST" action="{{ route(session('user_role') === 'Teacher' ? 'teacher.mfa.disable' : (session('user_role') === 'Principal' ? 'principal.mfa.disable' : 'admin.mfa.disable')) }}">
                            @csrf
                            <label class="form-label" for="disable_password">Confirm your current password to disable 2FA</label>
                            <input id="disable_password" type="password" name="current_password" class="form-control" required>
                            <button class="btn btn-outline-danger mt-3">Disable 2FA</button>
                        </form>
                    @endif

                    @if(session('mfa_setup_challenge_token'))
                        <hr class="my-4">
                        <h5>Verify email code</h5>
                        <p class="text-muted">Enter the code sent to your registered email to finish enabling 2FA.</p>
                        <form method="POST" action="{{ route(session('user_role') === 'Teacher' ? 'teacher.mfa.verify' : (session('user_role') === 'Principal' ? 'principal.mfa.verify' : 'admin.mfa.verify')) }}">
                            @csrf
                            <div class="input-group">
                                <input type="text" name="code" inputmode="numeric" maxlength="6" class="form-control" placeholder="6-digit code" required>
                                <button class="btn btn-primary">Verify and enable</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
