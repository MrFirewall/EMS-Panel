<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">Stammdaten</h3>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <b>Personalnr.</b> <a class="float-right">{{ $user->personal_number ?? '-' }}</a>
            </li>
            <li class="list-group-item">
                <b>Status</b>
                <a class="float-right">
                    @php
                        $statusClass = 'bg-dark'; // Standard-Farbe
                        switch ($user->status) {
                            case 'Aktiv':
                                $statusClass = 'bg-success';
                                break;
                            case 'Probezeit':
                            case 'Beobachtung':
                                $statusClass = 'bg-info';
                                break;
                            case 'Beurlaubt':
                            case 'Krankgeschrieben':
                                $statusClass = 'bg-warning';
                                break;
                            case 'Suspendiert':
                                $statusClass = 'bg-danger';
                                break;
                            case 'Ausgetreten':
                                $statusClass = 'bg-secondary';
                                break;
                            case 'Bewerbungsphase':
                                $statusClass = 'bg-light text-dark';
                                break;
                        }
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $user->status }}</span>
                </a>
            </li>
            <li class="list-group-item">
                <b>Mitarbeiter ID</b> <span class="float-right">{{ $user->employee_id ?? '-' }}</span>
            </li>
            <li class="list-group-item">
                <b>CFX.re ID</b> <span class="float-right">{{ $user->cfx_id }}</span>
            </li>
            <li class="list-group-item">
                <b>E-Mail</b> <span class="float-right">{{ $user->email ?? '-' }}</span>
            </li>
            <li class="list-group-item">
                <b>Geburtstag</b> <span class="float-right">{{ $user->birthday ? \Carbon\Carbon::parse($user->birthday)->format('d.m.Y') : '-' }}</span>
            </li>
            <li class="list-group-item">
                <b>Discord</b> <span class="float-right">{{ $user->discord_name ?? '-' }}</span>
            </li>
            <li class="list-group-item">
                <b>Forum</b> <span class="float-right">{{ $user->forum_name ?? '-' }}</span>
            </li>
             <li class="list-group-item">
                <b>Einstellungsdatum</b> <span class="float-right">{{ $user->hire_date ? \Carbon\Carbon::parse($user->hire_date)->format('d.m.Y') : '-' }}</span>
            </li>
        </ul>
    </div>
</div>