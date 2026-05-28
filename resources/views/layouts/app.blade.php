@php
    $title = $title ?? 'Dashboard';
    $appName = config('app.name', 'Kopsis POS');
    $activeMenu = $activeMenu ?? '';
    $pageCss = $pageCss ?? [];
    $pageScript = $pageScript ?? null;
    $pageScripts = $pageScripts ?? [];
    $useChart = $useChart ?? false;
    $useBarcode = $useBarcode ?? false;

    if (is_string($pageCss)) {
        $pageCss = [$pageCss];
    }
    if (is_string($pageScripts)) {
        $pageScripts = [$pageScripts];
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - {{ $appName }}</title>

    <link href="{{ app_asset_versioned('assets/vendor/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ app_asset_versioned('assets/vendor/tabler-icons.min.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ app_asset_versioned('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ app_asset_versioned('assets/css/components.css') }}">
    <link rel="stylesheet" href="{{ app_asset_versioned('assets/vendor/aos.css') }}">
    <link rel="stylesheet" href="{{ app_asset_versioned('assets/css/animations.css') }}">

    @foreach ($pageCss as $cssFile)
        <link rel="stylesheet" href="{{ app_asset_versioned($cssFile) }}">
    @endforeach
</head>
<body class="app-body">
<div class="app-shell">
    @include('layouts.sidebar')
    @include('layouts.navbar')

    @yield('content')

    <footer class="app-footer">
        <span>© {{ date('Y') }} Laboratorium Kewirausahaan MTSN 8 Banyuwangi.</span>
        <span>All rights reserved.</span>
    </footer>
    </section>
    </main>
</div>

@include('components.flash')
@include('components.toast-container')
@include('components.confirm-modal')

<script src="{{ app_asset_versioned('assets/vendor/bootstrap.bundle.min.js') }}"></script>
<script src="{{ app_asset_versioned('assets/vendor/aos.js') }}"></script>

<script src="{{ app_asset_versioned('assets/js/app.js') }}"></script>
<script src="{{ app_asset_versioned('assets/js/components.js') }}"></script>
<script src="{{ app_asset_versioned('assets/js/animations.js') }}"></script>

@if ($useChart)
    <script src="{{ app_asset_versioned('assets/vendor/chart.umd.min.js') }}"></script>
@endif

@if ($useBarcode)
    <script src="{{ app_asset_versioned('assets/vendor/JsBarcode.all.min.js') }}"></script>
@endif

@if ($pageScript === 'dashboard')
    <script src="{{ app_asset_versioned('assets/js/dashboard.js') }}"></script>
@endif

@foreach ($pageScripts as $scriptFile)
    <script src="{{ app_asset_versioned($scriptFile) }}"></script>
@endforeach

@yield('scripts')

</body>
</html>
