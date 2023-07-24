<?php

namespace deuxhuithuit\cfstream\controllers;

use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\jobs\UploadVideoJob;
use Craft;
use craft\web\Controller;

class UploadController extends Controller
{
    public $controllerNamespace = 'deuxhuithuit\cfstream\controllers';

    public function actionUpload()
    {
        $this->requirePostRequest();
        $elementId = Craft::$app->getRequest()->getBodyParam('elementId');
        if (!$elementId) {
            return $this->asJson(['success' => false, 'message' => 'No element ID provided.']);
        }
        $fieldHandle = Craft::$app->getRequest()->getBodyParam('fieldHandle');
        if (!$fieldHandle) {
            return $this->asJson(['success' => false, 'message' => 'No field handle provided.']);
        }
        $videoUrl = Craft::$app->getRequest()->getBodyParam('videoUrl');
        if (!$videoUrl) {
            return $this->asJson(['success' => false, 'message' => 'No video URL provided.']);
        }
        $videoName = Craft::$app->getRequest()->getBodyParam('videoName');
        if (!$videoName) {
            return $this->asJson(['success' => false, 'message' => 'No video name provided.']);
        }
        $element = Craft::$app->getElements()->getElementById($elementId);
        if (!$element) {
            return $this->asJson(['success' => false, 'message' => 'Element not found.']);
        }
        /**
         * @var CloudflareVideoStreamField $field
         */
        $field = Craft::$app->getFields()->getFieldByHandle($fieldHandle);
        if (!$field instanceof CloudflareVideoStreamField) {
            return $this->asJson(['success' => false, 'message' => 'Field is not a Cloudflare Video Stream field.']);
        }
        $uploadJob = new UploadVideoJob([
            'fieldHandle' => $fieldHandle,
            'elementId' => $elementId,
            'videoUrl' => $videoUrl,
            'videoName' => $videoName
        ]);
        Craft::$app->getQueue()->push($uploadJob);
        $element->setFieldValue($fieldHandle, ['readyToStream' => false]);
        Craft::$app->getElements()->saveElement($element, true, true, false);
        Craft::$app->getSession()->setNotice(Craft::t('cloudflare-stream', 'Video added to Cloudflare Stream successfully'));
        return $this->asJson(['success' => true]);
    }
}
