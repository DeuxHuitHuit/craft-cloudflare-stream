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

        // Get the CloudflareVideoStreamField by its handle
        $field = Craft::$app->getFields()->getFieldByHandle($this->fieldHandle);

        // Check if the field is a CloudflareVideoStreamField
        if (!$field instanceof CloudflareVideoStreamField) {
            return;
        }

        $client = new CloudflareVideoStreamClient(\deuxhuithuit\cfstream\Plugin::getInstance()->settings);
        $client->deleteVideo($this->videoUid);
        $element->setFieldValue($this->fieldHandle, null);
        Craft::$app->getElements()->saveElement($element);
    }

    protected function defaultDescription(): ?string
    {
        return 'Deleting video from Cloudflare Stream and updating field value';
    }
}
