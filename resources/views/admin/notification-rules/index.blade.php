@extends('layouts.app') {{-- Passe dies an dein Admin-Layout an --}}
@section('title', 'Benachrichtigungsregeln verwalten')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-cogs nav-icon"></i> Benachrichtigungsregeln verwalten</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                         <li class="breadcrumb-item active">Benachrichtigungsregeln</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Übersicht Benachrichtigungsregeln</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.notification-rules.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Neue Regel
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 25%">Aktion</th>
                                <th>Beschreibung</th>
                                <th>Zieltyp</th>
                                <th>Ziel</th>
                                <th class="text-center">Aktiv</th>
                                <th style="width: 100px">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rules as $rule)
                                <tr>
                                    <td><code>{{ $rule->controller_action }}</code></td>
                                    <td>{{ $rule->event_description }}</td>
                                    <td>{{ ucfirst($rule->target_type) }}</td>
                                    <td>
                                        {{-- Optional: Zeige Benutzername statt ID an --}}
                                        @if($rule->target_type === 'user')
                                            {{ \App\Models\User::find($rule->target_identifier)?->name ?? $rule->target_identifier }}
                                        @else
                                            {{ $rule->target_identifier }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($rule->is_active)
                                            <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                        @else
                                            <span class="badge badge-secondary"><i class="fas fa-times"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.notification-rules.edit', $rule) }}" class="btn btn-info btn-xs" title="Bearbeiten"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('admin.notification-rules.destroy', $rule) }}" method="POST" style="display:inline;" onsubmit="return confirm('Regel wirklich löschen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-xs" title="Löschen"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Keine Regeln definiert.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($rules->hasPages())
                    <div class="card-footer clearfix">
                        {{ $rules->links('vendor.pagination.bootstrap-4') }} {{-- Stelle sicher, dass du Bootstrap 4 Pagination verwendest --}}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection