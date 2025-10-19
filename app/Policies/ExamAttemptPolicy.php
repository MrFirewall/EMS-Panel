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
        // DEBUG-LOGIK START
        $debugData = [
            '*** POLICY DEBUG ***' => 'viewResult Check',
            '1. Angemeldete User ID ($user->id)' => $user->id,
            '2. Attempt User ID ($attempt->user_id)' => $attempt->user_id,
            '3. IDs stimmen überein (==)' => ($user->id == $attempt->user_id),
            '4. IDs stimmen überein (===)' => ($user->id === $attempt->user_id),
            '5. Ist Super-Admin ($user->hasRole)' => $user->hasRole('Super-Admin'),
            '6. Kann alles einsehen ($user->can)' => $user->can('evaluations.view.all'),
        ];
        
        // Stoppt die Ausführung und zeigt die Variablen.
        // DIESE ZEILE MUSS ENTFERNT WERDEN, SOBALD DER FEHLER GEFUNDEN WURDE!
        dd($debugData); 
        
        // DEBUG-LOGIK ENDE
        
        // Original-Return-Logik (wird von dd() blockiert)
        return $user->hasRole('Super-Admin') || $user->can('evaluations.view.all') || $user->id === $attempt->user_id;
    }
    /**
     * Determine whether an admin/authorized user can generate a new exam link.
     */
    public function generateExamLink(User $user): bool
    {
        return $user->can('exams.generatelinks');
    }
}
