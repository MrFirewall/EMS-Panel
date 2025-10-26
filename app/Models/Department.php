<?php

namespace App\Models;

// FÜGE DIESE ZEILE HINZU:
use Illuminate\Database\Eloquent\Factories\HasFactory; 

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class Department extends Model
{
    use HasFactory; // Diese Zeile funktioniert jetzt
    
    // Mass-Assignment erlauben
    protected $fillable = [
        'name', 
        'leitung_role_name', 
        'min_rank_level_to_assign_leitung'
    ];

    /**
     * Die Spatie-Rollen, die zu dieser Abteilung gehören.
     */
    public function roles()
    {
        // Wichtig: Den Spatie-Role-Pfad hier nutzen (App\Models\Role oder Spatie\...)
        return $this->belongsToMany(config('permission.models.role'), 'department_role');
    }
}