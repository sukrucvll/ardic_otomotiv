@extends('layouts.app')

@section('title', $training->title)
@section('subtitle')
    <span class="badge badge-soft bg-{{ $training->category_color }}-subtle text-{{ $training->category_color }}-emphasis me-1">
        <i class="bi bi-{{ $training->category_icon }} me-1"></i>{{ $training->category }}
    </span>
    <span class="badge badge-soft bg-light text-dark border me-1"><i class="bi bi-clock me-1"></i>{{ $training->duration_hours }} saat</span>
    @if ($training->is_mandatory)
        <span class="badge badge-soft bg-danger-subtle text-danger-emphasis"><i class="bi bi-asterisk me-1"></i>Zorunlu</span>
    @endif
@endsection

@section('actions')
    <a href="{{ route('trainings.edit', $training) }}" class="btn btn-light"><i class="bi bi-pencil me-1"></i> Düzenle</a>
    <form method="POST" action="{{ route('trainings.destroy', $training) }}"
          data-confirm="&quot;{{ $training->title }}&quot; eğitimi ve {{ $assignments->count() }} ataması kalıcı olarak silinecek."
          data-confirm-title="Eğitim silinsin mi?" data-confirm-button="Sil">
        @csrf
        @method('DELETE')
        <button class="btn btn-light text-danger"><i class="bi bi-trash3 me-1"></i> Sil</button>
    </form>
@endsection

@section('content')
    @php
        $total = $assignments->count();
        $completed = $statusCounts['completed'] ?? 0;
        $rate = $total ? (int) round($completed * 100 / $total) : 0;
    @endphp

    <div class="row g-4">
        <div class="col-xl-8">
            {{-- Özet --}}
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-5">
                            <div class="small text-muted mb-1">Tamamlanma oranı</div>
                            <div class="d-flex align-items-end gap-2 mb-2">
                                <span class="display-6 fw-bold lh-1">%{{ $rate }}</span>
                                <span class="text-muted small mb-1">{{ $completed }} / {{ $total }} kişi</span>
                            </div>
                            <div class="progress" style="height: 10px">
                                <div class="progress-bar" style="width: {{ $rate }}%"></div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="row g-2 text-center">
                                @foreach (\App\Enums\AssignmentStatus::cases() as $status)
                                    <div class="col-4">
                                        <div class="rounded-3 p-2 bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis">
                                            <div class="fw-bold fs-4">{{ $statusCounts[$status->value] ?? 0 }}</div>
                                            <div class="small"><i class="bi bi-{{ $status->icon() }} me-1"></i>{{ $status->label() }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @if ($training->description)
                        <hr class="my-4">
                        <p class="mb-0 text-muted" style="white-space: pre-line">{{ $training->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Atanan personel --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title"><i class="bi bi-people me-2 text-primary"></i>Atanan Personel</h2>
                    <span class="badge bg-light text-dark border">{{ $total }} kişi</span>
                </div>
                @if ($assignments->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-person-plus"></i>
                        Bu eğitime henüz kimse atanmadı. Sağdaki listeden personel seçerek atama yapın.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th>Personel</th>
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
                                            <span class="min-w-0">
                                                <span class="fw-semibold d-block">{{ $assignment->employee_name }}</span>
                                                <span class="small text-muted">{{ $assignment->employee_department }} · {{ $assignment->employee_email }}</span>
                                                @if (! $personnelUnavailable && ! $employees->has($assignment->employee_id))
                                                    <span class="badge bg-warning-subtle text-warning-emphasis ms-1" data-bs-toggle="tooltip" title="Bu kişi artık Personel Sistemi'nde kayıtlı değil; son bilinen bilgileri gösteriliyor.">
                                                        <i class="bi bi-person-dash"></i> Sistemde yok
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
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

        {{-- Personel atama --}}
        <div class="col-xl-4">
            <div class="card position-sticky" style="top: 84px">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-person-plus me-2 text-primary"></i>Personel Ata</h2>
                </div>
                <div class="card-body">
                    @if ($personnelUnavailable)
                        @include('partials.personnel-unavailable')
                    @else
                        <form method="POST" action="{{ route('assignments.store', $training) }}" id="assignForm">
                            @csrf

                            @error('employee_ids')
                                <div class="alert alert-danger py-2 small">{{ $message }}</div>
                            @enderror

                            <div class="row g-2 mb-2">
                                <div class="col-7">
                                    <input type="search" class="form-control form-control-sm" placeholder="Ara..." data-picker-search>
                                </div>
                                <div class="col-5">
                                    <select class="form-select form-select-sm" data-picker-department>
                                        <option value="">Tüm dept.</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department }}">{{ $department }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center small mb-2">
                                <label class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" data-picker-all>
                                    <span class="form-check-label">Görünenleri seç</span>
                                </label>
                                <span class="text-muted"><span data-picker-count>0</span> seçili</span>
                            </div>

                            <div class="employee-picker mb-3">
                                @forelse ($employees as $employee)
                                    @php($assigned = $assignedIds->has($employee->id))
                                    <label class="employee-option {{ $assigned ? 'is-assigned' : '' }}"
                                           data-search="{{ $employee->fullName().' '.$employee->email }}"
                                           data-department="{{ $employee->department }}">
                                        <input class="form-check-input mt-0" type="checkbox" name="employee_ids[]" value="{{ $employee->id }}"
                                               @disabled($assigned) @checked(in_array($employee->id, \App\Support\OldInput::list('employee_ids')))>
                                        <span class="avatar bg-{{ $employee->departmentColor() }}-subtle text-{{ $employee->departmentColor() }}-emphasis">{{ $employee->initials() }}</span>
                                        <span class="min-w-0 flex-grow-1">
                                            <span class="d-block fw-medium text-truncate">{{ $employee->fullName() }}</span>
                                            <span class="d-block small text-muted text-truncate">{{ $employee->department }}</span>
                                        </span>
                                        @if ($assigned)
                                            <span class="badge bg-success-subtle text-success-emphasis">Atandı</span>
                                        @endif
                                    </label>
                                @empty
                                    <div class="p-3 text-center text-muted small">Personel Sistemi'nde kayıtlı personel yok.</div>
                                @endforelse
                                <div class="p-3 text-center text-muted small d-none" data-picker-empty>Eşleşen personel yok.</div>
                            </div>

                            <div class="mb-3">
                                <label for="due_date" class="form-label">Son tamamlama tarihi <span class="text-muted fw-normal">(opsiyonel)</span></label>
                                <input type="date" id="due_date" name="due_date" value="{{ \App\Support\OldInput::text('due_date') }}" min="{{ today()->toDateString() }}"
                                       class="form-control @error('due_date') is-invalid @enderror">
                                @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <button class="btn btn-primary w-100" data-picker-submit disabled>
                                <i class="bi bi-send me-1"></i> Seçilenlere ata
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('assignForm');
            if (!form) return;

            const search = form.querySelector('[data-picker-search]');
            const department = form.querySelector('[data-picker-department]');
            const selectAll = form.querySelector('[data-picker-all]');
            const counter = form.querySelector('[data-picker-count]');
            const submit = form.querySelector('[data-picker-submit]');
            const empty = form.querySelector('[data-picker-empty]');
            const options = [...form.querySelectorAll('.employee-option')];
            options.forEach((option) => (option.dataset.search = option.dataset.search.toLocaleLowerCase('tr')));

            const visibleBoxes = () => options
                .filter((option) => !option.classList.contains('d-none'))
                .map((option) => option.querySelector('input'))
                .filter((box) => !box.disabled);

            const update = () => {
                const selected = form.querySelectorAll('input[name="employee_ids[]"]:checked').length;
                counter.textContent = selected;
                submit.disabled = selected === 0;

                const boxes = visibleBoxes();
                selectAll.checked = boxes.length > 0 && boxes.every((box) => box.checked);
            };

            const filter = () => {
                const term = search.value.trim().toLocaleLowerCase('tr');
                let shown = 0;

                options.forEach((option) => {
                    const match = option.dataset.search.includes(term)
                        && (!department.value || option.dataset.department === department.value);
                    option.classList.toggle('d-none', !match);
                    shown += match;
                });

                empty.classList.toggle('d-none', shown > 0 || options.length === 0);
                update();
            };

            search.addEventListener('input', filter);
            department.addEventListener('change', filter);
            selectAll.addEventListener('change', () => {
                visibleBoxes().forEach((box) => (box.checked = selectAll.checked));
                update();
            });
            form.addEventListener('change', (event) => {
                if (event.target.name === 'employee_ids[]') update();
            });

            update();
        })();
    </script>
@endpush
