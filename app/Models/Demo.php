<?php

namespace App\Models;

use App\Base\BaseModel;
use App\Enums\DemoStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Demo extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'status',
        'user_id',
    ];

    protected $casts = [
        'status' => DemoStatusEnum::class,
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
