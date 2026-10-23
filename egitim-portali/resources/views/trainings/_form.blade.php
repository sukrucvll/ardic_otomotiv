<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-journal-text me-2 text-primary"></i>Eğitim Bilgileri</h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="title" class="form-label">Eğitim adı</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $training->title) }}"
                               class="form-control @error('title') is-invalid @enderror" placeholder="Örn. Temel İş Güvenliği Eğitimi" required autofocus>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-7">
                        <label for="category" class="form-label">Kategori</label>
                        <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="" disabled @selected(! old('category', $training->category))>Kategori seçin</option>
                            @foreach ($categories as $name => $meta)
                                <option value="{{ $name }}" @selected(old('category', $training->category) === $name)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label for="duration_hours" class="form-label">Süre</label>
                        <div class="input-group has-validation">
                            <input type="number" id="duration_hours" name="duration_hours" min="1" max="1000"
                                   value="{{ old('duration_hours', $training->duration_hours) }}"
                                   class="form-control @error('duration_hours') is-invalid @enderror" required>
                            <span class="input-group-text">saat</span>
                            @error('duration_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea id="description" name="description" rows="4"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Eğitimin amacı, kapsamı ve hedef kitlesi...">{{ old('description', $training->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_mandatory" name="is_mandatory" value="1"
                                @checked(old('is_mandatory', $training->is_mandatory))>
                            <label class="form-check-label" for="is_mandatory">Zorunlu eğitim</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end gap-2 py-3 px-4">
                <a href="{{ $training->exists ? route('trainings.show', $training) : route('trainings.index') }}" class="btn btn-light">Vazgeç</a>
                <button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</div>
