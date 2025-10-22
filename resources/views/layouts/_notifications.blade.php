{{-- Benachrichtigungs-Header --}}
<div class="d-flex justify-content-between align-items-center dropdown-header">
    <span>{{ count($notifications) }} Neue Meldungen</span>

    {{-- NEU: Button "Alle als gelesen markieren" --}}
    @if(count($notifications) > 0)
    <form action="{{ route('notifications.markAllRead') }}" method="POST" class="m-0 p-0">
        @csrf
        <button type="submit" class="btn btn-xs btn-success" title="Alle als gelesen markieren">
            <i class="fas fa-check-double"></i>
        </button>
    </form>
    @endif
</div>

{{-- Benachrichtigungs-Liste --}}
@forelse ($notifications as $notification)
    <div class="dropdown-divider"></div>
    <a href="{{ $notification['url'] ?? '#' }}" class="dropdown-item">
        <i class="{{ $notification['icon'] }} mr-2"></i> {{ $notification['text'] }}
        <span class="float-right text-muted text-sm">{{ $notification['time'] }}</span>
    </a>
@empty
    <div class="dropdown-divider"></div>
    <a href="#" class="dropdown-item">
        <span class="text-muted">Keine neuen Meldungen.</span>
    </a>
@endforelse

{{-- Benachrichtigungs-Footer --}}
<div class="dropdown-divider"></div>
{{-- KORRIGIERT: Link zum neuen Archiv --}}
<a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer">Alle Benachrichtigungen anzeigen</a>