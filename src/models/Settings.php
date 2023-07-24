<?php

namespace deuxhuithuit\cfstream\models;

use craft\base\Model;
use craft\helpers\App;

class Settings extends Model
{
    public $accountId = '';
    public $apiToken = '';

    public function defineRules(): array
    {
        return [
            [['accountId', 'apiToken'], 'required'],
        ];
    }

    public function getAccountId(): string
    {
        return App::parseEnv($this->accountId);
    }

    public function getApiToken(): string
    {
        return App::parseEnv($this->apiToken);
    }
}
