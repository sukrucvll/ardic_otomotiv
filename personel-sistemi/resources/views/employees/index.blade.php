@extends('layouts.app')

@section('title', 'Personeller')
@section('subtitle', 'Şirket personelini görüntüleyin, ekleyin ve güncelleyin.')

@section('actions')
    <a href="{{ route('employees.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Yeni Personel
    </a>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        @foreach ([
            ['Toplam Personel', $stats['total'], 'people', 'primary'],
            ['Departman', $stats['departments'], 'diagram-3', 'success'],
            ['Bu Ay Eklenen', $stats['this_month'], 'person-check', 'warning'],
        ] as [$label, $value, $icon, $color])
            <div class="col-12 col-sm-4">
                <div class="card stat-card h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis"><i class="bi bi-{{ $icon }}"></i></div>
                        <div>
                            <div class="stat-value">{{ $value }}</div>
                            <div class="stat-label">{{ $label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('employees.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Ad, soyad veya e-posta ile ara...">
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <select name="department" class="form-select" onchange="this.form.submit()">
                        <option value="">Tüm departmanlar</option>
                        @foreach ($departments as $name => $color)
                            <option value="{{ $name }}" @selected(request('department') === $name)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i> Filtrele</button>
                </div>
                @if (request()->hasAny(['search', 'department']))
                    <div class="col-auto">
                        <a href="{{ route('employees.index') }}" class="btn btn-light"><i class="bi bi-x-lg"></i> Temizle</a>
                    </div>
                @endif
            </form>
        </div>

        @if ($employees->isEmpty())
            <div class="empty-state">
                <i class="bi bi-person-x"></i>
                <p class="mb-3">Kriterlere uygun personel bulunamadı.</p>
                <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Personel ekle</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Personel</th>
                        <th>Departman</th>
                        <th class="d-none d-md-table-cell">Kayıt Tarihi</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($employees as $employee)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-{{ $employee->department_color }}-subtle text-{{ $employee->department_color }}-emphasis">{{ $employee->initials }}</span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-dark">{{ $employee->full_name }}</div>
                                        <div class="small text-muted text-truncate">{{ $employee->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-soft bg-{{ $employee->department_color }}-subtle text-{{ $employee->department_color }}-emphasis">{{ $employee->department }}</span>
                            </td>
                            <td class="d-none d-md-table-cell text-muted small">{{ $employee->created_at->translatedFormat('d M Y') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-light btn-icon" data-bs-toggle="tooltip" title="Düzenle">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="d-inline"
                                      data-confirm="{{ $employee->full_name }} personel listesinden kaldırılacak."
                                      data-confirm-title="Personel silinsin mi?" data-confirm-button="Sil">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-light btn-icon text-danger" data-bs-toggle="tooltip" title="Sil">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($employees->hasPages())
                <div class="card-footer bg-transparent py-3 px-3">
                    {{ $employees->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
