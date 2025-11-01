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
            
            {{-- KORREKTUR: Umwandlung in ein GET-Formular, um die Checkbox zu senden --}}
            <form action="{{ route('login.cfx') }}" method="GET">
                {{-- Wir brauchen kein @csrf, da es eine GET-Anfrage ist --}}
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary btn-block btn-flat btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Login mit Cfx.re
                    </button>
                </div>
                
                {{-- NEU: "Angemeldet bleiben" Checkbox --}}
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">
                                Angemeldet bleiben
                            </label>
                        </div>
                    </div>
                </div>
            </form>
            {{-- ENDE KORREKTUR --}}

            <div class="mt-3 text-center">
                 <p class="mb-1 text-center">
                    <a href="{{ route('check-id.show') }}" class="text-muted small">Du kennst deine Cfx.re ID nicht oder bist neu? Klicke hier!</a>
                </p>
            </div>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- REQUIRED SCRIPTS (Minimal für den Login Screen) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.1/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
{{-- AdminLTE 3 benötigt icheck-bootstrap für die Checkbox-Styles --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">
</body>
</html>
