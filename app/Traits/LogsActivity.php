<?php

namespace App\Traits;

use App\Services\ActivityLogger;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            ActivityLogger::logModelEvent('create', $model);
        });

        static::updated(function ($model) {
            ActivityLogger::logModelEvent('update', $model);
        });

        static::deleted(function ($model) {
            ActivityLogger::logModelEvent('delete', $model);
        });
    }
}
