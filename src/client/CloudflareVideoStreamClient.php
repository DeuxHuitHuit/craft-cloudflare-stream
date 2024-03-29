<?php

namespace deuxhuithuit\cfstream\client;

use GuzzleHttp;

class CloudflareVideoStreamClient
{
    public $baseUrl = 'https://api.cloudflare.com/client/v4/accounts/';
    public $config;

    public function __construct(\deuxhuithuit\cfstream\models\Settings $config)
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

    public function uploadVideoByUrl(string $videoUrl, string $videoName, string $videoTitle = null)
    {
        $client = new GuzzleHttp\Client();
        $uploadRes = $client->request('POST', $this->createCfUrl('/stream/copy'), [
            'headers' => $this->createHttpHeaders(),
            'body' => json_encode([
                'url' => $videoUrl,
                'meta' => [
                    'name' => $videoName,
                    'title' => $videoTitle,
                ]
            ]),
            'http_errors' => false,
        ]);

        if ($uploadRes->getStatusCode() !== 200) {
            return [
                'error' => 'Error uploading video',
                'message' => $uploadRes->getBody(),
            ];
        }

        $data = json_decode($uploadRes->getBody(), true);

        return $data['result'];
    }

    public function uploadVideoByPath(string $videoPath, string $videoFilename)
    {
        $client = new GuzzleHttp\Client();
        $fullPath = rtrim($videoPath, '/') . '/' . $videoFilename;
        // Guzzle might close the file for us...
        $file = fopen($fullPath, 'r');
        if (!$file) {
            return [
                'error' => 'Error opening video file',
                'message' => "File '$fullPath' not found",
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
                'message' => $uploadRes->getBody(),
            ];
        }

        $data = json_decode($uploadRes->getBody(), true);

        return $data['result'];
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
                'message' => $res->getBody(),
            ];
        }
        return [
            'success' => true,
        ];
    }
}
