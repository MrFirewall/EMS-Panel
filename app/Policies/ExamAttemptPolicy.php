<?php
namespace App\Policies;
use App\Models\ExamAttempt;
use App\Models\User;
class ExamAttemptPolicy
{
    public function take(User $user, ExamAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id && $attempt->status === 'in_progress';
    }
    public function viewResult(User $user, ExamAttempt $attempt): bool
    {
        return $user->hasRole('Super-Admin') || $user->can('evaluations.view.all') || $user->id === $attempt->user_id;
    }
    public function generateExamLink(User $user): bool
    {

        return $user->can('exams.generatelinks');
    }
}

