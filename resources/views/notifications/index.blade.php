@extends('layouts.app')

@section('title', 'Benachrichtigungs-Archiv')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Benachrichtigungs-Archiv</h3>
                    
                    {{-- Nur anzeigen, wenn es ungelesene gibt --}}
                    @if($unreadCount > 0)
                    <div class="card-tools">
                        <form action="{{ route('notifications.markAllRead') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check-double mr-1"></i>
                                Alle als gelesen markieren ({{ $unreadCount }})
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse ($allNotifications as $notification)
                            <li class="list-group-item d-flex justify-content-between align-items-center {{ $notification->read_at ? '' : 'font-weight-bold' }}">
                                
                                {{-- Linke Seite: Icon, Text und Zeit --}}
                                <div>
                                    <a href="{{ $notification->data['url'] ?? '#' }}" class="text-dark">
                                        <i class="{{ $notification->data['icon'] ?? 'fas fa-bell' }} mr-3"></i>
                                        <span>{{ $notification->data['text'] ?? '...' }}</span>
                                    </a>
                                    <small class="d-block text-muted ml-4 pl-3 mt-1">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </small>
                                </div>

                                {{-- Rechte Seite: Löschen-Button --}}
                                <div>
                                    <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" onsubmit="return confirm('Möchten Sie diese Benachrichtigung wirklich löschen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" title="Löschen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">
                                Keine Benachrichtigungen vorhanden.
                            </li>
                        @endforelse
                    </ul>
                </div>
                
                {{-- Paginierungs-Links --}}
                @if($allNotifications->hasPages())
                <div class="card-footer">
                    {{ $allNotifications->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
