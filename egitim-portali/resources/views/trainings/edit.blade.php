@extends('layouts.app')

@section('title', 'Eğitimi Düzenle')
@section('subtitle', $training->title)

@section('actions')
    <a href="{{ route('trainings.show', $training) }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Eğitime dön</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('trainings.update', $training) }}" novalidate>
        @csrf
        @method('PUT')
        @include('trainings._form', ['submitLabel' => 'Değişiklikleri Kaydet'])
    </form>
@endsection
