@extends('layouts.app')

@section('title', 'Yeni Eğitim')
@section('subtitle', 'Personele atanabilecek yeni bir eğitim tanımlayın.')

@section('actions')
    <a href="{{ route('trainings.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Eğitimler</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('trainings.store') }}" novalidate>
        @csrf
        @include('trainings._form', ['submitLabel' => 'Eğitimi Oluştur'])
    </form>
@endsection
