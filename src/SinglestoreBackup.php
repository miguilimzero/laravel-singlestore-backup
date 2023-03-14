<?php

namespace Srdante\LaravelSinglestoreBackup;

use Illuminate\Support\Facades\DB;

class SinglestoreBackup
{
    /**
     * Construct method.
     */
    public function __construct(
        protected bool $init = false,
        protected bool $differential = false,
        protected ?int $timeout = null,
        protected ?int $multipartChunkSizeMb = null
    ) {
        if ($init && $differential) {
            throw new \InvalidArgumentException('You can\'t use "$init" and "$differential" attributes at the same time.');
        }
    }

    /**
     * Generate query and execute it.
     */
    public function executeQuery(): array
    {
        // Get parameters
        [$database, $bucket, $config, $credentials] = static::getParameters();

        // Mount statements
        $with = '';
        if ($this->init) {
            $with = 'WITH INIT';
        }
        if ($this->differential) {
            $with = 'WITH DIFFERENTIAL';
        }

        $timeoutStatement = '';
        if ($this->timeout) {
            $timeoutStatement = "TIMEOUT {$this->timeout}";
        }

        // Mount query
        $rawQuery = "BACKUP DATABASE {$database} {$with} TO S3 ? {$timeoutStatement} CONFIG ? CREDENTIALS ?";
        $rawQuery = preg_replace('/\s+/', ' ', $rawQuery);

        return DB::select($rawQuery, [$bucket, $config, $credentials]);
    }

    /**
     * Get query binding parameters.
     */
    protected function getParameters(): array
    {
        $config = [
            'endpoint_url' => (string) config('singlestore-backup.endpoint'),
        ];

        if ($this->multipartChunkSizeMb) {
            $config[]['multipart_chunk_size_mb'] = $this->multipartChunkSizeMb;
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
