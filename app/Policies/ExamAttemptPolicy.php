<?php
namespace App\Policies;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ExamAttemptPolicy
{
    /**
     * Determine whether the user can take the given exam attempt.
     */
    public function take(User $user, ExamAttempt $attempt): bool
    {
        // A user can take their own exam if it's in progress.
        return $user->id === $attempt->user_id && $attempt->status === 'in_progress';
    }

    /**
     * NEU: Determine whether the user can submit the given exam attempt.
     */
    public function submit(User $user, ExamAttempt $attempt): bool
    {
        // The logic is the same: A user can submit their own attempt if it is currently in progress.
        return $user->id === $attempt->user_id && $attempt->status === 'in_progress';
    }

    /**
     * NEU: Determine whether the user can update the given exam attempt.
     * This is often used as an alias for the 'submit' action in controllers.
     */
    public function update(User $user, ExamAttempt $attempt): bool
    {
        return $this->submit($user, $attempt);
    }

    /**
     * Determine whether the user can view the result of the exam attempt.
     */
    public function viewResult(User $user, ExamAttempt $attempt): bool
    {
        // Debug-Ausgabe: Wer versucht zuzugreifen?
        Log::info('POLICY DEBUG: ViewResult Check Started.');
        Log::info('User ID: ' . $user->id . ' | Attempt User ID: ' . $attempt->user_id);
        
        // 1. Bedingung: Ist der angemeldete User der Besitzer des Versuchs?
        $isOwner = ($user->id == $attempt->user_id);
        Log::info('Check 1 (Is Owner): ' . ($isOwner ? 'TRUE' : 'FALSE'));
        
        // 2. Bedingung: Hat der User die Super-Admin Rolle?
        $isSuperAdmin = $user->hasRole('Super-Admin');
        Log::info('Check 2 (Super-Admin Role Found): ' . ($isSuperAdmin ? 'TRUE' : 'FALSE'));
        
        // 3. Bedingung: Hat der User die Berechtigung 'evaluations.view.all'?
        $canViewAll = $user->can('evaluations.view.all');
        Log::info('Check 3 (Can View All Evals): ' . ($canViewAll ? 'TRUE' : 'FALSE'));

        $result = $isSuperAdmin || $canViewAll || $isOwner;
        
        Log::info('FINAL POLICY RESULT: ' . ($result ? 'ALLOWED' : 'DENIED'));

        return $result;
    }

    /**
     * Determine whether an admin/authorized user can generate a new exam link.
     */
    public function generateExamLink(User $user): bool
    {
        return $user->can('exams.generatelinks');
    }
}
