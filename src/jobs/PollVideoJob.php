<?php

namespace deuxhuithuit\cfstream\jobs;

use Craft;
use craft\queue\BaseJob;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\client\CloudflareVideoStreamClient;

class PollVideoJob extends BaseJob
{
    public $elementId;
    public $fieldHandle;
    public $videoUid;

    public function execute($queue): void
    {
        // Get the entry or element where the field is located
        $element = Craft::$app->getElements()->getElementById($this->elementId);
        if (!$element) {
            throw new \Exception('Element not found.');
        }

        // Get the CloudflareVideoStreamField by its handle
        $field = Craft::$app->getFields()->getFieldByHandle($this->fieldHandle);
        if (!$field) {
            throw new \Exception('Field not found.');
        }

        $this->setProgress($queue, 0.1, 'Validating Cloudflare Video Stream field');

        // Check if the field is a CloudflareVideoStreamField
        if (!$field instanceof CloudflareVideoStreamField) {
            $this->setProgress($queue, 0.1, 'ERROR: Field is not a Cloudflare Video Stream field');
            throw new \Exception('Field is not a Cloudflare Video Stream field');
        }

        $this->setProgress($queue, 0.2, 'Sending poll request to Cloudflare Stream');

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
            Craft::$app->getElements()->saveElement($element, true, true, false);

            $this->setProgress($queue, 1, 'Done');
        } else {
            $this->setProgress($queue, 0, 'Delayed retry');
            // Retry the job after 10 seconds
            $queue->delay(10)->push($this);
        }
    }

    protected function defaultDescription(): ?string
    {
        return 'Polling video and updating field value';
    }
}
