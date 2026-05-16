<?php

namespace App\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseRepository
{
    public string $model;
    protected array  $allowedFilters  = [];
    protected array  $allowedSorts    = ['-created_at', 'created_at'];
    protected array  $allowedIncludes = [];

    protected function query(): QueryBuilder
    {
        return QueryBuilder::for($this->model)
            ->allowedFilters(...$this->allowedFilters)
            ->allowedSorts(...$this->allowedSorts)
            ->allowedIncludes(...$this->allowedIncludes);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function findByUuid(string $uuid): Model
    {
        return $this->query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(array $data): Model
    {
        return $this->model::create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }

    public function rules(string $action, ?Model $model = null): array
    {
        return [];
    }

    public function validate(array $data, string $action, ?Model $model = null): array
    {
        $rules = $this->rules($action, $model);

        if ($rules === []) {
            return $data;
        }

        return validator($data, $rules)->validate();
    }
}
