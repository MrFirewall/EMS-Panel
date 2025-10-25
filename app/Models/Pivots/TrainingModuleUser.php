<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TrainingModuleUser extends Pivot
{
    /**
     * Definiert die Beziehung zum User-Model (der Zuweisende).
     */
    public function assigner()
    {
        // 'assigned_by_user_id' ist der Fremdschlüssel in der Pivot-Tabelle,
        // der auf die 'id' in der 'users'-Tabelle verweist.
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}