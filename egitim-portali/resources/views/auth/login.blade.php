<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div class="auth-shell">
    <aside class="auth-aside d-none d-lg-flex">
        <div class="brand p-0 mb-5">
            <span class="brand-logo"><i class="bi bi-mortarboard-fill"></i></span>
            <span>
                <span class="brand-title d-block">Eğitim Portalı</span>
                <span class="brand-sub">Eğitim Takip Sistemi</span>
            </span>
        </div>

        <div class="my-auto">
            <h1 class="auth-headline">Eğitimleri planlayın,<br>ilerlemeyi takip edin.</h1>
            <p class="auth-lead">Çalışanlara eğitim atayın ve tamamlanma durumlarını tek ekrandan izleyin.</p>

            <ul class="auth-features">
                <li><i class="bi bi-journal-bookmark"></i><span><strong>Eğitim tanımlama</strong> Kategori, süre ve zorunluluk bilgisiyle</span></li>
                <li><i class="bi bi-person-plus"></i><span><strong>Personele atama</strong> Departmana göre toplu atama ve son tarih</span></li>
                <li><i class="bi bi-graph-up-arrow"></i><span><strong>Tamamlanma takibi</strong> Durum, gecikme ve departman bazında ilerleme</span></li>
            </ul>
        </div>

        <div class="small opacity-50">© {{ now()->year }} Eğitim Portalı</div>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <div class="d-lg-none text-center mb-4">
                <span class="brand-logo mx-auto mb-2"><i class="bi bi-mortarboard-fill"></i></span>
                <div class="fw-bold">Eğitim Portalı</div>
            </div>

            <h2 class="h3 fw-bold mb-1">Giriş yap</h2>
            <p class="text-muted mb-4">Devam etmek için hesabınızla oturum açın.</p>

            @if (session('success'))
                <div class="alert alert-success d-flex gap-2 py-2 small border-0"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning d-flex gap-2 py-2 small border-0"><i class="bi bi-exclamation-triangle-fill"></i>{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">E-posta</label>
                    <div class="input-group input-group-lg has-validation">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email" value="{{ \App\Support\OldInput::text('email') }}" autocomplete="username"
                               class="form-control @error('email') is-invalid @enderror" placeholder="ad.soyad@sirket.com" required autofocus>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Parola</label>
                    <div class="input-group input-group-lg has-validation">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password" autocomplete="current-password"
                               class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required>
                        <button class="btn btn-light border" type="button" id="togglePassword" aria-label="Parolayı göster"><i class="bi bi-eye"></i></button>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
                    <label class="form-check-label small" for="remember">Beni hatırla</label>
                </div>

                <button class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right me-1"></i> Giriş yap</button>
            </form>

            @if (app()->isLocal())
                {{-- Yalnızca yerel ortamda: değerlendirme/demo kolaylığı için --}}
                <div class="demo-account mt-4">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="small">
                            <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>Demo hesabı</div>
                            <div><code>{{ config('training.admin.email') }}</code></div>
                            <div><code>{{ config('training.admin.password') }}</code></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="fillDemo"
                                data-email="{{ config('training.admin.email') }}" data-password="{{ config('training.admin.password') }}">
                            Doldur
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </main>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', (event) => {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        event.currentTarget.innerHTML = `<i class="bi bi-eye${show ? '-slash' : ''}"></i>`;
    });

    document.getElementById('fillDemo')?.addEventListener('click', (event) => {
        document.getElementById('email').value = event.currentTarget.dataset.email;
        document.getElementById('password').value = event.currentTarget.dataset.password;
    });
</script>
</body>
</html>
