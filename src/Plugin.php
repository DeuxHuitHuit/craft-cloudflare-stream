<?php

namespace deuxhuithuit\cfstream;

use Craft;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use craft\services\Fields;
use craft\web\View;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Event;
use craft\events\RegisterTemplateRootsEvent;

class Plugin extends \craft\base\Plugin
{
    /**
    * @inheritdoc
    */
    public function __construct($id, $parent = null, array $config = [])
    {
        Craft::setAlias('@plugin/cfstream', $this->getBasePath());
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

        parent::init();
    }

    public static function getConfig()
    {
        $config = require Craft::$app->getPath()->getConfigPath()
            . DIRECTORY_SEPARATOR . 'cfstream'
            . DIRECTORY_SEPARATOR . 'config.php';
        return $config;
    }
}
