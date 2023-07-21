<?php

namespace deuxhuithuit\cfstream\fields;

use deuxhuithuit\cfstream\assetbundles\CloudflareVideoStreamAssetBundle;
use deuxhuithuit\cfstream\graphql\types\CloudflareVideoStreamType;
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
        if (!$value) {
            return Craft::$app->getView()->renderTemplate('cfstream/upload', [
                'name' => $this->handle,
                'actionUrl' => UrlHelper::actionUrl('cfstream/upload/upload'),
                'element' => $element
            ]);
        }
        if (!$value['readyToStream']) {
            return Craft::$app->getView()->renderTemplate('cfstream/uploading', [
                'value' => $value,
            ]);
        }
        return Craft::$app->getView()->renderTemplate('cfstream/video', [
            'name' => $this->handle,
            'actionUrl' => UrlHelper::actionUrl('cfstream/delete/delete'),
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
