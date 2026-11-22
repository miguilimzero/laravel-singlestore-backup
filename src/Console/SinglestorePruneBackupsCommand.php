<?php

namespace Miguilim\LaravelSinglestoreBackup\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Miguilim\LaravelSinglestoreBackup\Console\Concerns\InteractsWithBackupDisk;

class SinglestorePruneBackupsCommand extends Command
{
    use InteractsWithBackupDisk;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'singlestore:prune-backups {--incremental} {--older-than-days=} {--older-than-date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description;

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

        if ($this->option('incremental') && ($this->option('older-than-days') || $this->option('older-than-date'))) {
            $this->error('You can\'t use --incremental and --older-than-days or --older-than-date options at the same time.');

            return Command::FAILURE;
        }
        
        if ($this->option('older-than-days') && $this->option('older-than-date')) {
            $this->error('You can\'t use --older-than-days and --older-than-date options at the same time.');

            return Command::FAILURE;
        }

        /*
         * Start backup pruning command
         */
        $this->warn('Starting backup pruning... This might take a while.');

        $disk = $this->buildBackupDisk();

        if ($this->option('incremental')) {
            if (! $this->pruneIncrementalBackups($disk)) {
                $this->error('No incremental backup to prune found.');

                return Command::FAILURE;
            }
        } else {
            if ($olderThan = $this->getOlderThan()) {
                if (! $this->pruneDateOrTimeBackups($disk, $olderThan)) {
                    $this->error('No backup with date or time older than '.$olderThan->format('Y-m-d H:i:s').' to prune found.');

                    return Command::FAILURE;
                }
            } else {
                if ($disk->exists($this->getBackupName())) {
                    $disk->deleteDirectory($this->getBackupName());
                } else {
                    $this->error('No backup to prune found.');

                    return Command::FAILURE;
                }
            }
        }

        $this->info('Backups pruned successfully.');

        return Command::SUCCESS;
    }

    protected function getOlderThan(): ?Carbon
    {
        if ($date = $this->option('older-than-date')) {
            return Carbon::parse($date);
        }

        if ($days = $this->option('older-than-days')) {
            return Carbon::today()->subDays($days);
        }

        return null;
    }

    protected function pruneIncrementalBackups(Filesystem $disk): bool
    {
        $found = 0;

        if ($disk->exists($this->getBackupName(incremental: true))) {
            $disk->deleteDirectory($this->getBackupName(incremental: true));

            $found++;
        }

        // Backups created by the "singlestore:incremental-backup" command
        $pattern = '/(^|\/)'.preg_quote(config('database.connections.singlestore.database'), '/').'_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.incr_backup$/';

        foreach ($disk->directories(config('singlestore-backup.path')) as $directory) {
            if (preg_match($pattern, $directory)) {
                $disk->deleteDirectory($directory);

                $found++;
            }
        }

        $disk->delete($this->getIncrementalStateFileName());

        return $found > 0;
    }

    protected function pruneDateOrTimeBackups(Filesystem $disk, Carbon $olderThan): int
    {
        $directories = $disk->directories(config('singlestore-backup.path'));

        $found = 0;
        foreach ($directories as $directory) {
            $isBackupDirectory = preg_match('/'.preg_quote(config('database.connections.singlestore.database').'_', '/').'(.*?)\.backup/', $directory, $matches);

            if (! $isBackupDirectory) {
                continue;
            }

            $directoryDate = (str_contains($matches[1], '_'))
                ? Carbon::createFromFormat('Y-m-d_H-i-s', $matches[1])
                : Carbon::createFromFormat('Y-m-d', $matches[1]);

            if ($directoryDate->isBefore($olderThan)) {
                $disk->deleteDirectory($directory);

                $found++;
            }
        }

        return $found > 0;
    }
}
