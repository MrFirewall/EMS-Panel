@extends('layouts.app')
@section('title', 'Prüfung eingereicht')

@section('content')
<div class="content-header">
<div class="container-fluid">
<div class="row mb-2">
<div class="col-sm-12">
<h1 class="m-0"><i class="fas fa-check-circle nav-icon text-success"></i> Prüfung eingereicht</h1>
</div>
</div>
</div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Bestätigung</h3>
                    </div>
                    <div class="card-body text-center">
                        <p class="lead">Vielen Dank! Ihre Prüfung wurde erfolgreich eingereicht.</p>
                        <h2 class="text-primary mt-4">Nächster Schritt: Bewertung</h2>
                        <p>
                            Die Prüfung enthält Freitextfelder und muss manuell durch einen Ausbilder bewertet werden. 
                            Das endgültige Ergebnis und der Status des Moduls werden in Kürze in Ihrem Profil angezeigt.
                        </p>
                        
                        <a href="{{ route('dashboard') }}" class="btn btn-primary mt-4">
                            <i class="fas fa-tachometer-alt"></i> Zurück zum Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection