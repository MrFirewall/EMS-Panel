<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cogs me-2"></i> Metadaten</h3>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-unbordered">
            <li class="list-group-item">
                <b>Letzte Änderung</b> <span class="float-right">{{ $user->last_edited_at ? \Carbon\Carbon::parse($user->last_edited_at)->format('d.m.Y - H:i') : '-' }}</span>
            </li>
            <li class="list-group-item">
                <b>Bearbeiter</b> <span class="float-right">{{ $user->last_edited_by ?? '-' }}</span>
            </li>
        </ul>
    </div>
</div>