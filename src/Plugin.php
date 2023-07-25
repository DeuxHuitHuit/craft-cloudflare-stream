<?php

namespace deuxhuithuit\cfstream;

use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\services\Fields;
use craft\web\View;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
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

    public function init()
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CloudflareVideoStreamField::class;
            }
        );

        // TODO
        // EVENT_AFTER_DELETE
        // EVENT_BEFORE_RESTORE

        Event::on(
            \craft\elements\Asset::class,
            \craft\base\Element::EVENT_BEFORE_SAVE,
            function (\craft\events\ModelEvent $event) {
                /** @var \craft\elements\Asset $asset */
                $asset = $event->sender;
                if (!$asset instanceof \craft\elements\Asset) {
                    return;
                }
                if ($asset->kind !== \craft\elements\Asset::KIND_VIDEO) {
                    return;
                }
                if (!$event->isNew) {
                    return;
                }
                if (!$event->isValid) {
                    return;
                }

                /** @var \deuxhuithuit\cfstream\models\Settings $settings */
                $settings = $this->getSettings();
                if (!$settings->isAutoUpload()) {
                    return;
                }

                $fields = $asset->getFieldLayout()->getCustomFields();

                /** @var null|CloudflareVideoStreamField $streamField */
                $streamField = null;
                foreach ($fields as $field) {
                    if ($field instanceof CloudflareVideoStreamField) {
                        $streamField = $field;

                        break;
                    }
                }

                if (!$streamField) {
                    return;
                }

                $uploadJob = new UploadVideoJob([
                    'fieldHandle' => $streamField->handle,
                    'elementId' => $asset->id,
                    'videoUrl' => $asset->getUrl(),
                    'videoName' => $asset->filename,
                ]);
                \Craft::$app->getQueue()->push($uploadJob);
                $asset->setFieldValue($streamField->handle, ['readyToStream' => false]);
            }
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
}
