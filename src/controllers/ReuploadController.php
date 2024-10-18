<?php

namespace deuxhuithuit\cfstream\controllers;

use craft\console\Controller;
use craft\elements\Asset;
use craft\helpers\Console;
use deuxhuithuit\cfstream\fields\CloudflareVideoStreamField;
use deuxhuithuit\cfstream\Folder;
use deuxhuithuit\cfstream\jobs\UploadVideoJob;
use yii\console\ExitCode;

class ReuploadController extends Controller
{
    public $defaultAction = 'reupload';

    public function actionReupload(): int
    {
        $volumes = \Craft::$app->getVolumes()->getAllVolumes();
        $volumeCount = is_array($volumes) ? count($volumes) : 0;

        $this->stdout("Found {$volumeCount} volumes", Console::FG_GREEN);
        $this->stdout(PHP_EOL);

        $uploadCount = 0;
        foreach ($volumes as $volume) {
            $this->stdout("Volume: {$volume->name}");
            $this->stdout(PHP_EOL);

            $assets = Asset::find()->volumeId($volume->id)->all();

            /** @var Asset $asset */
            foreach ($assets as $asset) {

                $field = CloudflareVideoStreamField::findStreamingFieldForAsset($asset);
                if (!$field) {
                    continue;
                }

                $this->stdout(
                    "  Asset {$asset->id} - {$asset->filename} with field `{$field->handle}` will be reuploaded"
                );

                $uploadJob = new UploadVideoJob([
                    'fieldHandle' => $field->handle,
                    'elementId' => $asset->id,
                    'videoUrl' => $asset->getUrl(),
                    'videoName' => $asset->filename,
                    'videoPath' => Folder::getAssetFolderPath($asset),
                    'videoTitle' => $asset->title,
                ]);
                \Craft::$app->getQueue()->push($uploadJob);
                ++$uploadCount;
            }
        }

        $this->stdout("Created {$uploadCount} new upload jobs!", Console::FG_GREEN);
        $this->stdout(PHP_EOL);

        return ExitCode::OK;
    }
}
