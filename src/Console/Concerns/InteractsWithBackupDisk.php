<?php

namespace Miguilim\LaravelSinglestoreBackup\Console\Concerns;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

trait InteractsWithBackupDisk
{
    protected function buildBackupDisk(): Filesystem
    {
        return Storage::build([
            'driver'                  => config('singlestore-backup.driver'),
            'key'                     => config('singlestore-backup.public_key'),
            'secret'                  => config('singlestore-backup.secret_key'),
            'region'                  => config('singlestore-backup.region') ?? 'us-east-1',
            'bucket'                  => config('singlestore-backup.bucket'),
            'endpoint'                => config('singlestore-backup.endpoint'),
            'use_path_style_endpoint' => config('singlestore-backup.force_path_style'),
        ]);
    }

    protected function getBackupName(bool $incremental = false, ?string $name = null): string
    {
        $path     = config('singlestore-backup.path');
        $name ??= config('database.connections.singlestore.database');

        return ($path ? "{$path}/" : '').$name.($incremental ? '.incr_backup' : '.backup');
    }

    /**
     * The file that tracks the current backup of the "singlestore:incremental-backup" command.
     */
    protected function getIncrementalStateFileName(): string
    {
        return $this->getBackupName(incremental: true).'.state';
    }
}
