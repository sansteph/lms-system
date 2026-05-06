@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-center align-items-center" style="height: 80vh;">
    <div class="card p-4 shadow" style="width: 350px;">
        <h3 class="text-center mb-3">Teacher Login</h3>

        <input type="text" class="form-control mb-2" placeholder="Username">
        <input type="password" class="form-control mb-3" placeholder="Password">

        <button class="btn btn-primary w-100">Login</button>
    </div>
</div>

@endsection