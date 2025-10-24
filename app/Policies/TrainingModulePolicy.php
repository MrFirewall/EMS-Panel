<?php

namespace App\Policies;

use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TrainingModulePolicy
{
public function viewAny(User $user): bool { return $user->can('training.view'); }
public function view(User $user): bool { return $user->can('training.view'); }
public function create(User $user): bool { return $user->can('training.create'); }
public function update(User $user, TrainingModule $trainingModule): bool { return $user->can('training.edit'); }
public function delete(User $user, TrainingModule $trainingModule): bool { return $user->can('training.delete'); }
public function assignUser(User $user): bool { return $user->can('training.assign'); }
}
 
