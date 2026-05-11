@extends('layouts.app') 
@section('content') 
<div class="d-flex justify-content-center align-items-center" style="height: 80vh;"> 
    <div class="card p-4 shadow" style="width: 350px;"> 
        <h3 class="text-center mb-3">Student Assessment</h3> 
        <input type="text" class="form-control mb-2" placeholder="Institute ID"> 
        <input type="text" class="form-control mb-2" placeholder="Student ID"> 
        <input type="text" class="form-control mb-3" placeholder="Assessment ID"> 
        <button class="btn btn-success w-100">Start Assessment</button> 
    </div> 
</div> 
@endsection