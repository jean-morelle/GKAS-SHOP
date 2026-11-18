<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Crée un compte avec un mot de passe temporaire aléatoire, à changer
     * obligatoirement à la première connexion.
     *
     * @return array{0: User, 1: string} [utilisateur, mot de passe temporaire en clair à communiquer une seule fois]
     */
    public function create(array $data, UserRole $role): array
    {
        $password = $this->temporaryPassword();

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $password,
        ]);
        $user->role = $role;
        $user->is_active = true;
        $user->must_change_password = true;
        $user->save();

        ActivityLogger::log('user.created', "Création du compte {$user->email} ({$role->label()})", $user);

        return [$user, $password];
    }

    public function changeRole(User $user, UserRole $role): void
    {
        if ($user->role === $role) {
            return;
        }

        $previous = $user->role;
        $user->role = $role;
        $user->save();

        ActivityLogger::log('user.role_changed', "Rôle de {$user->email} : {$previous->label()} → {$role->label()}", $user, [
            'from' => $previous->value,
            'to' => $role->value,
        ]);
    }

    public function setActive(User $user, bool $active): void
    {
        $user->is_active = $active;
        $user->save();

        ActivityLogger::log($active ? 'user.activated' : 'user.deactivated', ($active ? 'Activation' : 'Désactivation')." du compte {$user->email}", $user);
    }

    /** Génère un nouveau mot de passe temporaire et invalide les sessions « se souvenir de moi ». */
    public function resetAccess(User $user): string
    {
        $password = $this->temporaryPassword();
        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
            'remember_token' => Str::random(60),
        ])->save();

        ActivityLogger::log('user.access_reset', "Réinitialisation de l'accès de {$user->email}", $user);

        return $password;
    }

    private function temporaryPassword(): string
    {
        // 12 caractères lisibles + chiffres, sans caractères ambigus.
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';
        for ($i = 0; $i < 12; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password.random_int(10, 99);
    }
}
