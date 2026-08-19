<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Console\Command;

/**
 * First-boot content, safe to run on every deploy.
 *
 * Templates are only seeded into an empty table: the seeder matches on slug and
 * overwrites, so re-running it after launch would silently discard any edit an
 * admin made to a starter template through the CMS.
 */
class Provision extends Command
{
    protected $signature = 'app:provision {--force-templates : Re-seed the starter templates, discarding CMS edits to them}';

    protected $description = 'Seed starter templates and the admin account if they are not there yet';

    public function handle(): int
    {
        if (! User::where('role', 'admin')->exists()) {
            $this->callSilently('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true]);
            $this->info('Created the admin account from ADMIN_EMAIL.');
        } else {
            $this->line('Admin account already exists, leaving it alone.');
        }

        $templateCount = EmailTemplate::count();

        if ($templateCount === 0 || $this->option('force-templates')) {
            $this->callSilently('db:seed', ['--class' => EmailTemplateSeeder::class, '--force' => true]);
            $this->info('Seeded the starter templates.');
        } else {
            $this->line("{$templateCount} templates already present, leaving them alone.");
        }

        return self::SUCCESS;
    }
}
