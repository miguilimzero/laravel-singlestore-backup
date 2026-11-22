<?php

namespace Miguilim\LaravelSinglestoreBackup\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Miguilim\LaravelSinglestoreBackup\Console\Concerns\InteractsWithBackupDisk;
use Throwable;

class SinglestoreIncrementalBackupCommand extends Command
{
    use InteractsWithBackupDisk;

    /**
     * Calendar periods supported by the optimization interval, mapped to the
     * date format that identifies the period a date belongs to.
     */
    protected const PERIOD_FORMATS = [
        'daily'   => 'Y-m-d',
        'weekly'  => 'o-W',
        'monthly' => 'Y-m',
        'yearly'  => 'Y',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'singlestore:incremental-backup {--timeout=} {--optimize-every=} {--optimize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run an incremental backup, periodically replacing it with a fresh one';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (config('singlestore-backup.driver') !== 's3') {
            $this->error('This command can only be executed with "s3" driver.');

            return Command::FAILURE;
        }

        $interval = $this->option('optimize-every') ?? 'monthly';

        if (! $this->isValidInterval($interval)) {
            $this->error('Invalid optimization interval "'.$interval.'". Use "daily", "weekly", "monthly", "yearly" or a number of days.');

            return Command::FAILURE;
        }

        $disk = $this->buildBackupDisk();

        [$name, $initializedAt] = $this->getCurrentBackup($disk);

        if (! $name) {
            $this->warn('No incremental backup found. Starting backup initialization...');

            return $this->initBackup($disk);
        }

        if ($this->option('optimize') || ($initializedAt && $this->isOptimizationDue($initializedAt, $interval))) {
            return $this->optimizeBackup($disk, $name);
        }

        // An incremental backup created outside this command has no known age,
        // so the optimization interval starts counting from now.
        if (! $initializedAt) {
            $this->putCurrentBackup($disk, $name);
        }

        return $this->differentialBackup($name);
    }

    /**
     * Create a new INIT backup and only then delete the previous incremental
     * backup, so a failed initialization never leaves the database without one.
     */
    protected function optimizeBackup(Filesystem $disk, string $previousName): int
    {
        $this->warn('Starting backup optimization...');

        if ($this->initBackup($disk) !== Command::SUCCESS) {
            $this->warn('Keeping the previous incremental backup.');

            $this->differentialBackup($previousName);

            return Command::FAILURE;
        }

        $disk->deleteDirectory($this->getBackupName(incremental: true, name: $previousName));

        $this->info('Backups optimized successfully.');

        return Command::SUCCESS;
    }

    protected function initBackup(Filesystem $disk): int
    {
        $name = config('database.connections.singlestore.database').'_'.Carbon::now()->format('Y-m-d_H-i-s');

        if ($this->call('singlestore:backup', $this->backupArguments($name, ['--init' => true])) !== Command::SUCCESS) {
            $this->error('Backup initialization failed.');

            // Remove whatever the failed backup left behind.
            $disk->deleteDirectory($this->getBackupName(incremental: true, name: $name));

            return Command::FAILURE;
        }

        $this->putCurrentBackup($disk, $name);

        return Command::SUCCESS;
    }

    protected function differentialBackup(string $name): int
    {
        $this->warn('Starting differential backup...');

        if ($this->call('singlestore:backup', $this->backupArguments($name, ['--differential' => true])) !== Command::SUCCESS) {
            $this->error('Differential backup failed.');

            return Command::FAILURE;
        }

        $this->info('Differential backup ran successfully.');

        return Command::SUCCESS;
    }

    protected function backupArguments(string $name, array $arguments): array
    {
        $arguments['--name'] = $name;

        if ($this->option('timeout') !== null) {
            $arguments['--timeout'] = $this->option('timeout');
        }

        return $arguments;
    }

    protected function isValidInterval(string $interval): bool
    {
        return isset(static::PERIOD_FORMATS[$interval]) || (ctype_digit($interval) && (int) $interval > 0);
    }

    protected function isOptimizationDue(Carbon $initializedAt, string $interval): bool
    {
        $now = Carbon::now();

        if ($format = static::PERIOD_FORMATS[$interval] ?? null) {
            return $initializedAt->format($format) !== $now->format($format);
        }

        return $initializedAt->copy()->addDays((int) $interval)->lessThanOrEqualTo($now);
    }

    /**
     * Get the name of the current incremental backup and the date of its INIT backup.
     *
     * @return array{0: ?string, 1: ?Carbon}
     */
    protected function getCurrentBackup(Filesystem $disk): array
    {
        $state = json_decode((string) $disk->get($this->getIncrementalStateFileName()), true);

        if (is_array($state) && is_string($state['name'] ?? null) && $this->backupExists($disk, $state['name'])) {
            try {
                $initializedAt = Carbon::parse($state['initialized_at'] ?? null)->setTimezone(Carbon::now()->getTimezone());
            } catch (Throwable) {
                $initializedAt = null;
            }

            return [$state['name'], $initializedAt];
        }

        // Fallback to an incremental backup created by the "singlestore:backup" command.
        $database = config('database.connections.singlestore.database');

        return [$this->backupExists($disk, $database) ? $database : null, null];
    }

    protected function putCurrentBackup(Filesystem $disk, string $name): void
    {
        $disk->put($this->getIncrementalStateFileName(), json_encode([
            'name'           => $name,
            'initialized_at' => Carbon::now()->toIso8601String(),
        ]));
    }

    protected function backupExists(Filesystem $disk, string $name): bool
    {
        return $disk->exists($this->getBackupName(incremental: true, name: $name));
    }
}
