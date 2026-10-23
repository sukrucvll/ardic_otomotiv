<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar d-none d-lg-flex">
        @include('partials.nav')
    </aside>

    <div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="mobileNav">
        <div class="offcanvas-body d-flex flex-column">
            @include('partials.nav')
        </div>
    </div>

    <div class="app-main">
        <header class="topbar d-flex align-items-center gap-3">
            <button class="btn btn-light btn-icon d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Menü">
                <i class="bi bi-list fs-5"></i>
            </button>
            <nav aria-label="breadcrumb" class="small">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">{{ config('app.name') }}</li>
                    <li class="breadcrumb-item active fw-medium text-dark">@yield('title')</li>
                </ol>
            </nav>
            <div class="ms-auto d-flex align-items-center gap-3 small text-muted">
                <span class="d-none d-md-inline"><i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('d F Y, l') }}</span>
                <div class="dropdown">
                    <button class="user-chip btn btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar bg-primary-subtle text-primary-emphasis">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                        <span class="d-none d-sm-inline fw-medium">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <div class="fw-semibold text-dark">{{ auth()->user()->name }}</div>
                            <div class="small text-muted">{{ auth()->user()->email }}</div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Çıkış yap</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content">
            <div class="page-header d-flex flex-wrap align-items-end justify-content-between gap-3">
                <div>
                    <h1 class="page-title">@yield('title')</h1>
                    @hasSection('subtitle')
                        <p class="page-subtitle">@yield('subtitle')</p>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @yield('actions')
                </div>
            </div>

            @yield('content')
        </main>
    </div>
</div>

{{-- Bildirimler --}}
<div class="toast-container position-fixed top-0 end-0 p-3">
    @foreach (['success' => ['check-circle-fill', 'success'], 'error' => ['x-circle-fill', 'danger']] as $key => [$icon, $color])
        @if (session($key))
            <div class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                <div class="d-flex">
                    <div class="toast-body d-flex gap-2 align-items-start">
                        <i class="bi bi-{{ $icon }} text-{{ $color }} fs-5"></i>
                        <span>{{ session($key) }}</span>
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Kapat"></button>
                </div>
            </div>
        @endif
    @endforeach
</div>

{{-- Onay penceresi (form[data-confirm] ile kullanılır) --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="stat-icon bg-danger-subtle text-danger mx-auto mb-3"><i class="bi bi-exclamation-triangle"></i></div>
                <h5 class="fw-semibold mb-2" data-confirm-title>Emin misiniz?</h5>
                <p class="text-muted mb-4" data-confirm-message></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="button" class="btn btn-danger px-4" data-confirm-ok>Onayla</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
