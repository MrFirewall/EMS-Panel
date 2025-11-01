<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EMS Panel | Gesperrt</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- iCheck Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
</head>
<body class="hold-transition lockscreen">
<!-- Automatic element centering -->
<div class="lockscreen-wrapper">
    <div class="lockscreen-logo">
        <a href="{{ route('login') }}"><b>EMS</b> Panel</a>
    </div>
    <!-- User name -->
    <div class="lockscreen-name">{{ $name ?? 'Benutzer' }}</div>

    <!-- START LOCK SCREEN ITEM -->
    <div class="lockscreen-item">
        <!-- lockscreen image -->
        <div class="lockscreen-image">
            {{-- Zeige Avatar oder Standard-Benutzerbild --}}
            @if($avatar)
                <img src="{{ $avatar }}" alt="User Image">
            @else
                <img src="https://adminlte.io/themes/v3/dist/img/user1-128x128.jpg" alt="User Image">
            @endif
        </div>
        <!-- /.lockscreen-image -->

        <!-- lockscreen credentials (angepasst für CFX) -->
        <form class="lockscreen-credentials" method="GET" action="{{ route('login.cfx') }}">
            
            <p class="text-center text-muted">Sitzung abgelaufen. Bitte erneut anmelden.</p>
            
            <div class="input-group">
                {{-- Ersetzt das Passwortfeld durch einen Button --}}
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Erneut mit Cfx.re anmelden
                </button>
            </div>
            
            {{-- "Angemeldet bleiben" auch hier anbieten --}}
            <div class="row mt-3">
                <div class="col-12">
                    <div class="icheck-primary">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">
                            Angemeldet bleiben
                        </label>
                    </div>
                </div>
            </div>

        </form>
        <!-- /.lockscreen credentials -->

    </div>
    <!-- /.lockscreen-item -->
    <div class="help-block text-center">
        Melde dich an, um deine Sitzung fortzusetzen
    </div>
    <div class="text-center">
        {{-- Link zur normalen Login-Seite, falls man der "falsche" User ist --}}
        <a href="{{ route('logout') }}">Als anderer Benutzer anmelden</a>
    </div>
    <div class="lockscreen-footer text-center">
        Copyright &copy; 2025-{{ date('Y') }} <b>EMS Panel</b><br>
        All rights reserved
    </div>
</div>
<!-- /.center -->

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.1/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
</body>
</html>
