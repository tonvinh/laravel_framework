<?php

namespace App\Http\Controllers\Api\V1;

use App\Base\BaseApiController;
use App\Http\Resources\DemoResource;
use App\Repositories\DemoRepository;

class DemoController extends BaseApiController
{
    protected string $repository = DemoRepository::class;
    protected string $resource   = DemoResource::class;
}
