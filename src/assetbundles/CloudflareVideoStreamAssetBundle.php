<?php

namespace deuxhuithuit\cfstream\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CloudflareVideoStreamAssetBundle extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = '@plugin/cfstream/resources';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/action-buttons.js',
        ];

        parent::init();
    }
}
