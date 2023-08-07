<?php

namespace Miguilim\LaravelSinglestoreBackup;

use Illuminate\Support\Facades\DB;

class SinglestoreBackup
{
    protected string $driver;

    public function __construct(
        protected ?int $timeout = null,
        protected bool $init = false,
        protected bool $differential = false,
        protected ?int $multipartChunkSizeMb = null
    ) {
        if ($init && $differential) {
            throw new \InvalidArgumentException('You can\'t use "$init" and "$differential" attributes at the same time.');
        }

        $this->driver = config('singlestore-backup.driver');

        if ($this->driver !== 's3' && $this->multipartChunkSizeMb) {
            throw new \InvalidArgumentException('You can\'t use "$multipartChunkSizeMb" attribute with "' . $this->driver . '" driver.');
        }

        if ($this->driver === 'local' && ($this->init || $this->differential)) {
            throw new \InvalidArgumentException('You can\'t use "$init" or "$differential" attributes with "local" driver.');
        }
    }

    public function executeQuery(): array
    {
        if ($this->driver === 'local') {
            return $this->executeLocalStorageQuery();
        }
    
        return $this->executeExternalStorageQuery();
    }

    protected function executeLocalStorageQuery(): array
    {
        $timeoutStatement = '';
        if ($this->timeout) {
            $timeoutStatement = "TIMEOUT {$this->timeout}";
        }

        $database = (string) config('database.connections.singlestore.database');
        $path     = (string) config('singlestore-backup.path');

        // Mount query
        $rawQuery = "BACKUP DATABASE {$database} TO ? {$timeoutStatement}";
        $rawQuery = preg_replace('/\s+/', ' ', $rawQuery);

        return DB::select($rawQuery, [$path]);
    }

    protected function executeExternalStorageQuery(): array
    {
        [$to, $bucket, $config, $credentials] = static::getExternalStorageParameters();

        $database = (string) config('database.connections.singlestore.database');

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
        $rawQuery = "BACKUP DATABASE {$database} {$with} TO {$to} ? {$timeoutStatement} CONFIG ? CREDENTIALS ?";
        $rawQuery = preg_replace('/\s+/', ' ', $rawQuery);

        return DB::select($rawQuery, [$bucket, $config, $credentials]);
    }

    protected function getExternalStorageParameters(): array
    {
        $config = [
            'endpoint_url' => (string) config('singlestore-backup.endpoint'),
        ];

        $publicKey = (string) config('singlestore-backup.public_key');
        $secretKey = (string) config('singlestore-backup.secret_key');
    
        $credentials = match($this->driver) {
            's3' => [
                'aws_access_key_id'     => $publicKey,
                'aws_secret_access_key' => $secretKey,
            ],
            'gcs' => [
                'access_id'  => $publicKey,
                'secret_key' => $secretKey,
            ],
            'azure' => [
                'account_name' => $publicKey,
                'account_key'  => $secretKey,
            ],
        };

        if ($this->multipartChunkSizeMb) {
            $config[]['multipart_chunk_size_mb'] = $this->multipartChunkSizeMb;
        }

        return [
            strtoupper($this->driver),

            (string) config('singlestore-backup.bucket'),

            json_encode($config),

            json_encode($credentials),
        ];
    }
}
