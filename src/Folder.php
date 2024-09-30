<?php

namespace deuxhuithuit\cfstream;

use craft\elements\Asset;
use craft\helpers\App;

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

        // Start with the volume's path
        if ($fs->path) {
            $path = App::parseEnv($fs->path);
        }
        // or start with rootPath, if it exists?
        elseif (property_exists($fs, 'rootPath')) {
            $path = $fs->rootPath;
        }

        // Add the asset's folder path
        $folderPath = $asset->getFolder()->path;
        if ($folderPath) {
            $path .= '/' . $folderPath;
        }

        return $path;
    }
}
