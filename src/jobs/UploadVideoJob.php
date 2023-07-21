<?php

namespace deuxhuithuit\cfstream\jobs;

use Craft;
use craft\queue\BaseJob;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\client\CloudflareVideoStreamClient;

class UploadVideoJob extends BaseJob
{
    public $fieldHandle;
    public $elementId;
    public $videoUrl;
    public $videoName;

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

        $client = new CloudflareVideoStreamClient();
        $result = $client->uploadVideo($this->videoUrl, $this->videoName);

        if (!$result) {
            return;
        }
        $element->setFieldValue($this->fieldHandle, $result);
        Craft::$app->getElements()->saveElement($element);
        $pollingJob = new PollVideoJob([
            'elementId' => $this->elementId,
            'fieldHandle' => $this->fieldHandle,
            'videoUid' => $result['uid']
        ]);
        Craft::$app->getQueue()->push($pollingJob);
    }

    protected function defaultDescription(): ?string
    {
        return 'Uploading video to Cloudflare Stream and updating field value';
    }
}
