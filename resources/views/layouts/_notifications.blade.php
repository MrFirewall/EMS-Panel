{{-- Die Variable $groupedNotifications enthält die Gruppen (Level 1) --}}
{{-- Die Variable $totalCount wird vom Controller übergeben und enthält die GESAMTE Anzahl --}}

{{-- Benachrichtigungs-Header --}}
<div class="d-flex justify-content-between align-items-center dropdown-header">
    {{-- Zeigt die Gesamtanzahl der Benachrichtigungen an --}}
    <span>{{ $totalCount ?? 0 }} Neue Meldungen</span> 

    {{-- Button "Alle als gelesen markieren" beibehalten --}}
    @if(($totalCount ?? 0) > 0)
    <form action="{{ route('notifications.markAllRead') }}" method="POST" class="m-0 p-0">
        @csrf
        <button type="submit" class="btn btn-xs btn-success" title="Alle als gelesen markieren">
            <i class="fas fa-check-double"></i>
        </button>
    </form>
    @endif
</div>

{{-- Benachrichtigungs-Liste (Level 1: Gruppen) --}}
@forelse ($groupedNotifications as $group)
    @php
        // Eindeutige ID für das Collapse-Element generieren (basierend auf dem Index im Loop)
        $collapseId = 'group-collapse-' . $loop->index;
    @endphp

    <div class="dropdown-divider"></div>
    
    {{-- GRUPPEN-TITEL (Klickbarer Toggle-Link) --}}
    <a href="#{{ $collapseId }}" 
       class="dropdown-item dropdown-item-group-title text-sm text-info font-weight-bold d-flex justify-content-between align-items-center"
       data-toggle="collapse" 
       role="button" 
       aria-expanded="false" 
       aria-controls="{{ $collapseId }}">
        <span>
            <i class="{{ $group['group_icon'] }} mr-2"></i> {{ $group['group_title'] }}
        </span>
        {{-- Chevron-Icon für visuelles Feedback (rotiert durch Bootstrap/AdminLTE CSS) --}}
        <i class="fas fa-chevron-down ml-auto"></i>
    </a>

    {{-- Level 2: Einzelne Benachrichtigungen innerhalb der Gruppe (Collapsable Area) --}}
    <div class="collapse" id="{{ $collapseId }}">
        @foreach ($group['items'] as $notification)
            {{-- Kleinere Trennlinie für die Übersichtlichkeit --}}
            <div class="dropdown-divider my-0"></div>

            {{-- Jede einzelne Nachricht ist ein Formular/Link, um sie individuell als gelesen zu markieren --}}
            <form action="{{ route('notifications.markAsRead', $notification['id']) }}" method="POST" class="m-0 p-0 dropdown-item-form">
                @csrf
                {{-- Der Button/Link muss die gesamte Dropdown-Item-Fläche ausfüllen --}}
                <button type="submit" class="dropdown-item p-0 border-0 bg-transparent text-left w-100 pl-4 py-2">
                    {{-- Pl-4 (Padding-Left) rückt die Einzelnachrichten leicht ein, um die Hierarchie zu betonen --}}
                    <i class="far fa-circle text-info mr-2" style="font-size: 0.6rem;"></i> {{ $notification['text'] }}
                    <span class="float-right text-muted text-sm">{{ $notification['time'] }}</span>
                </button>
            </form>
        @endforeach
    </div>

@empty
    <div class="dropdown-divider"></div>
    <a href="#" class="dropdown-item">
        <span class="text-muted">Keine neuen Meldungen.</span>
    </a>
@endforelse

{{-- Benachrichtigungs-Footer --}}
<div class="dropdown-divider"></div>
<a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer">Alle Benachrichtigungen anzeigen</a>
