<?php

namespace deuxhuithuit\cfstream\client;

use deuxhuithuit\cfstream\CloudflareVideoStreamModule;
use GuzzleHttp;

class CloudflareVideoStreamClient
{
    public $baseUrl = 'https://api.cloudflare.com/client/v4/accounts/';
    public $accountId;
    public $apiToken;

    public function __construct(\deuxhuithuit\cfstream\models\Settings $config)
    {
        $this->accountId = $config->accountId;
        $this->apiToken = $config->apiToken;
    }

    public function uploadVideo(string $videoUrl, string $videoName)
    {
        $client = new GuzzleHttp\Client();
        $url = $this->baseUrl . $this->accountId . '/stream/copy';
        $uploadRes = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken
            ],
            'body' => json_encode(['url' => $videoUrl, 'meta' => ['name' => $videoName ]]),
            'http_errors' => false,
        ]);

        if ($uploadRes->getStatusCode() !== 200) {
            return null;
        }

        $data = json_decode($uploadRes->getBody(), true);
        $result = $data['result'];
        return $result;
    }

    public function getVideo(string $videoUid)
    {
        $client = new GuzzleHttp\Client();
        $url = $this->baseUrl . $this->accountId . '/stream/' . $videoUid;
        $res = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken
            ],
            'http_errors' => false
        ]);
        $data = json_decode($res->getBody(), true);
        return $data['result'];
    }

    public function getDownloadUrl(string $videoUid)
    {
        $client = new GuzzleHttp\Client();
        $url = $this->baseUrl . $this->accountId . '/stream/' . $videoUid . '/downloads';
        $res = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken
            ],
            'http_errors' => false,
        ]);
        $data = json_decode($res->getBody(), true);
        return $data['result']['default']['url'];
    }

    public function deleteVideo(string $videoUid)
    {
        $client = new GuzzleHttp\Client();
        $url = $this->baseUrl . $this->accountId . '/stream/' . $videoUid;
        $client->request('DELETE', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken
            ],
            'http_errors' => false
        ]);
    }
}
