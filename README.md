# Laravel SingleStore Backup

Laravel SingleStore Backup is a package that makes it easy to make backups of your SingleStore database to your favorite storage. Behind the scenes, this package uses the `BACKUP DATABASE` command, a [native command](https://docs.singlestore.com/db/v8.1/reference/sql-reference/operational-commands/backup-database/) from SingleStore DB engine.

## Contents

- [Installation](#installation)
- [Supported Drivers](#supported-drivers)
- [Configuring](#configuring)
- [Basic Usage](#basic-usage)
- [Setting Timeout Parameter](#setting-timeout-parameter)
- [Init Backup](#init-backup-non-local-only)
- [Differential Backup](#differential-backup-non-local-only)
- [Advanced Usage](#advanced-usage)
- [Publishing Config File](#publishing-config-file)
- [License](#license)

## Installation

You can install the package via composer:

```sh
composer require miguilim/laravel-singlestore-backup
```

## Supported Drivers

This package supports the following backup drivers:

- Local - `local`
- S3 / S3 compatible - `s3`
- Google Cloud Storage - `gcs`
- Azure Blobs - `azure`

## Configuring

You must add the following lines to your .env file in order to configure your S3 credentials:

```env
SINGLESTORE_BACKUP_DRIVER=

# Local storage
SINGLESTORE_BACKUP_PATH= 

# S3 storage (optionals)
SINGLESTORE_BACKUP_REGION=
SINGLESTORE_BACKUP_MULTIPART_CHUNK_SIZE=
SINGLESTORE_BACKUP_FORCE_PATH_STYLE=

# External storages
SINGLESTORE_BACKUP_ENDPOINT=
SINGLESTORE_BACKUP_BUCKET=
SINGLESTORE_BACKUP_PUBLIC_KEY=
SINGLESTORE_BACKUP_SECRET_KEY=
```

## Basic Usage

Below there is a simple example of how you use the backup command. By default, the command will do a full backup.

```sh
php artisan singlestore:backup
``` 

## Setting Timeout Parameter

You can set the timeout parameter, a value specified in milliseconds, to determines the length of time to wait for the `BACKUP DATABASE` command to commit across the cluster. If not specified, the `default_distributed_ddl_timeout` global variable value is used.

```sh
php artisan singlestore:backup --timeout=30000
```

## Init Backup

If you're making an incremental backup and want to create the `INIT` backup, you can do that by using the `--init` parameter.

```sh
php artisan singlestore:backup --init
``` 

## Differential Backup

If you're making an incremental backup and want to do a `DIFFERENTIAL` backup, you can do that by using the `--differential` parameter.

```sh
php artisan singlestore:backup --differential
``` 

## Advanced Usage

Sometimes the simple backup command with configs may not be flexible as you want. Instead, you can use the `SinglestoreBackup` class:

```php
use Miguilim\LaravelSinglestoreBackup\SinglestoreBackup;

$backupInstance = new SinglestoreBackup(
    driver: 'local',
    database: 'mydatabase',
    path: '/my-backup/path'
);

$result = $backupInstance->executeQuery();
```

Available arguments:

- `driver`
- `database`
- `path`
- `endpoint`
- `timeout`
- `publicKey`
- `secretKey`
- `bucket`
- `init`
- `differential`
- `region`
- `multipartChunkSizeMb`
- `s3ForcePathStyle`

## Publishing Config File

You can publish the package configuration file to your project with the following command:

```sh
php artisan vendor:publish --tag=singlestore-backup-config
```

## License

Laravel SingleStore Backup is open-sourced software licensed under the [MIT license](LICENSE).