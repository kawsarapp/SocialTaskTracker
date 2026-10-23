<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

class CloudflareR2Service
{
    private ?S3Client $client = null;

    /**
     * Creates and returns a configured AWS S3Client pointing to Cloudflare R2 endpoint.
     */
    public function getClient(): S3Client
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = new S3Client([
            'version'                 => 'latest',
            'region'                  => 'auto',
            'endpoint'                => config('r2.endpoint'),
            'use_path_style_endpoint' => true,
            'credentials'             => [
                'key'    => config('r2.key'),
                'secret' => config('r2.secret'),
            ],
        ]);

        return $this->client;
    }

    /**
     * Generates a presigned PUT URL for direct browser-to-R2 single-part upload.
     */
    public function generatePresignedUploadUrl(string $key, string $contentType, int $expiresIn = 3600): string
    {
        $client = $this->getClient();

        $command = $client->getCommand('PutObject', [
            'Bucket'      => config('r2.bucket'),
            'Key'         => $key,
            'ContentType' => $contentType,
        ]);

        $request = $client->createPresignedRequest($command, "+{$expiresIn} seconds");

        return (string) $request->getUri();
    }

    /**
     * Initiates a multipart upload and returns upload_id + key.
     */
    public function generatePresignedMultipartUpload(string $key, string $contentType): array
    {
        $client = $this->getClient();

        $result = $client->createMultipartUpload([
            'Bucket'      => config('r2.bucket'),
            'Key'         => $key,
            'ContentType' => $contentType,
        ]);

        return [
            'upload_id' => $result['UploadId'],
            'key'       => $key,
        ];
    }

    /**
     * Generates a presigned PUT URL for uploading a single part in a multipart upload.
     */
    public function presignPartUpload(string $key, string $uploadId, int $partNumber, int $expiresIn = 3600): string
    {
        $client = $this->getClient();

        $command = $client->getCommand('UploadPart', [
            'Bucket'     => config('r2.bucket'),
            'Key'        => $key,
            'UploadId'   => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        $request = $client->createPresignedRequest($command, "+{$expiresIn} seconds");

        return (string) $request->getUri();
    }

    /**
     * Completes a multipart upload and returns the public URL and key.
     *
     * @param  array  $parts  Array of ['ETag' => '...', 'PartNumber' => N]
     */
    public function completeMultipartUpload(string $key, string $uploadId, array $parts): array
    {
        $client = $this->getClient();

        $client->completeMultipartUpload([
            'Bucket'          => config('r2.bucket'),
            'Key'             => $key,
            'UploadId'        => $uploadId,
            'MultipartUpload' => ['Parts' => $parts],
        ]);

        return [
            'url' => $this->getPublicUrl($key),
            'key' => $key,
        ];
    }

    /**
     * Aborts an in-progress multipart upload.
     */
    public function abortMultipartUpload(string $key, string $uploadId): void
    {
        try {
            $this->getClient()->abortMultipartUpload([
                'Bucket'   => config('r2.bucket'),
                'Key'      => $key,
                'UploadId' => $uploadId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('R2 abortMultipartUpload failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Deletes an object from the R2 bucket.
     */
    public function deleteObject(string $key): void
    {
        try {
            $this->getClient()->deleteObject([
                'Bucket' => config('r2.bucket'),
                'Key'    => $key,
            ]);
        } catch (\Throwable $e) {
            Log::warning('R2 deleteObject failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Uploads a file (raw string body) to R2 directly.
     */
    public function putObject(string $key, string $body, string $contentType = 'application/octet-stream'): string
    {
        $this->getClient()->putObject([
            'Bucket'      => config('r2.bucket'),
            'Key'         => $key,
            'Body'        => $body,
            'ContentType' => $contentType,
        ]);

        return $this->getPublicUrl($key);
    }

    /**
     * Returns the public CDN URL for a given R2 object key.
     */
    public function getPublicUrl(string $key): string
    {
        return rtrim((string) config('r2.public_url', ''), '/') . '/' . ltrim($key, '/');
    }
}
