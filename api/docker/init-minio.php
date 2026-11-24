<?php

use Aws\S3\Exception\S3Exception;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** @var AwsS3V3Adapter $disk */
$disk = Storage::disk('s3');
$client = $disk->getClient();
$bucket = ['Bucket' => config('filesystems.disks.s3.bucket')];

try {
    $client->headBucket($bucket);
} catch (S3Exception $exception) {
    if ($exception->getStatusCode() !== 404) {
        throw $exception;
    }

    $client->createBucket($bucket);
}
