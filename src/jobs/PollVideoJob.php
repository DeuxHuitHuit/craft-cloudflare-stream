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

        $this->setProgress($queue, 0.2, 'Sending poll request to Cloudflare Stream');

        ++$this->attempts;
        $client = new CloudflareVideoStreamClient(\deuxhuithuit\cfstream\Plugin::getInstance()->getSettings());
        $result = $client->getVideo($this->videoUid);

        $this->setProgress($queue, 0.3, 'Poll request returned');

        $ready = $result ? $result['readyToStream'] : false;
        if ($ready) {
            $this->setProgress($queue, 0.4, 'Sending download url request to Cloudflare Stream');
            $mp4Url = $client->getDownloadUrl($this->videoUid);
            $this->setProgress($queue, 0.5, 'Download url request returned');

            $this->setProgress($queue, 0.6, 'Saving field value');
            $element->setFieldValue($this->fieldHandle, array_merge($result, ['mp4Url' => $mp4Url]));
            // element, runValidation, propagate, updateIndex
            if (!\Craft::$app->getElements()->saveElement($element, true, true, false)) {
                $this->setProgress($queue, 1, 'ERROR: Could not save element');

                throw new \Error('Could not save element');
            }

            $this->setProgress($queue, 1, 'Done');
        } else {
            if ($result) {
                $this->setProgress($queue, 0.4, 'Saving last result into the field');
                $element->setFieldValue($this->fieldHandle, $result);

                // Save, but ignore errors.
                // element, runValidation, propagate, updateIndex
                \Craft::$app->getElements()->saveElement($element, true, true, false);
            }

            $this->setProgress($queue, 0, 'Delayed retry');
            // Retry the job after x * 2 seconds
            $this->lastResult = $result;
            $queue->delay($this->attempts * 2)->push($this);
        }
    }

    protected function defaultDescription(): ?string
    {
        return 'Polling video and updating field value';
    }
}
