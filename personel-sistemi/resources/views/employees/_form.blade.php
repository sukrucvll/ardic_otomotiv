<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-person-vcard me-2 text-primary"></i>Personel Bilgileri</h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">Ad</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $employee->first_name) }}"
                               class="form-control @error('first_name') is-invalid @enderror" placeholder="Örn. Ahmet" required autofocus>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Soyad</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $employee->last_name) }}"
                               class="form-control @error('last_name') is-invalid @enderror" placeholder="Örn. Yılmaz" required>
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">E-posta</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="email" name="email" value="{{ old('email', $employee->email) }}"
                                   class="form-control @error('email') is-invalid @enderror" placeholder="ad.soyad@sirket.com" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Departman</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
                            <select id="department" name="department" class="form-select @error('department') is-invalid @enderror" required>
                                <option value="" disabled @selected(! old('department', $employee->department))>Departman seçin</option>
                                @foreach ($departments as $name => $color)
                                    <option value="{{ $name }}" @selected(old('department', $employee->department) === $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end gap-2 py-3 px-4">
                <a href="{{ route('employees.index') }}" class="btn btn-light">Vazgeç</a>
                <button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</div>
