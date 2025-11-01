<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EMS Panel | Gesperrt</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    
    <div class="login-logo">
        <a href="{{ route('login') }}"><b>EMS</b> Panel</a>
    </div>
    
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <h4 class="mb-0">{{ $name ?? 'Gesperrter Benutzer' }}</h4>
        </div>
        
        <div class="card-body login-card-body">
            
            <div class="text-center mb-3">
                @if($avatar)
                    <img src="{{ $avatar }}" class="img-circle elevation-2" alt="User Image" style="width: 90px; height: 90px; object-fit: cover;">
                @else
                    {{-- Fallback-Avatar, falls keiner vorhanden ist --}}
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($name ?? 'A') }}&background=0D8ABC&color=fff&size=90" class="img-circle elevation-2" alt="User Image">
                @endif
            </div>

            <p class="login-box-msg">Sitzung abgelaufen. Bitte erneut anmelden.</p>

            <form action="{{ route('login.cfx') }}" method="GET">
                <div class="row">
                    <div class="col-12">
                        <div class="icheck-primary mb-3 d-flex justify-content-center">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember" class="mb-0 ml-2">
                                Angemeldet bleiben
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="bi bi-box-arrow-in-right mr-2"></i> Erneut mit Cfx.re anmelden
                        </button>
                    </div>
                </div>
            </form>

            <p class="mt-3 mb-1 text-center">
                <a href="{{ route('logout') }}">Als anderer Benutzer anmelden</a>
            </p>
            
        </div>
        </div>
    </div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
</body>
</html>