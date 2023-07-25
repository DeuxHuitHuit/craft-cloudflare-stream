<?php

namespace deuxhuithuit\cfstream\assetpreviews;

class AssetPreviewHandler implements \craft\base\AssetPreviewHandlerInterface
{
    /** @var \craft\elements\Asset */
    private $asset;

    /** @var array */
    private $fieldData;

    public function __construct(\craft\elements\Asset $asset, array $fieldData)
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
