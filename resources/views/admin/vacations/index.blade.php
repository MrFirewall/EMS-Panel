@extends('layouts.app')

@section('title', 'Urlaubsanträge Verwaltung')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0"><i class="fas fa-calendar-check me-2"></i> Urlaubsanträge Verwaltung</h1>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        </div>
    @endif

    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title">Alle Anträge ({{ $vacations->total() }})</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mitarbeiter</th>
                            <th>Zeitraum</th>
                            <th>Tage (ca.)</th>
                            <th>Status</th>
                            <th>Grund</th>
                            <th class="text-right">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vacations as $vacation)
                            <tr>
                                <td>{{ $vacation->id }}</td>
                                <td>
                                    <a href="{{ route('admin.users.show', $vacation->user_id) }}">{{ $vacation->user->name ?? 'Unbekannt' }}</a>
                                    <small class="text-muted d-block">PN: {{ $vacation->user->personal_number ?? '-' }}</small>
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($vacation->start_date)->format('d.m.Y') }} - 
                                    {{ \Carbon\Carbon::parse($vacation->end_date)->format('d.m.Y') }}
                                </td>
                                <td>
                                    @php
                                        $startDate = \Carbon\Carbon::parse($vacation->start_date);
                                        $endDate = \Carbon\Carbon::parse($vacation->end_date);
                                        // Calculates working days (Mon-Fri)
                                        $days = $startDate->diffInDaysFiltered(fn(\Carbon\Carbon $date) => !$date->isWeekend(), $endDate) + 1;
                                    @endphp
                                    {{ $days }}
                                </td>
                                <td>
                                    @if($vacation->status === 'approved')
                                        <span class="badge bg-success">Genehmigt</span>
                                    @elseif($vacation->status === 'rejected')
                                        <span class="badge bg-danger">Abgelehnt</span>
                                    @else
                                        <span class="badge bg-warning">Ausstehend</span>
                                    @endif
                                    @if($vacation->approved_by)
                                        <small class="text-muted d-block" title="Bearbeitet von">
                                            <i class="fas fa-check-circle"></i> {{ $vacation->approver->name ?? '-' }}
                                        </small>
                                    @endif
                                </td>
                                <td style="max-width: 250px;">{{ Str::limit($vacation->reason, 50) }}</td>
                                <td class="text-right">
                                    @if($vacation->status === 'pending')
                                        {{-- The action buttons are now protected by the 'vacations.manage' permission --}}
                                        @can('vacations.manage')
                                            {{-- Approve Button --}}
                                            <form action="{{ route('admin.vacations.update.status', $vacation) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="btn btn-sm btn-success btn-flat" title="Genehmigen">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>

                                            {{-- Reject Button --}}
                                            <form action="{{ route('admin.vacations.update.status', $vacation) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn btn-sm btn-danger btn-flat" title="Ablehnen">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @else
                                        <span class="text-muted small">Abgeschlossen</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Keine Urlaubsanträge gefunden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $vacations->links() }}
        </div>
    </div>
@endsection