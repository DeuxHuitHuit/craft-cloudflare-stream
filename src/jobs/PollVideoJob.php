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

        // Get the CloudflareVideoStreamField by its handle
        $field = Craft::$app->getFields()->getFieldByHandle($this->fieldHandle);

        // Check if the field is a CloudflareVideoStreamField
        if ($field instanceof CloudflareVideoStreamField) {
            $client = new CloudflareVideoStreamClient();
            $result = $client->getVideo($this->videoUid);
            $ready = $result ? $result['readyToStream'] : false;
            if ($ready) {
                $mp4Url = $client->getDownloadUrl($this->videoUid);
                $element->setFieldValue($this->fieldHandle, array_merge($result, ['mp4Url' => $mp4Url]));
                Craft::$app->getElements()->saveElement($element);
            } else {
                // Retry the job after 10 seconds
                $queue->delay(10)->push($this);
            }
        }
    }

    protected function defaultDescription(): ?string
    {
        return 'Polling video and updating field value';
    }
}
