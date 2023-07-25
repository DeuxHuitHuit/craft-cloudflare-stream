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
        $deleteJob = new DeleteVideoJob([
            'fieldHandle' => $fieldHandle,
            'elementId' => $elementId,
            'videoUid' => $videoUid,
        ]);
        \Craft::$app->getQueue()->push($deleteJob);
        $element->setFieldValue($fieldHandle, null);
        // element, runValidation, propagate, updateIndex
        if (!\Craft::$app->getElements()->saveElement($element, true, true, false)) {
            return $this->asJson(['success' => false, 'message' => 'Failed to save asset.']);
        }
        \Craft::$app->getSession()->setNotice(\Craft::t('cloudflare-stream', 'Video removed from Cloudflare Stream successfully'));

        return $this->asJson(['success' => true]);
    }
}
