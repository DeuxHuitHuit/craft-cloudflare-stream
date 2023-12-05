<?php

namespace deuxhuithuit\cfstream;

class Folder
{
    /**
     * Returns the full path of the asset's folder
     * @param \craft\elements\Asset $asset
     */
    public static function getAssetFolderPath($asset): string
    {
        $fs = $asset->getVolume()->getFs();
        $path = '';
        
        if (property_exists($fs, 'rootPath')) {
            $path = $asset->getVolume()->getFs()->rootPath;
        }
        
        $folderPath = $asset->getFolder()->path;
        if ($folderPath) {
            $path .= '/' . $folderPath;
        }
        return $path;
    }
}
