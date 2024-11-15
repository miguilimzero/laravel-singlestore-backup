<?php

namespace Miguilim\LaravelSinglestoreBackup;

use Illuminate\Support\Facades\DB;

class SinglestoreBackup
{
    public function __construct(
        protected string $driver,
        protected string $database,
        protected ?string $path = null,
        protected ?string $endpoint = null,
        protected ?int $timeout = null,
        protected ?string $publicKey = null,
        protected ?string $secretKey = null,
        protected ?string $bucket = null,
        protected bool $init = false,
        protected bool $differential = false,
        protected ?string $region = null,
        protected ?int $multipartChunkSizeMb = null,
        protected bool $s3ForcePathStyle = false,
        protected bool $compatibilityMode = false,
        protected bool $withDate = false,
        protected bool $withTime = false,
    ) {
        if ($init && $differential) {
            throw new \InvalidArgumentException('You can\'t use "$init" and "$differential" attributes at the same time.');
        }

        if ($this->driver !== 's3' && $this->multipartChunkSizeMb) {
            throw new \InvalidArgumentException('You can\'t use "$multipartChunkSizeMb" attribute with "'.$this->driver.'" driver.');
        }

        if ($this->driver !== 's3' && $this->s3ForcePathStyle) {
            throw new \InvalidArgumentException('You can\'t use "$s3ForcePathStyle" attribute with "'.$this->driver.'" driver.');
        }

        if ($this->driver !== 's3' && $this->compatibilityMode) {
            throw new \InvalidArgumentException('You can\'t use "$compatibilityMode" attribute with "'.$this->driver.'" driver.');
        }

        if ($this->driver === 'local' && ($this->init || $this->differential)) {
            throw new \InvalidArgumentException('You can\'t use "$init" or "$differential" attributes with "local" driver.');
        }

        if (($this->withDate || $this->withTime) && ($this->init || $this->differential)) {
            throw new \InvalidArgumentException('You can\'t use "$init" or "$differential" attributes with "$withDate" or "$withTime" attributes.');
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

        // Mount query
        $rawQuery = "BACKUP DATABASE {$this->database} TO ? {$timeoutStatement}";
        $rawQuery = preg_replace('/\s+/', ' ', $rawQuery);

        return DB::select($rawQuery, [$this->mountFinalPath()]);
    }

    protected function executeExternalStorageQuery(): array
    {
        [$to, $config, $credentials] = static::getExternalStorageParameters();

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
        $rawQuery = "BACKUP DATABASE {$this->database} {$with} TO {$to} ? {$timeoutStatement} CONFIG ? CREDENTIALS ?";
        $rawQuery = preg_replace('/\s+/', ' ', $rawQuery);

        return DB::select($rawQuery, [$this->bucket.$this->mountFinalPath(), $config, $credentials]);
    }

    protected function getExternalStorageParameters(): array
    {
        $config = [];

        $credentials = match ($this->driver) {
            's3' => [
                'aws_access_key_id'     => $this->publicKey,
                'aws_secret_access_key' => $this->secretKey,
            ],
            'gcs' => [
                'access_id'  => $this->publicKey,
                'secret_key' => $this->secretKey,
            ],
            'azure' => [
                'account_name' => $this->publicKey,
                'account_key'  => $this->secretKey,
            ],
        };

        if ($this->multipartChunkSizeMb) {
            $config['multipart_chunk_size_mb'] = $this->multipartChunkSizeMb;
        }

        if ($this->s3ForcePathStyle) {
            $config['s3_force_path_style'] = $this->s3ForcePathStyle;
        }
        if ($this->compatibilityMode) {
            $config['compatibility_mode'] = $this->compatibilityMode;
        }

        if ($this->driver === 's3' && $this->region) {
            $config['region'] = $this->region;
        } else {
            $config['endpoint_url'] = $this->endpoint;
        }

        return [
            strtoupper($this->driver),

            json_encode($config),

            json_encode($credentials),
        ];
    }

    protected function mountFinalPath(): string
    {
        $name = $this->database;

        if ($this->withDate) {
            $name .= '_'.date('Y-m-d');
        }
        if ($this->withTime) {
            $name .= '_'.date('H-i-s');
        }

        $extension = ($this->init || $this->differential)
            ? 'incr_backup'
            : 'backup';

        return ($this->path ? "/{$this->path}" : '').'/'.$name.'.'.$extension;
    }
}
