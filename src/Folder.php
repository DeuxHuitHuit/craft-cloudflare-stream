<?php

namespace deuxhuithuit\cfstream;

use craft\elements\Asset;

class Folder
{
    /**
     * Returns the full path of the asset's folder.
     *
     * @param Asset $asset
     */
    public static function getAssetFolderPath($asset): string
    {
        $fs = $asset->getVolume()->getFs();
        $path = '';
        
        if (property_exists($fs, 'rootPath')) {
            $path = $fs->rootPath;
        }
        
        $folderPath = $asset->getFolder()->path;
        if ($folderPath) {
            $path .= '/' . $folderPath;
        }

        return $path;
    }
}
