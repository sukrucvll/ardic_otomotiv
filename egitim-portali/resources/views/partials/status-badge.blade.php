<span class="badge badge-soft bg-{{ $assignment->status->color() }}-subtle text-{{ $assignment->status->color() }}-emphasis">
    <i class="bi bi-{{ $assignment->status->icon() }} me-1"></i>{{ $assignment->status->label() }}
</span>
@if ($assignment->is_overdue)
    <span class="badge badge-soft bg-danger-subtle text-danger-emphasis"><i class="bi bi-alarm me-1"></i>Gecikmiş</span>
@endif
