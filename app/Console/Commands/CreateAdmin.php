<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Création sécurisée d'un administrateur : le mot de passe est saisi de
 * manière masquée et n'est jamais écrit dans le code ni dans les seeders.
 */
class CreateAdmin extends Command
{
    protected $signature = 'gkas:create-admin {--email=} {--name=}';

    protected $description = 'Crée un compte administrateur GKAS-GROUP (mot de passe saisi de façon masquée)';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nom complet');
        $email = $this->option('email') ?: $this->ask('Adresse e-mail');
        $password = $this->secret('Mot de passe (10 caractères min., majuscules, minuscules et chiffres)');
        $confirmation = $this->secret('Confirmez le mot de passe');

        $validator = Validator::make(
            compact('name', 'email', 'password') + ['password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->role = UserRole::Admin;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        ActivityLogger::log('user.created', "Administrateur {$email} créé en ligne de commande", $user);
        $this->info("Administrateur {$email} créé. Connectez-vous sur ".route('login'));

        return self::SUCCESS;
    }
}
