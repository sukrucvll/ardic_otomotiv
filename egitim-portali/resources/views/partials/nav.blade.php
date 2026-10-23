<a href="{{ route('dashboard') }}" class="brand">
    <span class="brand-logo"><i class="bi bi-mortarboard-fill"></i></span>
    <span>
        <span class="brand-title d-block">Eğitim Portalı</span>
        <span class="brand-sub">Eğitim Takip Sistemi</span>
    </span>
</a>

<div class="nav-section">Genel</div>
<nav class="nav flex-column side-nav gap-1">
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="bi bi-grid-1x2"></i> Panel
    </a>
    <a class="nav-link {{ request()->routeIs('trainings.*') ? 'active' : '' }}" href="{{ route('trainings.index') }}">
        <i class="bi bi-journal-bookmark"></i> Eğitimler
    </a>
    <a class="nav-link {{ request()->routeIs('assignments.*') ? 'active' : '' }}" href="{{ route('assignments.index') }}">
        <i class="bi bi-list-check"></i> Atamalar
    </a>
    <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
        <i class="bi bi-people"></i> Personeller
    </a>
</nav>

@include('partials.sidebar-user')
