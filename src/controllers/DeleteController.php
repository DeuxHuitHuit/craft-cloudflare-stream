<?php

namespace deuxhuithuit\cfstream\controllers;

use craft\web\Controller;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\jobs\DeleteVideoJob;

class DeleteController extends Controller
{
    public $controllerNamespace = 'deuxhuithuit\cfstream\controllers';

    public function actionDelete()
    {
        $this->requirePostRequest();
        $elementId = \Craft::$app->getRequest()->getBodyParam('elementId');
        $fieldHandle = \Craft::$app->getRequest()->getBodyParam('fieldHandle');
        $videoUid = \Craft::$app->getRequest()->getBodyParam('videoUid');
        $element = \Craft::$app->getElements()->getElementById($elementId);
        $field = \Craft::$app->getFields()->getFieldByHandle($fieldHandle);
        if (!$field instanceof CloudflareVideoStreamField) {
            return $this->asJson(['success' => false, 'message' => 'Field is not a Cloudflare Video Stream field.']);
        }
        $deleteJob = new DeleteVideoJob([
            'fieldHandle' => $fieldHandle,
            'elementId' => $elementId,
            'videoUid' => $videoUid,
        ]);
        \Craft::$app->getQueue()->push($deleteJob);
        $element->setFieldValue($fieldHandle, null);
        \Craft::$app->getElements()->saveElement($element, true, true, false);
        \Craft::$app->getSession()->setNotice(\Craft::t('cloudflare-stream', 'Video removed from Cloudflare Stream successfully'));

        return $this->asJson(['success' => true]);
    }
}
