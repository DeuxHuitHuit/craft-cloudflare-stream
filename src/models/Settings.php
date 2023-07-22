<?php

namespace deuxhuithuit\cfstream\models;

use craft\base\Model;

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
}