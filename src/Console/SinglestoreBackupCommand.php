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
    protected $signature = 'singlestore:backup {--timeout=} {--init} {--differential} {--with-date} {--with-time}';

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

        if (($this->option('with-date') || $this->option('with-time')) && ($this->option('init') || $this->option('differential'))) {
            throw new InvalidArgumentException('You can\'t use --init or --differential options with --with-date or --with-time options.');
        }

        /*
         * Start backup command
         */
        $this->warn('Starting backup... This might take a while.');

        $singlestoreBackup = new SinglestoreBackup(
            driver: config('singlestore-backup.driver'),
            database: config('database.connections.singlestore.database'),
            path: config('singlestore-backup.path'),
            endpoint: config('singlestore-backup.endpoint'),
            timeout: $this->option('timeout'),
            publicKey: config('singlestore-backup.public_key'),
            secretKey: config('singlestore-backup.secret_key'),
            bucket: config('singlestore-backup.bucket'),
            init: $this->option('init'),
            differential: $this->option('differential'),
            region: config('singlestore-backup.region'),
            multipartChunkSizeMb: config('singlestore-backup.multipart_chunk_size'),
            s3ForcePathStyle: config('singlestore-backup.force_path_style'),
            compatibilityMode: config('singlestore-backup.compatibility_mode'),
            withDate: $this->option('with-date'),
            withTime: $this->option('with-time'),
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
