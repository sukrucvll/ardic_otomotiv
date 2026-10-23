@auth
    <div class="sidebar-user">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            <div class="min-w-0">
                <div class="sidebar-user-name text-truncate">{{ auth()->user()->name }}</div>
                <div class="sidebar-user-email text-truncate">{{ auth()->user()->email }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-sm btn-logout w-100"><i class="bi bi-box-arrow-right me-1"></i> Çıkış yap</button>
        </form>
    </div>
@endauth
