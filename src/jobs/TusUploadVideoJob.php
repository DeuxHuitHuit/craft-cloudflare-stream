<?php

namespace deuxhuithuit\cfstream\jobs;

use craft\elements\Asset;
use Craft\helpers\App;
use craft\queue\BaseJob;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use GuzzleHttp;
use yii\queue\RetryableJobInterface;

// TODO: Make cancellable, to cancel the upload if the asset is deleted
class TusUploadVideoJob extends BaseJob implements RetryableJobInterface
{
    public const DEFAULT_CHUNK_SIZE = 1024 * 1024 * 50; // 50MB
    public const MINIMUM_CHUNK_SIZE = 1024 * 1024 * 5; // 5MB
    public const MAXIMUM_CHUNK_SIZE = 1024 * 1024 * 200; // 200MB

    public $elementId;
    public $fieldHandle;
    public $videoUid;
    public $videoLocation;
    public $videoPath;
    public $videoName;
    public $offset = 0;
    public $chunkSize = self::DEFAULT_CHUNK_SIZE;

    public function __construct($config = [])
    {
        parent::__construct($config);

        // Check for env chunk size
        $envChunkSize = (int) App::env('CF_STREAM_TUS_CHUNK_SIZE');
        if ($envChunkSize) {
            $this->chunkSize = $envChunkSize;
        }

        // Validate the chunk size
        $this->chunkSize = max(self::MINIMUM_CHUNK_SIZE, min(self::MAXIMUM_CHUNK_SIZE, $this->chunkSize));
    }

    public function getTtr()
    {
        // 1Mb per second
        return (int) max(5, $this->chunkSize / 1024 / 1024);
    }

    public function canRetry($attempt, $error)
    {
        return $attempt < 1000;
    }

    public function execute($queue): void
    {
        $this->setProgress($queue, 0, 'Validating job data');

        // Get the entry or element where the field is located
        $element = \Craft::$app->getElements()->getElementById($this->elementId);
        if (!$element) {
            // Ignore deleted entries
            $this->setProgress($queue, 1, 'Element not found');

            return;
        }
        if (!$element instanceof Asset) {
            throw new \Exception('Element not an asset.');
        }

        // Get the CloudflareVideoStreamField by its handle
        $field = \Craft::$app->getFields()->getFieldByHandle($this->fieldHandle);
        if (!$field) {
            // Ignore deleted fields
            $this->setProgress($queue, 1, 'Field not found');

            return;
        }

        $this->setProgress($queue, 0.1, 'Validating Cloudflare Video Stream field');

        // Check if the field is a CloudflareVideoStreamField
        if (!$field instanceof CloudflareVideoStreamField) {
            $this->setProgress($queue, 0.1, 'ERROR: Field is not a Cloudflare Video Stream field');

            throw new \Error('Field is not a Cloudflare Video Stream field');
        }

        $client = new GuzzleHttp\Client();

        // Sync the current offset
        $this->setProgress($queue, 0.3, 'Syncing current offset');
        $headRes = $client->request('HEAD', $this->videoLocation, [
            'headers' => [
                'Tus-Resumable' => '1.0.0',
            ],
            'http_errors' => false,
        ]);
        $headHeaders = $headRes->getHeaders();
        if ($headRes->getStatusCode() === 200) {
            if (!isset($headHeaders['upload-offset'][0])) {
                throw new \Exception('Missing upload-offset header');
            }
            $this->offset = (int) $headHeaders['upload-offset'][0];
        }

        // Upload a chunk of the video
        $this->setProgress($queue, 0.3, 'Uploading video to Cloudflare Stream via TUS');
        $file = \fopen($this->videoPath, 'r');
        if (!$file) {
            throw new \Exception('Failed to open file for reading');
        }

        $fileSize = \filesize($this->videoPath);
        if (!$fileSize) {
            throw new \Exception('Failed to get file size');
        }
        if ($fileSize < $this->offset) {
            throw new \Exception('File size is smaller than the current offset');
        }
        if ($fileSize === $this->offset) {
            $this->setProgress($queue, 1, 'Upload complete, starting polling job');
            \Craft::$app->getQueue()->push(new PollVideoJob([
                'elementId' => $this->elementId,
                'fieldHandle' => $this->fieldHandle,
                'videoUid' => $this->videoUid,
            ]));

            return;
        }

        if ($this->offset > 0) {
            \fseek($file, $this->offset);
        }
        $uploadRes = $client->request('PATCH', $this->videoLocation, [
            'headers' => [
                'Tus-Resumable' => '1.0.0',
                'Upload-Offset' => $this->offset,
                'Content-Type' => 'application/offset+octet-stream',
                'Expect' => '',
            ],
            'body' => \fread($file, $this->chunkSize),
            'http_errors' => false,
        ]);

        if (\is_resource($file)) {
            \fclose($file);
        }

        if ($uploadRes->getStatusCode() === 204) {
            $this->offset = min($fileSize, $this->offset + $this->chunkSize);
            \Craft::$app->getQueue()->push($this);
            $this->setProgress($queue, 1, 'Chunk upload completed, pushed next chunk');
        } else {
            throw new \Exception("Chunk at offset {$this->offset} failed, retrying");
        }
    }

    protected function defaultDescription(): ?string
    {
        return "TUS upload video {$this->videoName}, offset {$this->offset}.";
    }
}
