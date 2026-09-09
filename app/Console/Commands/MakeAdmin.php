<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'pokercoinverter:make-admin {email} {--revoke : Remove admin instead of granting it}';

    protected $description = 'Grant (or revoke) admin access for a user by email';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $user->is_admin = ! $this->option('revoke');
        $user->save();

        $this->info(($user->is_admin ? 'Granted' : 'Revoked')." admin for {$user->email}.");

        return self::SUCCESS;
    }
}
