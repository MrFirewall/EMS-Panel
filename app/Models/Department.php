<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class Department extends Model
{
    use HasFactory;
    
    // Mass-Assignment erlauben
    protected $fillable = [
        'name', 
        'leitung_role_name', 
        'min_rank_level_to_assign_leitung'
    ];

    public function roles()
    {
        return $this->belongsToMany(config('permission.models.role'), 'department_role');
    }
}
