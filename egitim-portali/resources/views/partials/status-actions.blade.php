@php($isCompleted = $assignment->status === \App\Enums\AssignmentStatus::Completed)
<div class="d-inline-flex gap-1">
    @unless ($isCompleted)
        <form method="POST" action="{{ route('assignments.update', $assignment) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="completed">
            <button class="btn btn-sm btn-outline-primary text-nowrap" data-bs-toggle="tooltip" title="Tamamlandı olarak işaretle">
                <i class="bi bi-check2"></i><span class="d-none d-xl-inline ms-1">Tamamla</span>
            </button>
        </form>
    @endunless
    <div class="dropdown">
        <button class="btn btn-sm btn-light btn-icon" data-bs-toggle="dropdown" aria-label="Diğer işlemler"><i class="bi bi-three-dots-vertical"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header">Durumu değiştir</h6></li>
            @foreach (\App\Enums\AssignmentStatus::cases() as $status)
                <li>
                    <form method="POST" action="{{ route('assignments.update', $assignment) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status->value }}">
                        <button class="dropdown-item {{ $assignment->status === $status ? 'active' : '' }}" @disabled($assignment->status === $status)>
                            <i class="bi bi-{{ $status->icon() }} text-{{ $status->color() }}"></i> {{ $status->label() }}
                        </button>
                    </form>
                </li>
            @endforeach
            <li><hr class="dropdown-divider"></li>
            <li>
                <button type="button" class="dropdown-item" data-edit-assignment
                        data-action="{{ route('assignments.dates', $assignment) }}"
                        data-title="{{ $assignment->employee_name }} · {{ $assignment->training->title }}"
                        data-due-date="{{ $assignment->due_date?->toDateString() }}"
                        data-completed-on="{{ $assignment->status === \App\Enums\AssignmentStatus::Completed ? $assignment->completed_at?->toDateString() : '' }}">
                    <i class="bi bi-calendar-event"></i> Tarihleri düzenle
                </button>
            </li>
            <li>
                <form method="POST" action="{{ route('assignments.destroy', $assignment) }}"
                      data-confirm="{{ $assignment->employee_name }} kişisinin &quot;{{ $assignment->training->title }}&quot; ataması kaldırılacak."
                      data-confirm-title="Atama kaldırılsın mı?" data-confirm-button="Kaldır">
                    @csrf
                    @method('DELETE')
                    <button class="dropdown-item text-danger"><i class="bi bi-x-circle"></i> Atamayı kaldır</button>
                </form>
            </li>
        </ul>
    </div>
</div>
