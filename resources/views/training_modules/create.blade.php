@extends('layouts.app')

@section('title', 'Neues Ausbildungsmodul erstellen')

@section('content')
    {{-- AdminLTE Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-plus-circle nav-icon"></i> Neues Ausbildungsmodul</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('modules.index') }}">Module</a></li>
                        <li class="breadcrumb-item active">Erstellen</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    {{-- Das Formular umschließt die gesamte Karte --}}
                    <form action="{{ route('modules.store') }}" method="POST">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Moduldetails eingeben</h3>
                            </div>
                            {{-- Das Partial bindet den @csrf-Token, den card-body und den card-footer ein --}}
                            @include('training_modules._form')
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

