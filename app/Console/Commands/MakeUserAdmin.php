<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {email : The email address of the account to promote} {--demote : Return the account to a regular user}';

    protected $description = 'Grant or revoke admin access to the CMS for an existing account';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No account found for {$email}.");

            return self::FAILURE;
        }

        $role = $this->option('demote') ? 'user' : 'admin';

        $user->forceFill(['role' => $role])->save();

        $this->info("{$email} is now a {$role}.");

        return self::SUCCESS;
    }
}
