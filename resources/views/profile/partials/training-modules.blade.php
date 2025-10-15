<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book-open me-2"></i> Abgeschlossene Module</h3>
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
                @forelse($trainingModules as $module)
                    <tr>
                        <td>{{ $module->date ? \Carbon\Carbon::parse($module->date)->format('d.m.Y') : '-' }}</td>
                        <td>{{ $module->module_name }}</td>
                        <td>{{ $module->instructor_name }}</td>
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