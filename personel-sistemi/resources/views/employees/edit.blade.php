@extends('layouts.app')

@section('title', 'Personeli Düzenle')
@section('subtitle', $employee->full_name.' bilgilerini güncelleyin.')

@section('actions')
    <a href="{{ route('employees.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Listeye dön</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('employees.update', $employee) }}" novalidate>
        @csrf
        @method('PUT')
        @include('employees._form', ['submitLabel' => 'Değişiklikleri Kaydet'])
    </form>
@endsection
