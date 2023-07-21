<?php

namespace deuxhuithuit\cfstream\controllers;

use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\jobs\DeleteVideoJob;
use Craft;
use craft\web\Controller;

class DeleteController extends Controller
{
    public $controllerNamespace = 'deuxhuithuit\cfstream\controllers';

    public function actionDelete()
    {
        $elementId = Craft::$app->getRequest()->getBodyParam('elementId');
        $fieldHandle = Craft::$app->getRequest()->getBodyParam('fieldHandle');
        $videoUid = Craft::$app->getRequest()->getBodyParam('videoUid');
        $videoName = Craft::$app->getRequest()->getBodyParam('videoName');
        $element = Craft::$app->getElements()->getElementById($elementId);
        $field = Craft::$app->getFields()->getFieldByHandle($fieldHandle);
        if ($field instanceof CloudflareVideoStreamField) {
            $deleteJob = new DeleteVideoJob([
                'fieldHandle' => $fieldHandle,
                'elementId' => $elementId,
                'videoUid' => $videoUid
            ]);
            Craft::$app->getQueue()->push($deleteJob);
            $element->setFieldValue($this->fieldHandle, null);
            Craft::$app->getElements()->saveElement($element);
        }
        return $this->asJson(['success' => true]);
    }
}
