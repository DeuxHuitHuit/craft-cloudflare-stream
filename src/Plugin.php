<?php

namespace deuxhuithuit\cfstream;

use craft\base\Element;
use craft\base\Model;
use craft\console\Controller;
use craft\elements\Asset;
use craft\events\AssetPreviewEvent;
use craft\events\DefineAssetThumbUrlEvent;
use craft\events\DefineConsoleActionsEvent;
use craft\events\ModelEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\services\Assets;
use craft\services\Fields;
use craft\web\View;
use deuxhuithuit\cfstream\controllers\ReuploadController;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\jobs\DeleteVideoJob;
use deuxhuithuit\cfstream\jobs\UploadVideoJob;
use deuxhuithuit\cfstream\models\Settings;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.4.0';

    public function __construct($id, $parent = null, array $config = [])
    {
        \Craft::setAlias('@plugin/cloudflare-stream', $this->getBasePath());
        \Craft::setAlias('@plugin/cloudflare-stream/resources', $this->getBasePath() . DIRECTORY_SEPARATOR . 'resources');
        $this->controllerNamespace = 'deuxhuithuit\cfstream\controllers';

        // Base template directory
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $e) {
            if (is_dir($baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                $e->roots[$this->id] = $baseDir;
            }
        });

        // Set this as the global instance of this module class
        static::setInstance($this);

        parent::__construct($id, $parent, $config);
    }

    /**
     * @param ModelEvent $event
     * @return void
     */
    public function beforeSave(ModelEvent $event)
    {
        // If this isn't a new asset, we don't need to do anything
        if (!$this->isNewValidEvent($event)) {
            return;
        }

        /** @var Asset $asset */
        $asset = $event->sender;
        $streamField = CloudflareVideoStreamField::findStreamingFieldForAsset($asset);
        // If the asset doesn't have a Stream field, we don't need to do anything
        if (!$streamField) {
            return;
        }

        // Since this asset has a Stream field, we need to make sure it's a video asset
        $event->isValid = $this->isVideoAsset($asset);
    }

    /**
     * @param ModelEvent $event
     * @return void
     */
    public function beforeAutoUpload(ModelEvent $event)
    {
        /** @var Asset $asset */
        $asset = $event->sender;
        if (!$this->isNewVideoAsset($asset)) {
            return;
        }
        if (!$this->isNewValidEvent($event)) {
            return;
        }
        if (!$this->isAutoUploadEnabled()) {
            return;
        }

        $streamField = CloudflareVideoStreamField::findStreamingFieldForAsset($asset);
        if (!$streamField) {
            return;
        }

        // Set the field value to "not ready"
        // We need to do this BEFORE the entry gets saved
        $asset->setFieldValue($streamField->handle, ['readyToStream' => false]);
    }

    public function autoUpload(ModelEvent $event)
    {
        /** @var Asset $asset */
        $asset = $event->sender;
        if (!$this->isNewVideoAsset($asset)) {
            return;
        }
        if (!$this->isNewValidEvent($event)) {
            return;
        }
        if (!$this->isAutoUploadEnabled()) {
            return;
        }

        $streamField = CloudflareVideoStreamField::findStreamingFieldForAsset($asset);
        if (!$streamField) {
            return;
        }

        // Create and push a new upload job.
        // This needs to be done AFTER the element gets saved,
        // since we need its id and url.
        $uploadJob = new UploadVideoJob([
            'fieldHandle' => $streamField->handle,
            'elementId' => $asset->id,
            'videoUrl' => $asset->getUrl(),
            'videoName' => $asset->filename,
            'videoPath' => Folder::getAssetFolderPath($asset),
            'videoTitle' => $asset->title,
        ]);
        \Craft::$app->getQueue()->push($uploadJob);
    }

    public function autoDelete(ModelEvent $event)
    {
        /** @var Asset $asset */
        $asset = $event->sender;
        if (!$this->isVideoAsset($asset)) {
            return;
        }
        if (!$this->isAutoUploadEnabled()) {
            return;
        }

        // For each Stream field, create and push a new delete job, if required.
        $fields = $asset->getFieldLayout()->getCustomFields();
        foreach ($fields as $field) {
            if (!$field instanceof CloudflareVideoStreamField) {
                continue;
            }

            $fieldData = $asset->getFieldValue($field->handle);
            if (!$fieldData || !isset($fieldData['uid'])) {
                continue;
            }

            $uploadJob = new DeleteVideoJob([
                'fieldHandle' => $field->handle,
                'elementId' => $asset->id,
                'videoUid' => $fieldData['uid'],
            ]);
            \Craft::$app->getQueue()->push($uploadJob);
        }
    }

    public function init()
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CloudflareVideoStreamField::class;
            }
        );

        \Craft::$app->getAssets()->on(
            Assets::EVENT_DEFINE_THUMB_URL,
            function (DefineAssetThumbUrlEvent $event) {
                if (!$this->isVideoAsset($event->asset)) {
                    return;
                }

                $streamField = CloudflareVideoStreamField::findStreamingFieldForAsset($event->asset);
                if (!$streamField) {
                    return;
                }

                $fieldData = $event->asset->getFieldValue($streamField->handle);
                if (!$fieldData || !isset($fieldData['thumbnail'])) {
                    return;
                }

                // Use the Stream thumbnail as the asset thumbnail
                $event->url = $fieldData['thumbnail'];
            }
        );

        \Craft::$app->getAssets()->on(
            Assets::EVENT_REGISTER_PREVIEW_HANDLER,
            function (AssetPreviewEvent $event) {
                if (!$this->isVideoAsset($event->asset)) {
                    return;
                }

                $streamField = CloudflareVideoStreamField::findStreamingFieldForAsset($event->asset);
                if (!$streamField) {
                    return;
                }

                $fieldData = $event->asset->getFieldValue($streamField->handle);
                if (!$fieldData) {
                    return;
                }

                // Use our custom preview handler
                $event->previewHandler = new assetpreviews\AssetPreviewHandler($event->asset, $fieldData);
            }
        );

        Event::on(
            Asset::class,
            Element::EVENT_BEFORE_SAVE,
            [$this, 'beforeSave']
        );

        Event::on(
            Asset::class,
            Element::EVENT_BEFORE_SAVE,
            [$this, 'beforeAutoUpload']
        );

        Event::on(
            Asset::class,
            Element::EVENT_AFTER_SAVE,
            [$this, 'autoUpload']
        );

        Event::on(
            Asset::class,
            Element::EVENT_BEFORE_RESTORE,
            [$this, 'autoUpload']
        );

        Event::on(
            Asset::class,
            Element::EVENT_BEFORE_DELETE,
            [$this, 'autoDelete']
        );

        Event::on(
            ReuploadController::class,
            Controller::EVENT_DEFINE_ACTIONS,
            function (DefineConsoleActionsEvent $event) {
                $event->actions['reupload'] = [
                    'options' => [],
                    'helpSummary' => 'Re-uploads all Cloudflare stream assets.',
                    'action' => function (): int {
                        /** @var CliController $controller */
                        $controller = \Craft::$app->controller;

                        return $controller->actionReupload();
                    },
                ];
            }
        );

        parent::init();
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return \Craft::$app->getView()->renderTemplate(
            'cloudflare-stream/settings',
            ['settings' => $this->getSettings()]
        );
    }

    private function isAutoUploadEnabled(): bool
    {
        /** @var Settings $settings */
        $settings = $this->getSettings();

        return $settings->isAutoUpload();
    }

    private function isVideoAsset(?Asset $asset): bool
    {
        if (!$asset) {
            return false;
        }

        return $asset->kind === Asset::KIND_VIDEO;
    }

    private function isNewVideoAsset(?Asset $asset): bool
    {
        return $this->isVideoAsset($asset)
            && $asset->getScenario() === Asset::SCENARIO_CREATE;
    }

    private function isNewValidEvent(ModelEvent $event): bool
    {
        return $event->isNew && $event->isValid;
    }
}
