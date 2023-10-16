<?php

namespace deuxhuithuit\cfstream\jobs;

use craft\queue\BaseJob;
use deuxhuithuit\cfstream\client\CloudflareVideoStreamClient;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;

// TODO: Make cancellable, to cancel the upload if the asset is deleted
class UploadVideoJob extends BaseJob implements \yii\queue\RetryableJobInterface
{
    public $fieldHandle;
    public $elementId;
    public $videoUrl;
    public $videoName;

    public function getTtr()
    {
        return 15 * 60;
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
            $this->setProgress($queue, 1, 'Element not found');

            return;
        }

        // Get the CloudflareVideoStreamField by its handle
        $field = \Craft::$app->getFields()->getFieldByHandle($this->fieldHandle);
        if (!$field) {
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

        $client = new CloudflareVideoStreamClient(\deuxhuithuit\cfstream\Plugin::getInstance()->getSettings());
        $result = $client->uploadVideo($this->videoUrl, $this->videoName);

        $this->setProgress($queue, 0.3, 'Uploading request returned');

        if (!$result) {
            $this->setProgress($queue, 0.3, 'ERROR: Upload request failed');

            throw new \Error('Upload request failed');
        }
        if (!empty($result['error'])) {
            $this->setProgress($queue, 0.3, 'ERROR: ' . $result['error']);

            throw new \Error($result['error'] . ' ' . $result['message']);
        }

        $this->setProgress($queue, 0.4, 'Saving craft element');
        $element->setFieldValue($this->fieldHandle, $result);
        // element, runValidation, propagate, updateIndex
        if (!\Craft::$app->getElements()->saveElement($element, true, true, false)) {
            $this->setProgress($queue, 1, 'ERROR: Could not save element');

            throw new \Error('Could not save element');
        }
        $this->setProgress($queue, 0.5, 'Craft element saved');

        $this->setProgress($queue, 0.6, 'Pushing polling job');
        $pollingJob = new PollVideoJob([
            'elementId' => $this->elementId,
            'fieldHandle' => $this->fieldHandle,
            'videoUid' => $result['uid'],
        ]);
        \Craft::$app->getQueue()->push($pollingJob);
        $this->setProgress($queue, 0.7, 'Polling job pushed');

        // Log the success
        \Craft::info('Video uploaded to Cloudflare Stream.', __METHOD__);
        $this->setProgress($queue, 1, 'Upload successful');
    }

    protected function defaultDescription(): ?string
    {
        return 'Uploading video to Cloudflare Stream and updating field value';
    }
}
