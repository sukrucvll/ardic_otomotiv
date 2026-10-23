@extends('layouts.app')

@section('title', 'Eğitimler')
@section('subtitle', 'Tanımlı eğitimler ve tamamlanma oranları.')

@section('actions')
    <a href="{{ route('trainings.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Yeni Eğitim</a>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Eğitim adı ile ara...">
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">Tüm kategoriler</option>
                        @foreach ($categories as $name => $meta)
                            <option value="{{ $name }}" @selected(request('category') === $name)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i> Filtrele</button></div>
                @if (request()->hasAny(['search', 'category']))
                    <div class="col-auto"><a href="{{ route('trainings.index') }}" class="btn btn-light"><i class="bi bi-x-lg"></i> Temizle</a></div>
                @endif
            </form>
        </div>
    </div>

    @if ($trainings->isEmpty())
        <div class="card">
            <div class="empty-state">
                <i class="bi bi-journal-x"></i>
                <p class="mb-3">Eğitim bulunamadı.</p>
                <a href="{{ route('trainings.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Eğitim oluştur</a>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($trainings as $training)
                <div class="col-md-6 col-xxl-4">
                    <div class="card training-card h-100 position-relative">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="stat-icon bg-{{ $training->category_color }}-subtle text-{{ $training->category_color }}-emphasis">
                                    <i class="bi bi-{{ $training->category_icon }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="small text-muted">{{ $training->category }}</div>
                                    <h3 class="h6 fw-semibold mb-0">
                                        <a href="{{ route('trainings.show', $training) }}" class="stretched-link text-reset text-decoration-none">{{ $training->title }}</a>
                                    </h3>
                                </div>
                            </div>

                            <p class="small text-muted mb-3 flex-grow-1">{{ \Illuminate\Support\Str::limit($training->description, 110) ?: 'Açıklama eklenmemiş.' }}</p>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge badge-soft bg-light text-dark border"><i class="bi bi-clock me-1"></i>{{ $training->duration_hours }} saat</span>
                                @if ($training->is_mandatory)
                                    <span class="badge badge-soft bg-danger-subtle text-danger-emphasis"><i class="bi bi-asterisk me-1"></i>Zorunlu</span>
                                @endif
                                <span class="badge badge-soft bg-light text-dark border"><i class="bi bi-people me-1"></i>{{ $training->assignments_count }} kişi</span>
                            </div>

                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Tamamlanma</span>
                                <span class="fw-semibold">%{{ $training->completion_rate }}</span>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar" style="width: {{ $training->completion_rate }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($trainings->hasPages())
            <div class="mt-4">{{ $trainings->links() }}</div>
        @endif
    @endif
@endsection
