@extends('layouts.app')
@section('title', 'Neue Benachrichtigungsregel erstellen')
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

