<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Deine Cfx.re ID</title>

    <!-- AdminLTE & Font Awesome Assets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    
    <style>
        .check-id-box {
            width: 420px; /* Leicht breiter für die Ausgabe */
            margin: 7% auto;
        }
        .login-page {
            /* Stellt sicher, dass das Layout vertikal zentriert bleibt */
            height: 100vh;
        }
    </style>
</head>
{{-- AdminLTE Klasse für Login-Seiten --}}
<body class="hold-transition login-page">

<div class="check-id-box">
    {{-- AdminLTE Card Layout --}}
    <div class="card card-outline card-success">
        <div class="card-header text-center">
            <h3 class="h3">Deine Cfx.re ID</h3>
        </div>
        <div class="card-body">
            <p class="login-box-msg">
                Kopiere die folgende ID und gib sie an die Personalabteilung weiter.
            </p>
            
            <div class="input-group mb-3">
                <input type="text" class="form-control form-control-lg" value="{{ $cfxId }}" readonly id="cfxIdInput">
                <div class="input-group-append">
                    {{-- btn-flat für AdminLTE Stil --}}
                    <button class="btn btn-outline-secondary btn-flat" type="button" id="copyButton">
                        <i class="fas fa-copy me-1"></i> Kopieren
                    </button>
                </div>
            </div>
            
            <p class="mb-3 text-center">Dein Cfx.re-Benutzername lautet: <strong>{{ $cfxName }}</strong></p>
            
            <div class="row">
                <div class="col-12">
                    <a href="{{ url('/') }}" class="btn btn-secondary btn-block btn-flat">
                        <i class="fas fa-arrow-left me-1"></i> Zurück zum Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://rac-panel.de/adminlte/jquery/jquery.min.js"></script>
<script src="https://rac-panel.de/adminlte/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('copyButton').addEventListener('click', function() {
        const input = document.getElementById('cfxIdInput');
        
        // Verwende moderne Clipboard API, falls verfügbar (mit Fallback)
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(() => {
                this.innerHTML = '<i class="fas fa-check me-1"></i> Kopiert!';
                setTimeout(() => { this.innerHTML = '<i class="fas fa-copy me-1"></i> Kopieren'; }, 2000);
            }).catch(err => {
                console.error('Kopieren fehlgeschlagen:', err);
                this.innerHTML = '<i class="fas fa-times me-1"></i> Fehler';
            });
        } else {
            // Fallback für ältere Browser
            input.select();
            document.execCommand('copy');
            this.innerHTML = '<i class="fas fa-check me-1"></i> Kopiert!';
            setTimeout(() => { this.innerHTML = '<i class="fas fa-copy me-1"></i> Kopieren'; }, 2000);
        }
    });
</script>
</body>
</html>
