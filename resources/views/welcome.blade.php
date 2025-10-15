<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EMS Panel Login</title>

    <!-- AdminLTE & Font Awesome Assets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap Icons (für den Login-Button) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
</head>
{{-- AdminLTE verwendet die Klasse 'login-page' --}}
<body class="hold-transition login-page">

<div class="login-box">
    <div class="login-logo">
        <a href="#"><b>EMS</b> Panel</a>
    </div>

    {{-- AdminLTE verwendet 'card' und 'card-primary' --}}
    <div class="card card-outline card-primary">
        @if(session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif
        
        <div class="card-body login-card-body">
            <p class="login-box-msg">
                Bitte melde dich mit deinem FiveM Account an, um fortzufahren.
            </p>
            
            <div class="mb-4 text-center">
                <a href="{{ route('login.cfx') }}" class="btn btn-primary btn-block btn-flat btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Login mit Cfx.re
                </a>
            </div>
            
            <p class="mb-1 text-center">
                <a href="{{ route('check-id.show') }}" class="text-muted small">Du kennst deine Cfx.re ID nicht oder bist neu? Klicke hier!</a>
            </p>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- REQUIRED SCRIPTS (Minimal für den Login Screen) -->
<script src="https://rac-panel.de/adminlte/jquery/jquery.min.js"></script>
<script src="https://rac-panel.de/adminlte/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
</body>
</html>
