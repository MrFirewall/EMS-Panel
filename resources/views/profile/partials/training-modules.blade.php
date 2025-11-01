<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-signature me-2"></i> Module</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 table-striped">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Modul</th>
                    <th>Ausbilder</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $assignedModules = $user->trainingModules->filter(function($module) {
                        return !is_null($module->pivot->assigned_by_user_id);
                    });
                @endphp

                @forelse($assignedModules as $module)
                <tr>
                    <td>{{ $module->pivot->created_at ? \Carbon\Carbon::parse($module->pivot->created_at)->format('d.m.Y') : '-' }}</td>
                    <td>{{ $module->name }}</td>
                    <td>
                        {{ $module->pivot->assigner->name ?? 'System' }}
                    </td>
                </tr>
                @empty
                <tr>
                    {{-- Text angepasst an den neuen Filter --}}
                    <td colspan="3" class="text-center text-muted">Keine bestandenen Moduleinträge.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
