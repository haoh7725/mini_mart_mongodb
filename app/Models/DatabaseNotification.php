<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as BaseNotification;
use MongoDB\Laravel\Eloquent\DocumentModel;

class DatabaseNotification extends BaseNotification
{
    use DocumentModel;

    protected $connection = 'mongodb';

    protected $keyType = 'string';
}
