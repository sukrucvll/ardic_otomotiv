@extends('layouts.app')

@php
    $name = $employee?->fullName() ?? $snapshot?->employee_name ?? "Personel #{$employeeId}";
    $email = $employee?->email ?? $snapshot?->employee_email;
    $department = $employee?->department ?? $snapshot?->employee_department;
    $initials = $employee?->initials() ?? $snapshot?->employee_initials ?? '?';
    $color = \App\Support\DepartmentColor::for($department);
    $total = $assignments->count();
    $completed = $statusCounts['completed'] ?? 0;
    $rate = $total ? (int) round($completed * 100 / $total) : 0;
@endphp

@section('title', $name)
@section('subtitle', 'Personelin eğitim atamaları ve tamamlanma durumu.')

@section('actions')
    <a href="{{ route('employees.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Personeller</a>
@endsection

@section('content')
    @if ($personnelUnavailable)
        @include('partials.personnel-unavailable', ['class' => 'mb-4'])
    @elseif (! $employee)
        <div class="alert alert-warning d-flex gap-3 border-0 shadow-sm mb-4">
            <i class="bi bi-person-dash fs-4"></i>
            <div>
                <div class="fw-semibold">Bu personel artık Personel Sistemi'nde kayıtlı değil</div>
                <div class="small">Atama geçmişi korunuyor; atama anında kaydedilen son bilinen bilgiler gösteriliyor.</div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-body p-4 text-center">
                    <span class="avatar avatar-lg bg-{{ $color }}-subtle text-{{ $color }}-emphasis mb-3">{{ $initials }}</span>
                    <h2 class="h5 fw-semibold mb-1">{{ $name }}</h2>
                    <div class="text-muted small mb-2">{{ $email }}</div>
                    <span class="badge badge-soft bg-{{ $color }}-subtle text-{{ $color }}-emphasis">{{ $department }}</span>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Eğitim tamamlama</span>
                        <span class="fw-semibold">{{ $completed }}/{{ $total }} · %{{ $rate }}</span>
                    </div>
                    <div class="progress" style="height: 8px"><div class="progress-bar" style="width: {{ $rate }}%"></div></div>
                    <div class="small text-muted mt-3 d-flex align-items-center gap-1">
                        <i class="bi bi-database"></i> Kaynak: Personel Sistemi · ID #{{ $employeeId }}
                    </div>
                </div>
            </div>

            @if ($employee)
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="bi bi-journal-plus me-2 text-primary"></i>Eğitim Ata</h2>
                    </div>
                    <div class="card-body">
                        @if ($availableTrainings->isEmpty())
                            <p class="small text-muted mb-0">Atanabilecek başka eğitim yok.</p>
                        @else
                            <form method="POST" action="{{ route('employees.assignments.store', $employeeId) }}">
                                @csrf
                                @error('training_ids') <div class="alert alert-danger py-2 small">{{ $message }}</div> @enderror
                                <div class="employee-picker mb-3" style="max-height: 260px">
                                    @foreach ($availableTrainings as $training)
                                        <label class="employee-option">
                                            <input class="form-check-input mt-0" type="checkbox" name="training_ids[]" value="{{ $training->id }}"
                                                @checked(in_array($training->id, old('training_ids', [])))>
                                            <i class="bi bi-{{ $training->category_icon }} text-{{ $training->category_color }}"></i>
                                            <span class="min-w-0 flex-grow-1">
                                                <span class="d-block fw-medium text-truncate">{{ $training->title }}</span>
                                                <span class="d-block small text-muted">{{ $training->duration_hours }} saat{{ $training->is_mandatory ? ' · Zorunlu' : '' }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Son tamamlama tarihi <span class="text-muted fw-normal">(opsiyonel)</span></label>
                                    <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" min="{{ today()->toDateString() }}"
                                           class="form-control @error('due_date') is-invalid @enderror">
                                    @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i> Seçilen eğitimleri ata</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-8">
            <div class="row g-3 mb-4">
                @foreach (\App\Enums\AssignmentStatus::cases() as $status)
                    <div class="col-4">
                        <div class="card stat-card h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis d-none d-sm-grid"><i class="bi bi-{{ $status->icon() }}"></i></div>
                                <div>
                                    <div class="stat-value">{{ $statusCounts[$status->value] ?? 0 }}</div>
                                    <div class="stat-label">{{ $status->label() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-list-check me-2 text-primary"></i>Eğitimleri</h2>
                </div>
                @if ($assignments->isEmpty())
                    <div class="empty-state"><i class="bi bi-journal"></i>Bu personele henüz eğitim atanmadı.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th>Eğitim</th>
                                <th>Son Tarih</th>
                                <th>Durum</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($assignments as $assignment)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="stat-icon bg-{{ $assignment->training->category_color }}-subtle text-{{ $assignment->training->category_color }}-emphasis" style="width:38px;height:38px;font-size:1rem">
                                                <i class="bi bi-{{ $assignment->training->category_icon }}"></i>
                                            </div>
                                            <div>
                                                <a href="{{ route('trainings.show', $assignment->training) }}" class="fw-semibold text-reset text-decoration-none">{{ $assignment->training->title }}</a>
                                                <div class="small text-muted">{{ $assignment->training->duration_hours }} saat · {{ $assignment->created_at->translatedFormat('d M Y') }} tarihinde atandı</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small text-nowrap {{ $assignment->is_overdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        {{ $assignment->due_date?->translatedFormat('d M Y') ?? '-' }}
                                    </td>
                                    <td>
                                        @include('partials.status-badge')
                                        @if ($assignment->completed_at)
                                            <div class="small text-muted mt-1">{{ $assignment->completed_at->translatedFormat('d M Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">@include('partials.status-actions')</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
