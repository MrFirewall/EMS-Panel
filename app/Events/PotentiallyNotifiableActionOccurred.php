<?php
// app/Events/PotentiallyNotifiableActionOccurred.php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class PotentiallyNotifiableActionOccurred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $controllerAction; // z.B. 'EvaluationController@store'
    public User $triggeringUser;   // Der Benutzer, der die Aktion ausgelöst hat (oder der betroffene Benutzer)
    public $relatedModel;         // Das betroffene Model (Evaluation, TrainingModule etc.)
    public ?User $actorUser;        // Optional: Der Admin/Benutzer, der die Aktion *durchgeführt* hat
    
    // --- KORREKTUR START ---
    /**
     * Zusätzliche Daten, die vom Controller übergeben werden (z.B. formatierte Beschreibung).
     * @var array
     */
    public array $additionalData;
    // --- KORREKTUR ENDE ---


    /**
     * Create a new event instance.
     * @param string $controllerAction
     * @param User $triggeringUser Der primär betroffene oder auslösende User
     * @param mixed $relatedModel Das zugehörige Model
     * @param User|null $actorUser Der optional handelnde User (z.B. Admin bei Zuweisung)
     * @param array $additionalData // --- KORREKTUR: 5. Argument hinzugefügt ---
     */
    public function __construct(string $controllerAction, User $triggeringUser, $relatedModel, ?User $actorUser = null, array $additionalData = [])
    {
        $this->controllerAction = $controllerAction;
        $this->triggeringUser = $triggeringUser;
        $this->relatedModel = $relatedModel;
        $this->actorUser = $actorUser ?? $triggeringUser; // Fallback auf triggeringUser, wenn kein expliziter Akteur
        $this->additionalData = $additionalData;
        // --- KORREKTUR ENDE ---
    }
}
