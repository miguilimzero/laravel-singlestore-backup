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
- [Setting multipart_chunk_size_mb Parameter](#setting-multipart_chunk_size_mb-parameter-s3-only)
- [Setting s3_force_path_style Parameter](#setting-s3_force_path_style-parameter-s3-compatible-only)
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

# S3 storage
SINGLESTORE_BACKUP_REGION=

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

## Init Backup (Non-local Only)

If you're making an incremental backup and want to create the `INIT` backup, you can do that by using the `--init` parameter.

```sh
php artisan singlestore:backup --init
``` 

## Differential Backup (Non-local Only)

If you're making an incremental backup and want to do a `DIFFERENTIAL` backup, you can do that by using the `--differential` parameter.

```sh
php artisan singlestore:backup --differential
``` 

## Setting multipart_chunk_size_mb Parameter (S3 Only)

The `multipart_chunk_size_mb` must be in the range of [5..500]. By default, the chunk size is 5 MB. A larger chunk size allows users to upload large files without going over Amazon’s limitation on maximum number of parts per upload. Although, a larger chunk size increases the chance of a network error during the upload to S3. If a chunk fails to upload, SingleStoreDB retries uploading it until the limit set on the number of retries by AWS is reached. Each partition will use "multipart_chunk_size_mb" MB(s) of additional memory.

```sh
php artisan singlestore:backup --multipart_chunk_size_mb=10
```

## Setting s3_force_path_style Parameter (S3 Compatible Only)

`s3_force_path_style` is an optional boolean JSON config option that defaults to true. It specifies whether to use path style (the default: region.amazonaws.com/bucket) or virtual address style (bucket.region.amazonaws.com) syntax when specifying the location of the bucket.

```sh
php artisan singlestore:backup --s3_force_path_style
```

## License

Laravel SingleStore Backup is open-sourced software licensed under the [MIT license](LICENSE).