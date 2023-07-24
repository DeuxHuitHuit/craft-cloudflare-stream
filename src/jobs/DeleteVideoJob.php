<?php

namespace deuxhuithuit\cfstream\jobs;

use Craft;
use craft\queue\BaseJob;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\client\CloudflareVideoStreamClient;

class DeleteVideoJob extends BaseJob
{
    public $fieldHandle;
    public $elementId;
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

        $this->setProgress($queue, 0.2, 'Sending delete request to Cloudflare Stream');

        $client = new CloudflareVideoStreamClient(\deuxhuithuit\cfstream\Plugin::getInstance()->settings);
        $client->deleteVideo($this->videoUid);

        $this->setProgress($queue, 0.3, 'Delete request returned');

        $this->setProgress($queue, 0.4, 'Updating field value');
        $element->setFieldValue($this->fieldHandle, null);
        Craft::$app->getElements()->saveElement($element, true, true, false);

        $this->setProgress($queue, 1, 'Done');
    }

    protected function defaultDescription(): ?string
    {
        return 'Deleting video from Cloudflare Stream and updating field value';
    }
}
