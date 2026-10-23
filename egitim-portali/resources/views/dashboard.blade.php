@extends('layouts.app')

@section('title', 'Panel')
@section('subtitle', 'Eğitim atamalarının ve tamamlanma durumlarının genel görünümü.')

@section('actions')
    <a href="{{ route('trainings.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Yeni Eğitim</a>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        @foreach ([
            ['Eğitim', $stats['trainings'], 'journal-bookmark', 'primary', null],
            ['Toplam Atama', $stats['assignments'], 'person-lines-fill', 'info', null],
            ['Tamamlanan', $stats['completed'], 'check2-circle', 'success', '%'.$stats['completion_rate'].' tamamlanma'],
            ['Gecikmiş', $stats['overdue'], 'alarm', 'danger', 'Son tarihi geçmiş'],
        ] as [$label, $value, $icon, $color, $hint])
            <div class="col-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis"><i class="bi bi-{{ $icon }}"></i></div>
                        <div>
                            <div class="stat-value">{{ $value }}</div>
                            <div class="stat-label">{{ $label }}</div>
                            @if ($hint) <div class="small text-muted">{{ $hint }}</div> @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Eğitim Bazında İlerleme</h2>
                    <a href="{{ route('trainings.index') }}" class="small">Tümü <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    @forelse ($trainings as $training)
                        <a href="{{ route('trainings.show', $training) }}" class="d-block text-decoration-none text-reset {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-{{ $training->category_color }}-subtle text-{{ $training->category_color }}-emphasis" style="width:40px;height:40px;font-size:1.1rem">
                                    <i class="bi bi-{{ $training->category_icon }}"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="fw-semibold text-truncate">{{ $training->title }}</span>
                                        <span class="small text-muted text-nowrap">{{ $training->completed_count }}/{{ $training->assignments_count }} kişi</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <div class="progress progress-thin flex-grow-1">
                                            <div class="progress-bar" style="width: {{ $training->completion_rate }}%"></div>
                                        </div>
                                        <span class="small fw-semibold" style="width:2.5rem">%{{ $training->completion_rate }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-journal-plus"></i>
                            <p class="mb-3">Henüz eğitim tanımlanmadı.</p>
                            <a href="{{ route('trainings.create') }}" class="btn btn-primary btn-sm">İlk eğitimi oluştur</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-pie-chart me-2 text-primary"></i>Durum Dağılımı</h2>
                </div>
                <div class="card-body">
                    @php
                        $palette = ['assigned' => '#94a3b8', 'in_progress' => '#f59e0b', 'completed' => '#16a34a'];
                    @endphp
                    <div class="progress mb-3" style="height: 14px">
                        @foreach (\App\Enums\AssignmentStatus::cases() as $status)
                            @php($count = $statusCounts[$status->value] ?? 0)
                            @if ($count)
                                <div class="progress-bar" style="width: {{ $count * 100 / max($stats['assignments'], 1) }}%; background: {{ $palette[$status->value] }}"
                                     data-bs-toggle="tooltip" title="{{ $status->label() }}: {{ $count }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="d-flex flex-column gap-2">
                        @foreach (\App\Enums\AssignmentStatus::cases() as $status)
                            <a href="{{ route('assignments.index', ['status' => $status->value]) }}" class="d-flex justify-content-between text-decoration-none text-reset small">
                                <span class="status-legend"><span style="--dot: {{ $palette[$status->value] }}">{{ $status->label() }}</span></span>
                                <span class="fw-semibold">{{ $statusCounts[$status->value] ?? 0 }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-diagram-3 me-2 text-primary"></i>Departman Bazında Tamamlanma</h2>
                </div>
                <div class="card-body">
                    @forelse ($departments as $department)
                        @php($color = \App\Support\DepartmentColor::for($department->name))
                        <a href="{{ route('assignments.index', ['department' => $department->name]) }}"
                           class="d-block text-decoration-none text-reset {{ $loop->last ? '' : 'mb-3' }}">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-medium"><i class="bi bi-circle-fill text-{{ $color }} me-1" style="font-size: .5rem; vertical-align: middle"></i>{{ $department->name }}</span>
                                <span class="text-muted">{{ $department->completed }}/{{ $department->total }} · <span class="fw-semibold text-dark">%{{ $department->rate }}</span></span>
                            </div>
                            <div class="progress progress-thin"><div class="progress-bar" style="width: {{ $department->rate }}%"></div></div>
                        </a>
                    @empty
                        <div class="empty-state py-3"><i class="bi bi-diagram-3"></i>Henüz atama yok.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title"><i class="bi bi-clock-history me-2 text-primary"></i>Son Atamalar</h2>
                    <a href="{{ route('assignments.index') }}" class="small">Tümü <i class="bi bi-arrow-right"></i></a>
                </div>
                @if ($recent->isEmpty())
                    <div class="empty-state"><i class="bi bi-inbox"></i>Henüz atama yapılmadı.</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Personel</th><th>Eğitim</th><th>Durum</th></tr></thead>
                            <tbody>
                            @foreach ($recent as $assignment)
                                <tr>
                                    <td>
                                        <a href="{{ route('employees.show', $assignment->employee_id) }}" class="text-decoration-none text-reset d-flex align-items-center gap-2">
                                            <span class="avatar bg-{{ \App\Support\DepartmentColor::for($assignment->employee_department) }}-subtle text-{{ \App\Support\DepartmentColor::for($assignment->employee_department) }}-emphasis" style="width:34px;height:34px;font-size:.75rem">{{ $assignment->employee_initials }}</span>
                                            <span>
                                                <span class="fw-semibold d-block">{{ $assignment->employee_name }}</span>
                                                <span class="small text-muted">{{ $assignment->employee_department }}</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td class="small"><a href="{{ route('trainings.show', $assignment->training) }}" class="text-reset">{{ $assignment->training->title }}</a></td>
                                    <td>@include('partials.status-badge')</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-calendar-event me-2 text-primary"></i>Yaklaşan Son Tarihler</h2>
                </div>
                <div class="card-body">
                    @forelse ($upcoming as $assignment)
                        <div class="d-flex align-items-center gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                            <div class="text-center rounded-3 px-2 py-1 {{ $assignment->is_overdue ? 'bg-danger-subtle text-danger-emphasis' : 'bg-light' }}" style="min-width:52px">
                                <div class="fw-bold lh-1">{{ $assignment->due_date->format('d') }}</div>
                                <div class="small text-uppercase" style="font-size:.65rem">{{ $assignment->due_date->translatedFormat('M') }}</div>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate">{{ $assignment->employee_name }}</div>
                                <div class="small text-muted text-truncate">{{ $assignment->training->title }}</div>
                            </div>
                            <span class="small {{ $assignment->is_overdue ? 'text-danger fw-semibold' : 'text-muted' }} text-nowrap">
                                {{ $assignment->is_overdue ? 'Gecikti' : $assignment->due_date->diffForHumans(['parts' => 1]) }}
                            </span>
                        </div>
                    @empty
                        <div class="empty-state py-4"><i class="bi bi-calendar-check"></i>Bekleyen son tarih yok.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
