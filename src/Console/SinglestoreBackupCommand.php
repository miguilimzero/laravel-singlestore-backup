<?php

namespace Srdante\LaravelSinglestoreBackup\Console;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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

        [$database, $bucket, $config, $credentials] = $this->getParameters();

        $with = '';
        if ($this->option('init')) {
            $with = 'WITH INIT';
        }
        if ($this->option('differential')) {
            $with = 'WITH DIFFERENTIAL';
        }

        $timeout = '';
        if ($this->option('timeout')) {
            $timeout = "TIMEOUT {$this->option('timeout')}";
        }

        /*
         * Do backup query
         */
        $rawQuery = "BACKUP DATABASE {$database} {$with} TO S3 ? {$timeout} CONFIG ? CREDENTIALS ?";
        $rawQuery = preg_replace('/\s+/', ' ', $rawQuery);

        try {
            $result = DB::select($rawQuery, [$bucket, $config, $credentials]);
        } catch (QueryException $e) {
            $this->error('Backup failed. Please check your SingleStore backup credentials.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Backup created successfully.');

        return Command::SUCCESS;
    }

    /**
     * Get query binding parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        $config = [
            'endpoint_url' => (string) config('singlestore-backup.endpoint'),
        ];

        if ($this->option('multipart_chunk_size_mb')) {
            $config[]['multipart_chunk_size_mb'] = $this->option('multipart_chunk_size_mb');
        }

        return [
            (string) config('database.connections.singlestore.database'),

            (string) config('singlestore-backup.bucket'),

            json_encode($config),

            json_encode([
                'aws_access_key_id'     => (string) config('singlestore-backup.access_key'),
                'aws_secret_access_key' => (string) config('singlestore-backup.secret_key'),
            ]),
        ];
    }
}
