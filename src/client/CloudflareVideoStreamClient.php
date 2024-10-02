<?php

namespace deuxhuithuit\cfstream\client;

use deuxhuithuit\cfstream\models\Settings;
use GuzzleHttp;

class CloudflareVideoStreamClient
{
    public $baseUrl = 'https://api.cloudflare.com/client/v4/accounts/';
    public $config;

    public function __construct(Settings $config)
    {
        if (!$config->getApiToken()) {
            throw new \Exception('No API token found');
        }
        if (!$config->getAccountId()) {
            throw new \Exception('No account ID found');
        }
        $this->config = $config;
    }

    public function createCfUrl(string $endpoint)
    {
        return $this->baseUrl . $this->config->getAccountId() . $endpoint;
    }

    public function createHttpHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->getApiToken(),
        ];
    }

    public function uploadVideoByUrl(string $videoUrl, string $videoName, ?string $videoTitle = null)
    {
        $client = new GuzzleHttp\Client();
        $uploadRes = $client->request('POST', $this->createCfUrl('/stream/copy'), [
            'headers' => $this->createHttpHeaders(),
            'body' => json_encode([
                'url' => $videoUrl,
                'meta' => [
                    'name' => $videoName,
                    'title' => $videoTitle,
                ],
            ]),
            'http_errors' => false,
        ]);

        if ($uploadRes->getStatusCode() !== 200) {
            return [
                'error' => 'Error uploading video',
                'message' => $uploadRes->getBody()->getContents(),
            ];
        }

        $data = json_decode($uploadRes->getBody(), true);

        return $data['result'];
    }

    public function uploadVideoByPath(string $videoPath, string $videoFilename)
    {
        $client = new GuzzleHttp\Client();
        $fullPath = $this->createSafeFullPath($videoPath, $videoFilename);

        // Guzzle might? close the file for us...
        $file = fopen($fullPath, 'r');
        if (!$file) {
            return [
                'error' => 'Error opening video file',
                'message' => "File '{$fullPath}' not found",
            ];
        }
        $uploadRes = $client->request('POST', $this->createCfUrl('/stream'), [
            'headers' => $this->createHttpHeaders(),
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $file,
                    'filename' => $videoFilename,
                ],
            ],
            'http_errors' => false,
        ]);

        if (\is_resource($file)) {
            fclose($file);
        }

        if ($uploadRes->getStatusCode() !== 200) {
            return [
                'error' => 'Error uploading video',
                'message' => $uploadRes->getBody()->getContents(),
            ];
        }

        $data = json_decode($uploadRes->getBody(), true);

        return $data['result'];
    }

    public function uploadVideoByTus(string $videoPath, string $videoFilename)
    {
        $client = new GuzzleHttp\Client();
        $fullPath = $this->createSafeFullPath($videoPath, $videoFilename);

        $file = \file_exists($fullPath);
        if (!$file) {
            return [
                'error' => 'Video file does not exist',
                'message' => "File '{$fullPath}' not found",
            ];
        }
        $uploadRes = $client->request('POST', $this->createCfUrl('/stream?direct_user=true'), [
            'headers' => \array_merge($this->createHttpHeaders(), [
                'Tus-Resumable' => '1.0.0',
                'Upload-Length' => (string) \filesize($fullPath),
                'Upload-Metadata' => 'name ' . \base64_encode($videoFilename),
            ]),
            'http_errors' => false,
        ]);

        if ($uploadRes->getStatusCode() !== 201) {
            return [
                'error' => 'Error creating TUS request',
                'message' => $uploadRes->getBody()->getContents(),
            ];
        }

        $headers = $uploadRes->getHeaders();
        $location = $headers['Location'][0];
        $uid = $headers['stream-media-id'][0];

        if (!$location) {
            return [
                'error' => 'Error getting TUS location',
                'message' => $uploadRes->getBody()->getContents(),
            ];
        }

        return [
            'readyToStream' => false,
            'uid' => $uid,
            'location' => $location,
            'fullPath' => $fullPath,
        ];
    }

    public function getVideo(string $videoUid)
    {
        $client = new GuzzleHttp\Client();
        $res = $client->request('GET', $this->createCfUrl('/stream/' . $videoUid), [
            'headers' => $this->createHttpHeaders(),
            'http_errors' => false,
        ]);
        $data = json_decode($res->getBody(), true);

        return $data['result'];
    }

    public function getDownloadUrl(string $videoUid)
    {
        $client = new GuzzleHttp\Client();
        $res = $client->request('POST', $this->createCfUrl('/stream/' . $videoUid . '/downloads'), [
            'headers' => $this->createHttpHeaders(),
            'http_errors' => false,
        ]);
        $data = json_decode($res->getBody(), true);

        return $data['result']['default']['url'];
    }

    public function deleteVideo(string $videoUid)
    {
        $client = new GuzzleHttp\Client();
        $res = $client->request('DELETE', $this->createCfUrl('/stream/' . $videoUid), [
            'headers' => $this->createHttpHeaders(),
            'http_errors' => false,
        ]);
        if ($res->getStatusCode() !== 200) {
            return [
                'error' => 'Error deleting video',
                'message' => $res->getBody()->getContents(),
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function updateThumbnail(string $videoUid, float $time, float $duration)
    {
        $client = new GuzzleHttp\Client();
        $res = $client->request('POST', $this->createCfUrl('/stream/' . $videoUid), [
            'headers' => $this->createHttpHeaders(),
            'json' => [
                'thumbnailTimestampPct' => $time / $duration,
            ],
            'http_errors' => false,
        ]);
        if ($res->getStatusCode() !== 200) {
            return [
                'error' => 'Error updating thumbnail',
                'message' => $res->getBody()->getContents(),
            ];
        }

        return [
            'success' => true,
        ];
    }

    private function createSafeFullPath(string $videoPath, string $videoFilename)
    {
        return rtrim($videoPath, '/') . '/' . $videoFilename;
    }
}
