<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup S3 Endpoint
    |--------------------------------------------------------------------------
    |
    | The endpoint of the S3 compatible storage.
    |
    */
    'endpoint' => env('SINGLESTORE_BACKUP_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup S3 Bucket
    |--------------------------------------------------------------------------
    |
    | The bucket where the backups will be stored.
    |
    */
    'bucket' => env('SINGLESTORE_BACKUP_BUCKET'),

    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup S3 Access Key
    |--------------------------------------------------------------------------
    |
    | The access key of the S3 compatible storage.
    |
    */
    'access_key' => env('SINGLESTORE_BACKUP_ACCESS_KEY'),

    /*
    |--------------------------------------------------------------------------
    | SingleStore Backup S3 Secret Access Key
    |--------------------------------------------------------------------------
    |
    | The secret key of the S3 compatible storage.
    |
    */
    'secret_key' => env('SINGLESTORE_BACKUP_SECRET_KEY'),
];
