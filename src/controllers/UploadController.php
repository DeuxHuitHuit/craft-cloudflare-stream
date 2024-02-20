<?php

namespace deuxhuithuit\cfstream\controllers;

use craft\web\Controller;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\jobs\UploadVideoJob;

class UploadController extends Controller
{
    public $controllerNamespace = 'deuxhuithuit\cfstream\controllers';

    public function actionUpload()
    {
        $this->requirePostRequest();
        // Validate body params
        $elementId = \Craft::$app->getRequest()->getBodyParam('elementId');
        if (!$elementId) {
            return $this->asJson(['success' => false, 'message' => 'No element ID provided.']);
        }
        $fieldHandle = \Craft::$app->getRequest()->getBodyParam('fieldHandle');
        if (!$fieldHandle) {
            return $this->asJson(['success' => false, 'message' => 'No field handle provided.']);
        }

        // Get and validate element
        $element = \Craft::$app->getElements()->getElementById($elementId);
        if (!$element) {
            return $this->asJson(['success' => false, 'message' => 'Element not found.']);
        } elseif (!($element instanceof \craft\elements\Asset)) {
            return $this->asJson(['success' => false, 'message' => 'Element not an asset.']);
        }

        // Get and validate field
        /**
         * @var CloudflareVideoStreamField $field
         */
        $field = \Craft::$app->getFields()->getFieldByHandle($fieldHandle);
        if (!$field) {
            return $this->asJson(['success' => false, 'message' => 'Field not found.']);
        } elseif (!($field instanceof CloudflareVideoStreamField)) {
            return $this->asJson(['success' => false, 'message' => 'Field is not a Cloudflare Video Stream field.']);
        }

        // Update the asset to be ready to stream
        $element->setFieldValue($fieldHandle, ['readyToStream' => false]);
        // element, runValidation, propagate, updateIndex
        if (!\Craft::$app->getElements()->saveElement($element, true, true, false)) {
            return $this->asJson(['success' => false, 'message' => 'Failed to save asset.']);
        }

        // Create and push a new upload job, using the element's data.
        $uploadJob = new UploadVideoJob([
            'fieldHandle' => $fieldHandle,
            'elementId' => $elementId,
            'videoUrl' => $element->getUrl(),
            'videoName' => $element->filename,
            'videoPath' => \deuxhuithuit\cfstream\Folder::getAssetFolderPath($element),
            'videoTitle' => $element->title,
        ]);
        \Craft::$app->getQueue()->push($uploadJob);

        // Display a notice to the user
        \Craft::$app->getSession()->setNotice(
            \Craft::t('cloudflare-stream', 'Video added to Cloudflare Stream successfully')
        );

        return $this->asJson(['success' => true]);
    }
}
