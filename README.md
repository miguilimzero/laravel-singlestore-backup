# Laravel SingleStore Backup

Laravel SingleStore Backup is a package that makes it easy to make backups of your SingleStore database to your favorite storage. Behind the scenes, this package uses the `BACKUP DATABASE` command, a [native command](https://docs.singlestore.com/db/v8.1/reference/sql-reference/operational-commands/backup-database/) from SingleStore DB engine.

## Contents

- [Installation](#installation)
- [Supported Drivers](#supported-drivers)
- [Configuring](#configuring)
- [Basic Usage](#basic-usage)
  - [Setting Timeout Parameter](#setting-timeout-parameter)
  - [Setting With Date Parameter](#setting-with-date-parameter)
  - [Setting With Time Parameter](#setting-with-time-parameter)
  - [Init Backup](#init-backup-non-local-only)
  - [Differential Backup](#differential-backup-non-local-only)
- [Prune Backups](#prune-backups)
  - [Prune Incremental Backups](#prune-incremental-backups)
  - [Prune Backups Older Than Days](#prune-backups-older-than-days)
  - [Prune Backups Older Than Date](#prune-backups-older-than-date)
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

# Local / External storage
SINGLESTORE_BACKUP_PATH= 

# External storage (S3 / GCS / Azure)
SINGLESTORE_BACKUP_ENDPOINT=
SINGLESTORE_BACKUP_BUCKET=
SINGLESTORE_BACKUP_PUBLIC_KEY=
SINGLESTORE_BACKUP_SECRET_KEY=

# S3 storage (optionals)
SINGLESTORE_BACKUP_REGION=
SINGLESTORE_BACKUP_MULTIPART_CHUNK_SIZE=
SINGLESTORE_BACKUP_FORCE_PATH_STYLE=
SINGLESTORE_BACKUP_COMPATIBILITY_MODE=
```

## Basic Usage

Below there is a simple example of how you use the backup command. By default, the command will do a full backup.

```sh
php artisan singlestore:backup
``` 

### Setting Timeout Parameter

You can set the timeout parameter, a value specified in milliseconds, to determines the length of time to wait for the `BACKUP DATABASE` command to commit across the cluster. If not specified, the `default_distributed_ddl_timeout` global variable value is used.

```sh
php artisan singlestore:backup --timeout=30000
```

### Setting With Date Parameter

If you want to add the date to the backup name, you can do that by using the `--with-date` parameter.

```sh
php artisan singlestore:backup --with-date
``` 

### Setting With Time Parameter

If you want to add the time to the backup name, you can do that by using the `--with-time` parameter.

```sh
php artisan singlestore:backup --with-time
```

> [!IMPORTANT]
> The `--with-date` and `--with-time` parameters cannot be used in an incremental backup.

### Init Backup

If you're making an incremental backup and want to create the `INIT` backup, you can do that by using the `--init` parameter.

```sh
php artisan singlestore:backup --init
``` 

### Differential Backup

If you're making an incremental backup and want to do a `DIFFERENTIAL` backup, you can do that by using the `--differential` parameter.

```sh
php artisan singlestore:backup --differential
``` 

## Prune Backups

You can prune backups by using the `singlestore:prune-backups` command. This command will prune the `{$database}.backup` directory by default.

```sh
php artisan singlestore:prune-backups
```

> [!IMPORTANT]
> This command can only be executed with the `s3` driver.

### Prune Incremental Backups

If you want to prune incremental backups, you can do that by using the `--incremental` parameter. This will prune the `{$database}.incr_backup` directory.

```sh
php artisan singlestore:prune-backups --incremental
```

### Prune Backups Older Than Days

If you want to prune backups older than a certain number of days, you can do that by using the `--older-than-days` parameter. This will prune the `{$database}_(.*?).backup` (respecting the date) directory.

```sh
php artisan singlestore:prune-backups --older-than-days=30
```

### Prune Backups Older Than Date

If you want to prune backups older than a certain date, you can do that by using the `--older-than-date` parameter. This will prune the `{$database}_(.*?).backup` (respecting the date) directory.

```sh
php artisan singlestore:prune-backups --older-than-date=2024-01-01
```

> [!IMPORTANT]
> Be careful when using the `--older-than-date` or `--older-than-days` parameters. They will prune all directories matched with the `{$database}_(.*?).backup` pattern + respecting the date specified. It may delete other unrelated directories if they match the pattern.

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
- `compatibilityMode`
- `withDate`
- `withTime`

## Publishing Config File

You can publish the package configuration file to your project with the following command:

```sh
php artisan vendor:publish --tag=singlestore-backup-config
```

## License

Laravel SingleStore Backup is open-sourced software licensed under the [MIT license](LICENSE).