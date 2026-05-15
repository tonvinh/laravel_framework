<?php

namespace App\Base\Traits;

use Illuminate\Support\Str;

trait HasUuidTrait
{
    protected static function bootHasUuidTrait(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
