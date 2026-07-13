<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Maison;

class MaisonPolicy
{
    public function before(User $user, string $ability)
    {
        if ($user->hasRole(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return true;
        }
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ROLE_ENQUETEUR');
    }

    public function view(User $user, Maison $maison): bool
    {
        return $user->agent && $maison->enqueteur_id === $user->agent->id;
    }

    public function update(User $user, Maison $maison): bool
    {
        return $user->agent && $maison->enqueteur_id === $user->agent->id;
    }

    public function delete(User $user, Maison $maison): bool
    {
        return $user->agent && $maison->enqueteur_id === $user->agent->id;
    }
}
