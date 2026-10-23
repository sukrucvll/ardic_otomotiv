@extends('layouts.app')

@section('title', 'Personeller')
@section('subtitle', 'Çalışanlar ve eğitim ilerlemeleri.')

@section('content')
    @if ($personnelUnavailable)
        @include('partials.personnel-unavailable', ['class' => 'mb-4'])
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Ad, soyad veya e-posta ile ara...">
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <select name="department" class="form-select" onchange="this.form.submit()">
                        <option value="">Tüm departmanlar</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i> Filtrele</button></div>
                @if (request()->hasAny(['search', 'department']))
                    <div class="col-auto"><a href="{{ route('employees.index') }}" class="btn btn-light"><i class="bi bi-x-lg"></i> Temizle</a></div>
                @endif
            </form>
        </div>

        @if ($employees->isEmpty())
            <div class="empty-state">
                <i class="bi bi-people"></i>
                {{ $personnelUnavailable ? 'Personel listesi alınamadı.' : 'Kriterlere uygun personel bulunamadı.' }}
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Personel</th>
                        <th>Departman</th>
                        <th style="min-width: 180px">Eğitim İlerlemesi</th>
                        <th class="text-end"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($employees as $employee)
                        @php
                            $row = $progress->get($employee->id);
                            $total = (int) ($row->total ?? 0);
                            $done = (int) ($row->completed ?? 0);
                            $rate = $total ? (int) round($done * 100 / $total) : 0;
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-{{ $employee->departmentColor() }}-subtle text-{{ $employee->departmentColor() }}-emphasis">{{ $employee->initials() }}</span>
                                    <div>
                                        <a href="{{ route('employees.show', $employee->id) }}" class="fw-semibold text-reset text-decoration-none">{{ $employee->fullName() }}</a>
                                        <div class="small text-muted">{{ $employee->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-soft bg-{{ $employee->departmentColor() }}-subtle text-{{ $employee->departmentColor() }}-emphasis">{{ $employee->department }}</span>
                            </td>
                            <td>
                                @if ($total)
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">{{ $done }}/{{ $total }} eğitim</span>
                                        <span class="fw-semibold">%{{ $rate }}</span>
                                    </div>
                                    <div class="progress progress-thin"><div class="progress-bar" style="width: {{ $rate }}%"></div></div>
                                @else
                                    <span class="small text-muted">Atama yok</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-sm btn-light">Detay <i class="bi bi-chevron-right"></i></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($employees->hasPages())
                <div class="card-footer bg-transparent py-3">{{ $employees->links() }}</div>
            @endif
        @endif
    </div>
@endsection
