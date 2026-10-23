<?php

return [

    'after_or_equal' => ':attribute :date veya sonrası bir tarih olmalıdır.',
    'array' => ':attribute alanı bir liste olmalıdır.',
    'boolean' => ':attribute alanı doğru veya yanlış olmalıdır.',
    'date' => ':attribute geçerli bir tarih olmalıdır.',
    'distinct' => ':attribute alanında tekrar eden değer var.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'enum' => 'Seçilen :attribute geçersiz.',
    'exists' => 'Seçilen :attribute geçersiz.',
    'in' => 'Seçilen :attribute geçersiz.',
    'integer' => ':attribute bir tam sayı olmalıdır.',
    'max' => [
        'numeric' => ':attribute :max değerinden büyük olamaz.',
        'string' => ':attribute en fazla :max karakter olabilir.',
        'array' => ':attribute en fazla :max öğe içerebilir.',
    ],
    'min' => [
        'numeric' => ':attribute en az :min olmalıdır.',
        'string' => ':attribute en az :min karakter olmalıdır.',
        'array' => ':attribute en az :min öğe içermelidir.',
    ],
    'required' => ':attribute alanı zorunludur.',
    'string' => ':attribute metin olmalıdır.',

    'attributes' => [
        'email' => 'E-posta',
        'password' => 'Parola',
        'title' => 'Eğitim adı',
        'category' => 'Kategori',
        'description' => 'Açıklama',
        'duration_hours' => 'Süre',
        'is_mandatory' => 'Zorunlu',
        'employee_ids' => 'Personel',
        'employee_ids.*' => 'Personel',
        'training_ids' => 'Eğitim',
        'training_ids.*' => 'Eğitim',
        'due_date' => 'Son tamamlama tarihi',
        'status' => 'Durum',
        'today' => 'bugün',
    ],

];
