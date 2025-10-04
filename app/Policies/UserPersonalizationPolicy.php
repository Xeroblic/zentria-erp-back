<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserPersonalization;

class UserPersonalizationPolicy
{
    public function view(User $user, UserPersonalization $personalization): bool
    {
        return $user->id === $personalization->user_id;
    }

    public function update(User $user, UserPersonalization $personalization): bool
    {
        return $user->id === $personalization->user_id;
    }
}