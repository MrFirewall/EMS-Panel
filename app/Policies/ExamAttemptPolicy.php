<?php
namespace App\Policies;
use App\Models\ExamAttempt;
use App\Models\User;

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
        return $user->hasRole('Super-Admin') || $user->can('evaluations.view.all') || $user->id == $attempt->user_id;
    }

    /**
     * Determine whether an admin/authorized user can generate a new exam link.
     */
    public function generateExamLink(User $user): bool
    {
        return $user->can('exams.generatelinks');
    }
        /**
     * Determine whether the user can reset an exam attempt.
     */
    public function resetAttempt(User $user, ExamAttempt $attempt): bool
    {
        // Erlaube nur Super-Admins oder Benutzern mit der passenden Berechtigung
        return $user->hasRole('Super-Admin') || $user->can('exams.manage'); 
    }

    // NEU: Berechtigung, einen Versuch manuell zu bewerten
    public function setEvaluated(User $user, ExamAttempt $attempt): bool
    {
        return $user->hasRole('Super-Admin') || $user->can('exams.manage');
    }

    // NEU: Berechtigung, den Link manuell zu senden (Admin-Funktion)
    public function sendLink(User $user, ExamAttempt $attempt): bool
    {
        return $user->hasRole('Super-Admin') || $user->can('exams.generatelinks');
    }
}
