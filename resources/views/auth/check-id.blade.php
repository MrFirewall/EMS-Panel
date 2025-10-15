<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cfx.re ID abrufen</title>

    <!-- AdminLTE & Font Awesome Assets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    
    <style>
        .check-id-box {
            width: 360px; /* Standardbreite für AdminLTE Login Box */
            margin: 7% auto;
        }
    </style>
</head>
{{-- AdminLTE Klasse für Login-Seiten --}}
<body class="hold-transition login-page">

<div class="check-id-box">
    @if(session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- AdminLTE Card Layout --}}
    <div class="card card-outline card-info">
        <div class="card-header text-center">
            <h3 class="h3">Cfx.re ID abrufen</h3>
        </div>
        <div class="card-body">
            <p class="login-box-msg">
                Um dich im EMS Panel zu bewerben oder angelegt zu werden, benötigen wir deine eindeutige Cfx.re ID. Klicke auf den Button, um dich bei FiveM einzuloggen und deine ID anzeigen zu lassen.
            </p>
            
            <div class="row">
                <div class="col-12">
                    <a href="{{ route('check-id.start') }}" class="btn btn-primary btn-block btn-flat">
                        <i class="fas fa-sign-in-alt me-2"></i> ID mit FiveM abrufen
                    </a>
                </div>
            </div>
            
            <p class="mt-3 mb-1 text-center">
                <a href="{{ url('/') }}" class="text-muted small">Zurück zum Haupt-Login</a>
            </p>
        </div>
    </div>
</div>

{{-- REQUIRED SCRIPTS (Minimal für Login-Seite) --}}
<script src="https://rac-panel.de/adminlte/jquery/jquery.min.js"></script>
<script src="https://rac-panel.de/adminlte/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
