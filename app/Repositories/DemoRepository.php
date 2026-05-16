<?php

namespace App\Repositories;

use App\Base\BaseRepository;
use App\Models\Demo;
use Illuminate\Database\Eloquent\Model;

class DemoRepository extends BaseRepository
{
    public string $model = Demo::class;

    protected array $allowedFilters  = ['title', 'status'];
    protected array $allowedSorts    = ['title', '-created_at', 'created_at'];
    protected array $allowedIncludes = ['user'];

    public function rules(string $action, ?Model $model = null): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'status'  => ['sometimes', 'in:draft,published,archived'],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }
}
