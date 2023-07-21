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
        $elementId = Craft::$app->getRequest()->getBodyParam('elementId');
        $fieldHandle = Craft::$app->getRequest()->getBodyParam('fieldHandle');
        $videoUrl = Craft::$app->getRequest()->getBodyParam('videoUrl');
        $videoName = Craft::$app->getRequest()->getBodyParam('videoName');
        $element = Craft::$app->getElements()->getElementById($elementId);
        $field = Craft::$app->getFields()->getFieldByHandle($fieldHandle);
        if ($field instanceof CloudflareVideoStreamField) {
            $uploadJob = new UploadVideoJob([
                'fieldHandle' => $fieldHandle,
                'elementId' => $elementId,
                'videoUrl' => $videoUrl,
                'videoName' => $videoName
            ]);
            Craft::$app->getQueue()->push($uploadJob);
            $element->setFieldValue($this->fieldHandle, ['readyToStream' => false]);
            Craft::$app->getElements()->saveElement($element);
        }
        return $this->asJson(['success' => true]);
    }
}
