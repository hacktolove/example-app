<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:create {--email= : Email address} {--password= : Password} {--name= : Display name}')]
#[Description('Create an administrator account for signing in to the dashboard')]
class CreateAdmin extends Command
{
    use PasswordValidationRules;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $scripted = (bool) $this->option('email');
        $email = $this->option('email') ?: text('Email address', required: true);

        if (! $this->emailIsValid($email)) {
            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing && ! $this->confirmPasswordReset($email)) {
            $this->components->warn('No changes made.');

            return self::FAILURE;
        }

        [$plain, $confirmation] = $this->readPassword();

        if (! $this->passwordIsValid($plain, $confirmation)) {
            return self::FAILURE;
        }

        $name = $this->option('name')
            ?? null;

        $name ??= $existing->name
            ?? ($scripted ? 'Admin' : text('Name', default: 'Admin'));

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($plain)]
        );

        // `email_verified_at` is deliberately not mass-assignable on User, and an
        // account created from the console has no verification link to click.
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->components->info(
            $existing
                ? "Password updated for {$user->email}."
                : "Administrator {$user->email} created."
        );

        return self::SUCCESS;
    }

    private function emailIsValid(string $email): bool
    {
        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email']]);

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first('email'));

            return false;
        }

        return true;
    }

    private function confirmPasswordReset(string $email): bool
    {
        if ($this->option('password')) {
            return true;
        }

        return confirm(
            label: "{$email} already exists. Set a new password for them?",
            default: false,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function readPassword(): array
    {
        if ($given = $this->option('password')) {
            return [$given, $given];
        }

        return [
            password('Password', required: true),
            password('Confirm password', required: true),
        ];
    }

    /**
     * Validated against the same policy the web sign-up flow uses.
     */
    private function passwordIsValid(string $password, string $confirmation): bool
    {
        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirmation],
            ['password' => $this->passwordRules()]
        );

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first('password'));

            return false;
        }

        return true;
    }
}
