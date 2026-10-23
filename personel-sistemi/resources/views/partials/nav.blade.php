<a href="{{ route('employees.index') }}" class="brand">
    <span class="brand-logo"><i class="bi bi-people-fill"></i></span>
    <span>
        <span class="brand-title d-block">Personel Sistemi</span>
        <span class="brand-sub">İnsan Kaynakları</span>
    </span>
</a>

<div class="nav-section">Yönetim</div>
<nav class="nav flex-column side-nav gap-1">
    <a class="nav-link {{ request()->routeIs('employees.index', 'employees.edit') ? 'active' : '' }}" href="{{ route('employees.index') }}">
        <i class="bi bi-people"></i> Personeller
    </a>
    <a class="nav-link {{ request()->routeIs('employees.create') ? 'active' : '' }}" href="{{ route('employees.create') }}">
        <i class="bi bi-person-plus"></i> Yeni Personel
    </a>
</nav>

@include('partials.sidebar-user')
