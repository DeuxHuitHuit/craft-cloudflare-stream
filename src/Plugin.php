<?php

namespace deuxhuithuit\cfstream;

use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\services\Fields;
use craft\web\View;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\jobs\DeleteVideoJob;
use deuxhuithuit\cfstream\jobs\UploadVideoJob;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public bool $hasCpSettings = true;

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

    public function beforeAutoUpload(\craft\events\ModelEvent $event)
    {
        /** @var \craft\elements\Asset $asset */
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

        $streamField = $this->findStreamingField($asset);
        if (!$streamField) {
            return;
        }

        // Set the field value to "not ready"
        // We need to do this BEFORE the entry gets saved
        $asset->setFieldValue($streamField->handle, ['readyToStream' => false]);
    }

    public function autoUpload(\craft\events\ModelEvent $event)
    {
        /** @var \craft\elements\Asset $asset */
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

        $streamField = $this->findStreamingField($asset);
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
        ]);
        \Craft::$app->getQueue()->push($uploadJob);
    }

    public function autoDelete(\craft\events\ModelEvent $event)
    {
        /** @var \craft\elements\Asset $asset */
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
            \craft\services\Assets::EVENT_DEFINE_THUMB_URL,
            function (\craft\events\DefineAssetThumbUrlEvent $event) {
                if (!$this->isVideoAsset($event->asset)) {
                    return;
                }

                $streamField = $this->findStreamingField($event->asset);
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
            \craft\services\Assets::EVENT_REGISTER_PREVIEW_HANDLER,
            function (\craft\events\AssetPreviewEvent $event) {
                if (!$this->isVideoAsset($event->asset)) {
                    return;
                }

                $streamField = $this->findStreamingField($event->asset);
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
            \craft\elements\Asset::class,
            \craft\base\Element::EVENT_BEFORE_SAVE,
            [$this, 'beforeAutoUpload']
        );

        Event::on(
            \craft\elements\Asset::class,
            \craft\base\Element::EVENT_AFTER_SAVE,
            [$this, 'autoUpload']
        );

        Event::on(
            \craft\elements\Asset::class,
            \craft\base\Element::EVENT_BEFORE_RESTORE,
            [$this, 'autoUpload']
        );

        Event::on(
            \craft\elements\Asset::class,
            \craft\base\Element::EVENT_BEFORE_DELETE,
            [$this, 'autoDelete']
        );

        parent::init();
    }

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new \deuxhuithuit\cfstream\models\Settings();
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
        /** @var \deuxhuithuit\cfstream\models\Settings $settings */
        $settings = $this->getSettings();

        return $settings->isAutoUpload();
    }

    private function isVideoAsset(?\craft\elements\Asset $asset): bool
    {
        if (!$asset) {
            return false;
        }

        return $asset->kind === \craft\elements\Asset::KIND_VIDEO;
    }

    private function isNewVideoAsset(?\craft\elements\Asset $asset): bool
    {
        return $this->isVideoAsset($asset)
            && $asset->getScenario() === \craft\elements\Asset::SCENARIO_CREATE;
    }

    private function isNewValidEvent(\craft\events\ModelEvent $event): bool
    {
        return $event->isNew && $event->isValid;
    }

    private function findStreamingField(\craft\elements\Asset $asset): ?CloudflareVideoStreamField
    {
        $fields = $asset->getFieldLayout()->getCustomFields();

        /** @var null|CloudflareVideoStreamField $streamField */
        $streamField = null;
        foreach ($fields as $field) {
            if ($field instanceof CloudflareVideoStreamField) {
                $streamField = $field;

                break;
            }
        }

        return $streamField;
    }
}
