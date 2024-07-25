<?php

namespace deuxhuithuit\cfstream\assetpreviews;

use craft\base\AssetPreviewHandlerInterface;
use craft\elements\Asset;

class AssetPreviewHandler implements AssetPreviewHandlerInterface
{
    /** @var Asset */
    private $asset;

    /** @var array */
    private $fieldData;

    public function __construct(Asset $asset, array $fieldData)
    {
        $this->asset = $asset;
        $this->fieldData = $fieldData;
    }

    public function getPreviewHtml(array $variables = []): string
    {
        return \Craft::$app->getView()->renderTemplate('cloudflare-stream/preview', array_merge($variables, [
            'value' => $this->fieldData,
            'element' => $this->asset,
        ]));
    }
}
