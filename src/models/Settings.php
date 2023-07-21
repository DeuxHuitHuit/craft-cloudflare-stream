<?php

namespace deuxhuithuit\cfstream\models;

use craft\base\Model;

class Settings extends Model
{
    public $foo = 'defaultFooValue';
    public $bar = 'defaultBarValue';

    public function defineRules(): array
    {
        return [
            [['foo', 'bar'], 'required'],
            // ...
        ];
    }
}