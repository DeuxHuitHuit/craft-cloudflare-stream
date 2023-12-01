<?php

namespace deuxhuithuit\cfstream\fields;

use craft\base\ElementInterface;
use craft\base\Field;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use deuxhuithuit\cfstream\assetbundles\CloudflareVideoStreamAssetBundle;
use deuxhuithuit\cfstream\graphql\types\CloudflareVideoStreamType;
use deuxhuithuit\cfstream\Plugin;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;

class CloudflareVideoStreamField extends Field
{
    public static function displayName(): string
    {
        return 'Cloudflare Video Stream';
    }

    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    public function normalizeValue(mixed $value, ElementInterface $element = null): mixed
    {
        return Json::decodeIfJson($value);
    }

    public function serializeValue(mixed $value, ElementInterface $element = null): mixed
    {
        return Json::encode($value);
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        \Craft::$app->getView()->registerAssetBundle(CloudflareVideoStreamAssetBundle::class);

        // Make sure it's a video
        if (!$element || $element->kind !== \craft\elements\Asset::KIND_VIDEO) {
            return \Craft::$app->getView()->renderTemplate('cloudflare-stream/not-a-video');
        }

        /** @var \deuxhuithuit\cfstream\models\Settings */
        $settings = Plugin::getInstance()->getSettings();

        // Validate settings
        if (!$settings->getAccountId() || !$settings->getApiToken()) {
            return \Craft::$app->getView()->renderTemplate('cloudflare-stream/missing-settings', [
                'name' => $this->handle,
                'settingsUrl' => UrlHelper::cpUrl('settings/plugins/cloudflare-stream'),
                'element' => $element,
            ]);
        }

        // Validate value in DB
        if (!$value) {
            return \Craft::$app->getView()->renderTemplate('cloudflare-stream/upload', [
                'name' => $this->handle,
                'actionUrl' => UrlHelper::actionUrl('cloudflare-stream/upload/upload'),
                'element' => $element,
            ]);
        }

        // Not added to stream
        if (!isset($value['meta'])) {
            return \Craft::$app->getView()->renderTemplate('cloudflare-stream/added', [
                'name' => $this->handle,
                'value' => $value,
                'element' => $element,
            ]);
        }

        // Not ready to stream (i.e. still processing...)
        if (!$value['readyToStream']) {
            return \Craft::$app->getView()->renderTemplate('cloudflare-stream/uploading', [
                'name' => $this->handle,
                'value' => $value,
                'element' => $element,
            ]);
        }

        // Ready to stream
        return \Craft::$app->getView()->renderTemplate('cloudflare-stream/video', [
            'name' => $this->handle,
            'actionUrl' => UrlHelper::actionUrl('cloudflare-stream/delete/delete'),
            'element' => $element,
            'value' => $value,
            // This is to maintain compatibility with pre 1.4.5 versions:
            // If the completed key is not set, we assume it's true
            'completed' => !isset($value['completed']) || $value['completed'],
        ]);
    }

    public function getContentGqlType(): array|Type
    {
        $type = new CloudflareVideoStreamType();
        $typeName = $type->name;
        $videoType = GqlEntityRegistry::getEntity($typeName)
            ?: GqlEntityRegistry::createEntity($typeName, $type);

        TypeLoader::registerType($typeName, static function () use ($videoType) {
            return $videoType;
        });

        return $videoType;
    }
}
