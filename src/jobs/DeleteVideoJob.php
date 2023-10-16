<?php

namespace deuxhuithuit\cfstream\jobs;

use craft\queue\BaseJob;
use deuxhuithuit\cfstream\client\CloudflareVideoStreamClient;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;

class DeleteVideoJob extends BaseJob
{
    public $fieldHandle;
    public $elementId;
    public $videoUid;

    public function getTtr()
    {
        return 30; // 30 seconds
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

        $this->setProgress($queue, 0.2, 'Sending delete request to Cloudflare Stream');

        $client = new CloudflareVideoStreamClient(\deuxhuithuit\cfstream\Plugin::getInstance()->settings);
        $client->deleteVideo($this->videoUid);

        $this->setProgress($queue, 0.3, 'Delete request returned');

        $this->setProgress($queue, 0.4, 'Updating field value');
        $element->setFieldValue($this->fieldHandle, null);
        // element, runValidation, propagate, updateIndex
        if (!\Craft::$app->getElements()->saveElement($element, true, true, false)) {
            $this->setProgress($queue, 1, 'ERROR: Could not save element');

            throw new \Error('Could not save element');
        }

        $this->setProgress($queue, 1, 'Done');
    }

    protected function defaultDescription(): ?string
    {
        return 'Deleting video from Cloudflare Stream and updating field value';
    }
}
