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
            throw new \Error('No API token found');
        }
        if (!$config->getAccountId()) {
            throw new \Error('No account ID found');
        }
        $this->config = $config;
    }

    public function uploadVideo(string $videoUrl, string $videoName)
    {
        $client = new GuzzleHttp\Client();
        $accountId = $this->config->getAccountId();
        $apiToken = $this->config->getApiToken();
        $url = $this->baseUrl . $accountId . '/stream/copy';
        $uploadRes = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
            ],
            'body' => json_encode(['url' => $videoUrl, 'meta' => ['name' => $videoName]]),
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

    public function getVideo(string $videoUid)
    {
        $client = new GuzzleHttp\Client();
        $accountId = $this->config->getAccountId();
        $apiToken = $this->config->getApiToken();
        $url = $this->baseUrl . $accountId . '/stream/' . $videoUid;
        $res = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
            ],
            'http_errors' => false,
        ]);
        $data = json_decode($res->getBody(), true);

        return $data['result'];
    }

    public function getDownloadUrl(string $videoUid)
    {
        $client = new GuzzleHttp\Client();
        $accountId = $this->config->getAccountId();
        $apiToken = $this->config->getApiToken();
        $url = $this->baseUrl . $accountId . '/stream/' . $videoUid . '/downloads';
        $res = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
            ],
            'http_errors' => false,
        ]);
        $data = json_decode($res->getBody(), true);

        return $data['result']['default']['url'];
    }

    public function deleteVideo(string $videoUid)
    {
        $client = new GuzzleHttp\Client();
        $accountId = $this->config->getAccountId();
        $apiToken = $this->config->getApiToken();
        $url = $this->baseUrl . $accountId . '/stream/' . $videoUid;
        $client->request('DELETE', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
            ],
            'http_errors' => false,
        ]);
    }
}
