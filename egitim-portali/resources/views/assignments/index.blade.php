@extends('layouts.app')

@section('title', 'Atamalar')
@section('subtitle', 'Tüm eğitim atamaları ve tamamlanma durumları.')

@section('content')
    @php
        $tabs = ['' => 'Tümü'] + collect(\App\Enums\AssignmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all() + ['overdue' => 'Gecikmiş'];
    @endphp

    <ul class="nav nav-pills gap-1 mb-3 flex-nowrap overflow-auto">
        @foreach ($tabs as $value => $label)
            <li class="nav-item">
                <a class="nav-link text-nowrap {{ (string) request('status') === (string) $value ? 'active' : 'text-body' }}"
                   href="{{ route('assignments.index', array_filter(['status' => $value] + request()->except('status', 'page'))) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="col-12 col-lg">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Personel adı veya e-posta...">
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <select name="training" class="form-select" onchange="this.form.submit()">
                        <option value="">Tüm eğitimler</option>
                        @foreach ($trainings as $training)
                            <option value="{{ $training->id }}" @selected((string) request('training') === (string) $training->id)>{{ $training->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <select name="department" class="form-select" onchange="this.form.submit()">
                        <option value="">Tüm departmanlar</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i></button></div>
                @if (request()->hasAny(['search', 'training', 'department', 'status']))
                    <div class="col-auto"><a href="{{ route('assignments.index') }}" class="btn btn-light"><i class="bi bi-x-lg"></i></a></div>
                @endif
            </form>
        </div>

        @if ($assignments->isEmpty())
            <div class="empty-state"><i class="bi bi-inbox"></i>Kriterlere uygun atama bulunamadı.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Personel</th>
                        <th>Eğitim</th>
                        <th class="d-none d-md-table-cell">Atanma</th>
                        <th>Son Tarih</th>
                        <th>Durum</th>
                        <th class="text-end">İşlem</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($assignments as $assignment)
                        @php($color = \App\Support\DepartmentColor::for($assignment->employee_department))
                        <tr>
                            <td>
                                <a href="{{ route('employees.show', $assignment->employee_id) }}" class="d-flex align-items-center gap-3 text-decoration-none text-reset">
                                    <span class="avatar bg-{{ $color }}-subtle text-{{ $color }}-emphasis">{{ $assignment->employee_initials }}</span>
                                    <span>
                                        <span class="fw-semibold d-block">{{ $assignment->employee_name }}</span>
                                        <span class="small text-muted">{{ $assignment->employee_department }}</span>
                                    </span>
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('trainings.show', $assignment->training) }}" class="text-reset fw-medium">{{ $assignment->training->title }}</a>
                                <div class="small text-muted">{{ $assignment->training->category }}</div>
                            </td>
                            <td class="d-none d-md-table-cell small text-muted text-nowrap">{{ $assignment->created_at->translatedFormat('d M Y') }}</td>
                            <td class="small text-nowrap {{ $assignment->is_overdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $assignment->due_date?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                            <td>@include('partials.status-badge')</td>
                            <td class="text-end">@include('partials.status-actions')</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($assignments->hasPages())
                <div class="card-footer bg-transparent py-3">{{ $assignments->links() }}</div>
            @endif
        @endif
    </div>
@endsection
