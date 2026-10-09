<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    /**
     * Individuelle Fragen bearbeiten oder löschen: nur im aktuellen Lager.
     */
    public function manage(User $user, Question $question): bool
    {
        return $question->camp_id !== null && (int) $question->camp_id === (int) $user->camp_id;
    }
}
