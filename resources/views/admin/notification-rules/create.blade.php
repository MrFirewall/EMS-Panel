@extends('layouts.app')
@section('title', 'Neue Benachrichtigungsregel erstellen')

@push('styles')
    <link rel="stylesheet" href="[https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css](https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css)" />
    <link rel="stylesheet" href="[https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css](https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css)"> {{-- Passe ggf. die Version an --}}
@endpush

@section('content')
     <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-plus-circle nav-icon"></i> Neue Benachrichtigungsregel</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                         <li class="breadcrumb-item"><a href="{{ route('admin.notification-rules.index') }}">Benachrichtigungsregeln</a></li>
                         <li class="breadcrumb-item active">Erstellen</li>
                    </ol>
                </div>
            </div>
        </div>
     </div>
     <div class="content">
         <div class="container-fluid">
             <form action="{{ route('admin.notification-rules.store') }}" method="POST">
                 <div class="card card-primary card-outline">
                     <div class="card-header">
                         <h3 class="card-title">Regeldetails</h3>
                     </div>
                     {{-- Include form partial --}}
                     @include('admin.notification-rules._form', ['notificationRule' => null])
                 </div>
             </form>
         </div>
     </div>
@endsection

@push('scripts')
    <script src="[https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js](https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js)"></script>
    {{-- Das JavaScript aus dem _form.blade.php wird hier automatisch eingefügt --}}
@endpush
