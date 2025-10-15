@extends('layouts.app') {{-- Oder dein Admin-Layout --}}

@section('title', 'Ankündigungen verwalten')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Alle Ankündigungen</h3>
        <div class="card-tools">
            @can('announcements.create')
                <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Neue Ankündigung
                </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Erstellt von</th>
                    <th>Status</th>
                    <th>Erstellt am</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($announcements as $announcement)
                    <tr>
                        <td>{{ $announcement->title }}</td>
                        <td>{{ $announcement->user->name ?? 'Unbekannt' }}</td>
                        <td>
                            @if ($announcement->is_active)
                                <span class="badge badge-success">Aktiv</span>
                            @else
                                <span class="badge badge-secondary">Inaktiv</span>
                            @endif
                        </td>
                        <td>{{ $announcement->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @can('announcements.edit')
                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-warning btn-xs">Bearbeiten</a>
                            @endcan
                            @can('announcements.delete')
                                <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" class="d-inline" onsubmit="return confirm('Sicher löschen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs">Löschen</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Keine Ankündigungen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection