<?php

namespace deuxhuithuit\cfstream\jobs;

use craft\elements\Asset;
use craft\queue\BaseJob;
use deuxhuithuit\cfstream\client\CloudflareVideoStreamClient;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\models\Settings;
use deuxhuithuit\cfstream\Plugin;
use yii\queue\RetryableJobInterface;

// TODO: Make cancellable, to cancel the upload if the asset is deleted
class UploadVideoJob extends BaseJob implements RetryableJobInterface
{
    public $fieldHandle;
    public $elementId;
    public $videoUrl;
    public $videoName;
    public $videoPath;
    public $videoTitle;

    public function getTtr()
    {
        return 2 * 60; // 2 minutes
    }

    public function canRetry($attempt, $error)
    {
        return $attempt < 5;
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

        $this->setProgress($queue, 0.2, 'Uploading video to Cloudflare Stream');

        /** @var Settings */
        $settings = Plugin::getInstance()->getSettings();
        $client = new CloudflareVideoStreamClient($settings);
        $result = null;
        $jobType = 'poll';
        if ($settings->isUsingFormUpload()) {
            if ($element->size > TusUploadVideoJob::DEFAULT_CHUNK_SIZE) {
                \Craft::info('Uploading video by TUS', __METHOD__);
                $jobType = 'tus';
                $result = $client->uploadVideoByTus($this->videoPath, $this->videoName);
            } else {
                \Craft::info('Uploading video by path', __METHOD__);
                $result = $client->uploadVideoByPath($this->videoPath, $this->videoName);
            }
        } else {
            \Craft::info('Uploading video by url', __METHOD__);
            $result = $client->uploadVideoByUrl($this->videoUrl, $this->videoName, $this->videoTitle);
        }

        $this->setProgress($queue, 0.3, 'Uploading request returned');

        if (!$result) {
            $this->setProgress($queue, 0.3, 'ERROR: Upload request failed');
            \Craft::error('Upload request failed.', __METHOD__);

            throw new \Error('Upload request failed');
        }
        if (!empty($result['error'])) {
            $this->setProgress($queue, 0.3, 'ERROR: ' . $result['error'] . ': ' . $result['message']);
            \Craft::error('Upload request failed.' . $result['error'] . ' ' . $result['message'], __METHOD__);

            throw new \Error($result['error'] . ' ' . $result['message']);
        }

        $this->setProgress($queue, 0.4, 'Saving craft element');
        $element->setFieldValue($this->fieldHandle, $result);
        // element, runValidation, propagate, updateIndex
        if (!\Craft::$app->getElements()->saveElement($element, true, true, false)) {
            $this->setProgress($queue, 1, 'ERROR: Could not save element');

            \Craft::error('Could not save element');

            throw new \Error('Could not save element');
        }
        $this->setProgress($queue, 0.5, 'Craft element saved');

        // Push next job
        if ($jobType == 'poll') {
            $this->setProgress($queue, 0.6, 'Pushing polling job');
            $pollingJob = new PollVideoJob([
                'elementId' => $this->elementId,
                'fieldHandle' => $this->fieldHandle,
                'videoUid' => $result['uid'],
            ]);
            \Craft::$app->getQueue()->push($pollingJob);
            $this->setProgress($queue, 0.7, 'Polling job pushed');
        } elseif ($jobType == 'tus') {
            $this->setProgress($queue, 0.6, 'Pushing TUS job');
            $tusJob = new TusUploadVideoJob([
                'elementId' => $this->elementId,
                'fieldHandle' => $this->fieldHandle,
                'videoUid' => $result['uid'],
                'videoLocation' => $result['location'],
                'videoPath' => $result['fullPath'],
                'videoName' => $this->videoName,
            ]);
            \Craft::$app->getQueue()->push($tusJob);
            $this->setProgress($queue, 0.7, 'TUS job pushed');
        } else {
            $this->setProgress($queue, 0.6, 'ERROR: Unknown job type');
            \Craft::error('Unknown job type: ' . $jobType, __METHOD__);

            throw new \Error('Unknown job type');
        }

        // Log the success
        \Craft::info('Video uploaded to Cloudflare Stream.', __METHOD__);
        $this->setProgress($queue, 1, 'Upload successful');
    }

    protected function defaultDescription(): ?string
    {
        return 'Uploading video to Cloudflare Stream and updating field value';
    }
}
