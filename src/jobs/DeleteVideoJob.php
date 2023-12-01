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
    public $validateElementIsDeleted = true;

    public function getTtr()
    {
        return 30; // 30 seconds
    }

    public function execute($queue): void
    {
        $this->setProgress($queue, 0, 'Validating job data');

        // Check to make sure the entry does not exist anymore, if requested
        if ($this->validateElementIsDeleted) {
            $element = \Craft::$app->getElements()->getElementById($this->elementId);
            if ($element) {
                throw new \Error('Element still exists');
            }
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

        $this->setProgress($queue, 0.2, 'Sending delete request to Cloudflare Stream');

        $client = new CloudflareVideoStreamClient(\deuxhuithuit\cfstream\Plugin::getInstance()->settings);
        $result = $client->deleteVideo($this->videoUid);

        $this->setProgress($queue, 0.3, 'Delete request returned');

        if (!$result) {
            $this->setProgress($queue, 0.3, 'ERROR: Upload request failed');
            \Craft::error('Delete request failed.', __METHOD__);

            throw new \Error('Delete request failed');
        } elseif (!empty($result['error'])) {
            $this->setProgress($queue, 0.3, 'ERROR: ' . $result['error']);
            \Craft::error('Delete request failed.' . $result['error'] . ' ' . $result['message'], __METHOD__);

            throw new \Error($result['error'] . ' ' . $result['message']);
        }

        // Log the success
        \Craft::info('Video deleted from Cloudflare Stream.', __METHOD__);
        $this->setProgress($queue, 1, 'Delete successful');
    }

    protected function defaultDescription(): ?string
    {
        return "Deleting video {$this->videoUid} from Cloudflare Stream";
    }
}
