<div class="card card-info card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-star me-2"></i> Bewertungsübersicht</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 table-striped">
            <thead>
                <tr>
                    <th>Kategorie</th>
                    <th class="text-center">Erhalten</th>
                    <th class="text-center">Verfasst</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $labels = ['azubi' => 'Azubi', 'leitstelle' => 'Leitstelle', 'mitarbeiter' => 'Mitarbeiter', 'praktikant' => 'Praktikant'];
                @endphp
                @foreach($labels as $type => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="text-center">{{ $evaluationCounts['erhalten'][$type] ?? 0 }}</td>
                        <td class="text-center">{{ $evaluationCounts['verfasst'][$type] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>