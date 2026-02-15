<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use App\Config\Config;

class MinioStorageService
{
    private static function getClient(): S3Client
    {
        return new S3Client([
            'version'     => 'latest',
            'region'      => 'us-east-1', // any region
            'endpoint'    => Config::MINIO_ENDPOINT(),
            'use_path_style_endpoint' => true, // Required for MinIO
            'credentials' => [
                'key'    => Config::MINIO_ACCESS_KEY(),
                'secret' => Config::MINIO_SECRET_KEY(),
            ],
        ]);
    }

    public static function putObject(string $bucket, string $key, string $filePath, string $contentType = 'application/octet-stream'): ?string
    {
        try {
            $client = self::getClient();

            $client->putObject([
                'Bucket'      => $bucket,
                'Key'         => $key,
                'SourceFile'  => $filePath,
                'ContentType' => $contentType
            ]);

            return "/{$bucket}/{$key}";
        } catch (AwsException $e) {
            error_log("MinIO PutObject error: " . $e->getMessage());
            return null;
        }
    }

    public static function getObject(string $bucket, string $key): ?string
    {
        try {
            $client = self::getClient();

            $result = $client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            return (string) $result['Body'];
        } catch (AwsException $e) {
            error_log("MinIO GetObject error: " . $e->getMessage());
            return null;
        }
    }

    public static function getObjectUrl(string $key): string
    {
        return trim(Config::MINIO_ENDPOINT(), '/') . '/' . trim($key, '/');
    }

    public static function ensureBucketExists(string $bucket): void
    {
        $client = self::getClient();

        try {
            if (!$client->doesBucketExist($bucket)) {
                $client->createBucket(['Bucket' => $bucket]);
                error_log("Bucket '$bucket' created.");
            } else {
                error_log("Bucket '$bucket' already exists.");
            }
        } catch (\Aws\Exception\AwsException $e) {
            error_log("Error checking/creating bucket '$bucket': " . $e->getMessage());
            throw $e;
        }
    }

    public static function uploadXml(string $bucket, string $invoiceId, string $base64Xml): ?string
    {
        $tmpDir = sys_get_temp_dir();
        $fileName = $invoiceId . '.xml';
        $filePath = $tmpDir . DIRECTORY_SEPARATOR . $fileName;

        try {
            $xmlContent = base64_decode($base64Xml, true);
            if ($xmlContent === false) {
                error_log("Invalid base64");
                return null;
            }

            if (file_put_contents($filePath, $xmlContent) === false) {
                error_log("Failed to write file to: $filePath");
                return null;
            }

            self::ensureBucketExists($bucket);

            $url = self::putObject(
                bucket: $bucket,
                key: "invoices/{$invoiceId}.xml",
                filePath: $filePath,
                contentType: 'application/xml'
            );

            @unlink($filePath);

            return $url;
        } catch (\Throwable $e) {
            error_log("uploadXml error: " . $e->getMessage());
            return null;
        }
    }
}
