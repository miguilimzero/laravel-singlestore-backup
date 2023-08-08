<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup Driver
    |--------------------------------------------------------------------------
    |
    | The selected backup storage driver.
    |
    | Available drivers: "s3", "gcs", "azure", "local".
    |
    */
    'driver' => env('SINGLESTORE_BACKUP_DRIVER'),

    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup Path
    |--------------------------------------------------------------------------
    |
    | The file path where the backups will be stored (Only used by "local" driver).
    |
    */
    'path' => env('SINGLESTORE_BACKUP_PATH'),

    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup Endpoint
    |--------------------------------------------------------------------------
    |
    | The endpoint of the compatible storage.
    |
    */
    'endpoint' => env('SINGLESTORE_BACKUP_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup Bucket
    |--------------------------------------------------------------------------
    |
    | The bucket where the backups will be stored.
    |
    */
    'bucket' => env('SINGLESTORE_BACKUP_BUCKET'),

    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup Public Key
    |--------------------------------------------------------------------------
    |
    | The public key of the selected driver storage.
    |
    */
    'public_key' => env('SINGLESTORE_BACKUP_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key of the selected driver storage.
    |
    */
    'secret_key' => env('SINGLESTORE_BACKUP_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup S3 Parameters
    |--------------------------------------------------------------------------
    |
    | AWS S3 extra parameters (Only used by "s3" driver).
    |
    */
    'region'  => env('SINGLESTORE_BACKUP_REGION'),

    'multipart_chunk_size' => (int) env('SINGLESTORE_BACKUP_MULTIPART_CHUNK_SIZE'),

    'force_path_style' => (bool) env('SINGLESTORE_BACKUP_FORCE_PATH_STYLE'),
];
