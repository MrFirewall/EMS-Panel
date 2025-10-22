{{-- Benachrichtigungs-Header --}}
<div class="d-flex justify-content-between align-items-center dropdown-header">
    {{-- Zeigt die Gesamtanzahl der Benachrichtigungen an --}}
    <span>{{ collect($notifications)->sum('count') }} Neue Meldungen</span> 

    {{-- Button "Alle als gelesen markieren" beibehalten --}}
    @if(collect($notifications)->sum('count') > 0)
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
    <form action="{{ route('notifications.markAsRead', $notification['id']) }}" method="POST" class="m-0 p-0 dropdown-item-form">
        @csrf
        <button type="submit" class="dropdown-item p-0 border-0 bg-transparent text-left w-100">
            {{-- Der Text ist bereits im Controller für die Gruppierung zusammengefasst --}}
            <i class="{{ $notification['icon'] }} mr-2"></i> {{ $notification['text'] }}
            <span class="float-right text-muted text-sm">{{ $notification['time'] }}</span>
        </button>
    </form>
@empty
    <div class="dropdown-divider"></div>
    <a href="#" class="dropdown-item">
        <span class="text-muted">Keine neuen Meldungen.</span>
    </a>
@endforelse

{{-- Benachrichtigungs-Footer --}}
<div class="dropdown-divider"></div>
<a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer">Alle Benachrichtigungen anzeigen</a>