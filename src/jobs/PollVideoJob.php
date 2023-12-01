<?php

namespace deuxhuithuit\cfstream\jobs;

use craft\queue\BaseJob;
use deuxhuithuit\cfstream\client\CloudflareVideoStreamClient;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;

class PollVideoJob extends BaseJob
{
    public $elementId;
    public $fieldHandle;
    public $videoUid;
    public $lastResult;
    public $attempts = 0;
    public $mp4Url;
    public $completed = false;

    public function getTtr()
    {
        return 10 + $this->delay();
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

        // Start the queue
        $this->setProgress($queue, 0.1, 'Validating Cloudflare Video Stream field');

        // Check if the field is a CloudflareVideoStreamField
        if (!$field instanceof CloudflareVideoStreamField) {
            $this->setProgress($queue, 0.1, 'ERROR: Field is not a Cloudflare Video Stream field');

            throw new \Error('Field is not a Cloudflare Video Stream field');
        }

        // Check if we have a videoUid
        if (!$this->videoUid) {
            $this->setProgress($queue, 0.1, 'ERROR: Missing videoUid');

            throw new \Error('Missing videoUid');
        }

        $this->setProgress($queue, 0.2, 'Sending poll request to Cloudflare Stream');

        ++$this->attempts;
        $client = new CloudflareVideoStreamClient(\deuxhuithuit\cfstream\Plugin::getInstance()->getSettings());
        $result = $client->getVideo($this->videoUid);

        $this->setProgress($queue, 0.3, 'Poll request returned');

        $ready = $result ? $result['readyToStream'] : false;
        $hasMp4Url = isset($this->mp4Url) && !empty($this->mp4Url);
        $this->completed = $ready &&
            isset($result['status']['pctComplete']) &&
            \floatval($result['status']['pctComplete']) === 100;

        // If the video is ready, request the creation of a download / mp4 url if not already done
        if ($ready && !$hasMp4Url) {
            $this->setProgress($queue, 0.4, 'Sending download url request to Cloudflare Stream');
            $this->mp4Url = $client->getDownloadUrl($this->videoUid);
            $this->setProgress($queue, 0.5, 'Download url request returned');

            $this->setProgress($queue, 0.6, 'Saving field value');
            $this->setFieldValue($element, $result);

            // element, runValidation, propagate, updateIndex
            if (!\Craft::$app->getElements()->saveElement($element, true, true, false)) {
                $this->setProgress($queue, 1, 'ERROR: Could not save element');

                throw new \Error('Could not save element');
            }
            // We now have a mp4 url
            $hasMp4Url = true;
        } elseif ($result) {
            // We have an intermediate results
            $this->setProgress($queue, 0.7, 'Saving last result into the field');
            $this->setFieldValue($element, $result);

            // Save it, but ignore errors.
            // element, runValidation, propagate, updateIndex
            \Craft::$app->getElements()->saveElement($element, true, true, false);
        }

        // Check if we need to retry the job.
        // We need to if the process is not completed or if we don't still have a mp4 url
        if (!$this->completed || !$hasMp4Url) {
            $this->setProgress($queue, 0, 'Delayed retry');
            // Retry the job after x * 2 seconds
            $this->lastResult = $result;
            $queue->delay($this->delay())->push($this);
        } else {
            // We are done !!!
            $this->setProgress($queue, 1, 'Done');
        }
    }

    protected function defaultDescription(): ?string
    {
        return "Polling video {$this->videoUid} for status updates.";
    }

    private function delay()
    {
        return $this->attempts * 2;
    }

    private function setFieldValue($element, array $result)
    {
        $element->setFieldValue($this->fieldHandle, array_merge($result, [
            'mp4Url' => $this->mp4Url,
            'completed' => $this->completed,
        ]));
    }
}
