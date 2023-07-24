<?php

namespace deuxhuithuit\cfstream\fields;

use deuxhuithuit\cfstream\assetbundles\CloudflareVideoStreamAssetBundle;
use deuxhuithuit\cfstream\graphql\types\CloudflareVideoStreamType;
use deuxhuithuit\cfstream\Plugin;
use yii\db\Schema;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Json;
use craft\web\View;
use craft\db\Migration;
use craft\helpers\UrlHelper;
use craft\helpers\Gql;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\models\GqlSchema;
use GraphQL\Type\Definition\Type;

class CloudflareVideoStreamField extends Field
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Cloudflare Video Stream';
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ElementInterface $element = null): mixed
    {
        return Json::decodeIfJson($value);
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ElementInterface $element = null): mixed
    {
        return Json::encode($value);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        Craft::$app->getView()->registerAssetBundle(CloudflareVideoStreamAssetBundle::class);

        $settings = Plugin::getInstance()->getSettings();

        // Valiate settings
        if (!$settings->accountId || !$settings->apiToken) {
            return Craft::$app->getView()->renderTemplate('cloudflare-stream/missing-settings', [
                'name' => $this->handle,
                'settingsUrl' => UrlHelper::cpUrl('settings/plugins/cloudflare-stream'),
                'element' => $element,
            ]);
        }

        // Validate value in DB
        if (!$value) {
            return Craft::$app->getView()->renderTemplate('cloudflare-stream/upload', [
                'name' => $this->handle,
                'actionUrl' => UrlHelper::actionUrl('cloudflare-stream/upload/upload'),
                'element' => $element,
            ]);
        }

        // Not added to stream
        if (!isset($value['meta'])) {
            return Craft::$app->getView()->renderTemplate('cloudflare-stream/added', [
                'name' => $this->handle,
                'value' => $value,
                'element' => $element,
            ]);
        }

        // Not ready to stream (i.e. still processing...)
        if (!$value['readyToStream']) {
            return Craft::$app->getView()->renderTemplate('cloudflare-stream/uploading', [
                'name' => $this->handle,
                'value' => $value,
                'element' => $element,
            ]);
        }

        // Ready to stream
        return Craft::$app->getView()->renderTemplate('cloudflare-stream/video', [
            'name' => $this->handle,
            'actionUrl' => UrlHelper::actionUrl('cloudflare-stream/delete/delete'),
            'element' => $element,
            'value' => $value
        ]);
    }

    public function getContentGqlType(): Type|array
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
