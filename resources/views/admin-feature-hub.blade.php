@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <main class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2>{{ $title }}</h2>
                <p>{{ $description }}</p>
            </div>

            <div class="admin-hub-grid">
                @foreach($items as $item)
                    <a href="{{ $item['route'] }}" class="admin-hub-card">
                        <div class="admin-hub-icon">
                            <i class="fa {{ $item['icon'] }}"></i>
                        </div>

                        <div>
                            <h4>{{ $item['title'] }}</h4>
                            <p>{{ $item['description'] }}</p>
                        </div>

                        <span class="admin-hub-arrow">
                            <i class="fa fa-arrow-right"></i>
                        </span>
                    </a>
                @endforeach
            </div>

        </main>

    </div>
</div>

@endsection
