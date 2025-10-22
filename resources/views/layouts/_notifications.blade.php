{{-- Benachrichtigungs-Header --}}
<li class="dropdown-header">{{ count($notifications) }} Neue Benachrichtigungen</li>

{{-- Benachrichtigungs-Liste --}}
<div class="dropdown-divider"></div>
@forelse ($notifications as $notification)
    <li>
        <a href="{{ $notification['url'] ?? '#' }}" class="dropdown-item">
            {{-- Das Icon und die Farbe kommen direkt aus dem Controller --}}
            <i class="{{ $notification['icon'] }} mr-2"></i> {{ $notification['text'] }}
            <span class="float-right text-muted text-sm">{{ $notification['time'] }}</span>
        </a>
    </li>
    @if (!$loop->last)
        <div class="dropdown-divider"></div>
    @endif
@empty
    <li class="dropdown-item">
        <span class="text-muted">Keine neuen Meldungen.</span>
    </li>
@endforelse

{{-- Benachrichtigungs-Footer --}}
<div class="dropdown-divider"></div>
<li class="dropdown-footer">
    <a href="#" class="text-sm text-primary">Alle Auswertungen anzeigen <i class="fas fa-arrow-circle-right ml-1"></i></a>
</li>
