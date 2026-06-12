@extends('layouts.app')

@section('content')

<div class="coming-soon-container">

    <video autoplay muted loop playsinline class="coming-soon-video">

        <source src="{{ asset('videos/ComingSoon.mp4') }}" type="video/mp4">

    </video>

    <div class="coming-soon-overlay">

        <h1>Coming Soon</h1>

        <p>
            We're building something awesome.
        </p>

        <a href="{{ route('home') }}" class="btn btn-primary">
            Back to Home
        </a>

    </div>

</div>

@endsection