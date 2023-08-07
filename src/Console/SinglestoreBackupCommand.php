<?php

namespace Miguilim\LaravelSinglestoreBackup\Console;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Miguilim\LaravelSinglestoreBackup\SinglestoreBackup;

class SinglestoreBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'singlestore:backup {--init} {--differential} {--timeout=} {--multipart_chunk_size_mb=}';

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
        if ($this->option('init') && $this->option('differential')) {
            throw new InvalidArgumentException('You can\'t use --init and --differential options at the same time.');
        }

        /*
         * Start backup command
         */
        $this->warn('Starting backup... This might take a while.');

        $singlestoreBackup = new SinglestoreBackup(
            $this->option('init'),
            $this->option('differential'),
            $this->option('timeout'),
            $this->option('multipart_chunk_size_mb'),
        );

        try {
            $result = $singlestoreBackup->executeQuery();
        } catch (QueryException $e) {
            $this->error('Backup failed. Please check your SingleStore backup credentials.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Backup created successfully.');

        return Command::SUCCESS;
    }
}
