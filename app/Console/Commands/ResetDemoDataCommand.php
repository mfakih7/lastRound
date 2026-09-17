<?php

namespace App\Console\Commands;

use App\Services\DemoDataResetService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('lastround:reset-demo-data {--dry-run : Show what would be deleted without changing any data} {--force : Skip confirmation prompts}')]
#[Description('Delete demo and business data while preserving the admin account and system settings')]
class ResetDemoDataCommand extends Command
{
    public function handle(DemoDataResetService $reset): int
    {
        try {
            $admins = $reset->admins();

            if ($admins->isEmpty()) {
                $this->components->error('No admin user exists. Aborting so you are not locked out.');

                return self::FAILURE;
            }

            $counts = $reset->preview();
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($this->laravel->environment('production') && ! $dryRun) {
            $this->components->alert('Application In Production');
            $this->warn('This permanently deletes clients, coaches, packages, purchases, and sessions.');
            $this->warn('The existing admin account (username, email, and password hash) and settings are kept.');
        }

        $this->info($dryRun
            ? 'Dry run — the following records would be deleted:'
            : 'The following records will be deleted:');

        $this->table(
            ['Record', 'Count'],
            collect($counts)
                ->map(fn (int $count, string $name): array => [$name, $count])
                ->values()
                ->all(),
        );

        $this->info('Preserved admin user(s): '.$admins->pluck('username')->implode(', '));
        $this->info('Settings, roles, and the admin coach profile(s) are left unchanged.');

        if ($dryRun) {
            $this->components->info('No records were deleted.');

            return self::SUCCESS;
        }

        if (! $this->confirmed()) {
            $this->components->warn('Command cancelled.');

            return self::FAILURE;
        }

        $deleted = $reset->reset();

        $this->table(
            ['Record', 'Deleted'],
            collect($deleted)
                ->map(fn (int $count, string $name): array => [$name, $count])
                ->values()
                ->all(),
        );

        $this->components->info('Demo data reset complete. The admin password was not changed.');

        return self::SUCCESS;
    }

    protected function confirmed(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $question = $this->laravel->environment('production')
            ? 'Application is in production. Permanently delete all demo/business data while keeping the admin account?'
            : 'Delete all demo/business data while preserving the admin account?';

        return $this->confirm($question, false);
    }
}
