@extends('layouts.app')

@section('title', 'Coming Soon | InnovatEdge Hybrid Learning')
@section('meta_description', 'InnovatEdge hybrid learning features are coming soon for students, schools, and STEM learners.')

@section('content')

<div class="coming-soon-container">

    <video autoplay muted loop playsinline class="coming-soon-video">

        <source src="{{ asset('videos/ComingSoon.mp4') }}" type="video/mp4">

    </video>

    <div class="coming-soon-overlay">

        <a href="{{ route('home') }}" class="btn btn-primary">
            Back to Home
        </a>

    </div>

</div>

@endsection
