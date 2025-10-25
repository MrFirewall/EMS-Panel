<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Zugewiesene Module</h5>
    </div>
    <div class="card-body">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th scope="col" style="width: 15%;">Datum</th>
                    <th scope="col" style="width: 55%;">Modul</th>
                    <th scope="col" style="width: 30%;">Ausbilder</th>
                </tr>
            </thead>
            <tbody>
                {{-- Die $user Variable kommt vom ProfileController --}}
                @forelse($user->trainingModules as $module)
                <tr>
                    <td>{{ $module->pivot->created_at ? \Carbon\Carbon::parse($module->pivot->created_at)->format('d.m.Y') : '-' }}</td>
                    <td>{{ $module->name }}</td>
                    <td>
                        @if ($module->pivot->assigned_by_user_id)
                            Zugewiesen (ID: {{ $module->pivot->assigned_by_user_id }})
                        @else
                            <span class="badge bg-info text-dark">Anmeldung</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">Keine Moduleinträge.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
