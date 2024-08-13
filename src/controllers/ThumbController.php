<?php

namespace deuxhuithuit\cfstream\controllers;

use craft\web\Controller;
use deuxhuithuit\cfstream\client\CloudflareVideoStreamClient;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\Plugin;

class ThumbController extends Controller
{
    public $controllerNamespace = 'deuxhuithuit\cfstream\controllers';

    public function actionUpdate()
    {
        $this->requirePostRequest();
        $elementId = \Craft::$app->getRequest()->getBodyParam('elementId');
        if (!$elementId) {
            return $this->asJson(['success' => false, 'message' => 'No element ID provided.']);
        }

        $fieldHandle = \Craft::$app->getRequest()->getBodyParam('fieldHandle');
        if (!$fieldHandle) {
            return $this->asJson(['success' => false, 'message' => 'No field handle provided.']);
        }

        $videoUid = \Craft::$app->getRequest()->getBodyParam('videoUid');
        if (!$videoUid) {
            return $this->asJson(['success' => false, 'message' => 'No video Uid provided.']);
        }

        $element = \Craft::$app->getElements()->getElementById($elementId);
        if (!$element) {
            return $this->asJson(['success' => false, 'message' => 'Element not found.']);
        }

        $field = \Craft::$app->getFields()->getFieldByHandle($fieldHandle);
        if (!$field instanceof CloudflareVideoStreamField) {
            return $this->asJson(['success' => false, 'message' => 'Field is not a Cloudflare Video Stream field.']);
        }

        $time = \Craft::$app->getRequest()->getBodyParam('time');
        if (!$time) {
            return $this->asJson(['success' => false, 'message' => 'No time provided.']);
        }
        $time = (float) $time;
        if ($time < 0) {
            return $this->asJson(['success' => false, 'message' => 'Time must be a positive number.']);
        }

        $duration = \Craft::$app->getRequest()->getBodyParam('duration');
        if (!$duration) {
            return $this->asJson(['success' => false, 'message' => 'No duration provided.']);
        }
        $duration = (float) $duration;
        if ($duration <= 0) {
            return $this->asJson(['success' => false, 'message' => 'Duration must be a positive number.']);
        }

        $client = new CloudflareVideoStreamClient(Plugin::getInstance()->settings);
        $response = $client->updateThumbnail($videoUid, $time, $duration);

        if (isset($response['error'])) {
            throw new \Exception($response['error'] . ': ' . $response['message']);
        }

        // Success
        \Craft::$app->getSession()->setNotice(
            \Craft::t('cloudflare-stream', 'Thumbnail successfully updated! This might take a few minutes to reflect on the video.')
        );

        return $this->asJson(['success' => true]);
    }
}
