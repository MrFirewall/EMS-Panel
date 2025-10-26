<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /** Die Abteilungen, zu denen diese Rolle gehört. */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_role');
    }
}