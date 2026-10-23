<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<main class="error-page">
    <div class="text-center">
        <div class="error-code">@yield('code')</div>
        <h1 class="h3 fw-bold mb-2">@yield('title')</h1>
        <p class="text-muted mb-4">@yield('message')</p>
        <a href="{{ url('/') }}" class="btn btn-primary px-4"><i class="bi bi-house me-1"></i> Ana sayfaya dön</a>
    </div>
</main>
</body>
</html>
