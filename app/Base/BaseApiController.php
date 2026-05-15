<?php

namespace App\Base;

use App\Base\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    use ApiResponseTrait;

    protected string $repository;
    protected string $resource;
    protected BaseRepository $repo;

    public function __construct()
    {
        $this->repo = app($this->repository);
    }

    public function index(): JsonResponse
    {
        return $this->success(
            $this->resource::collection($this->repo->paginate())
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $model = $this->repo->findByUuid($uuid);
        $this->authorize('view', $model);

        return $this->success($this->resource::make($model));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', $this->repo->model);

        $data  = $this->repo->validate($request->all(), 'create');
        $model = $this->repo->create($data);

        return $this->created($this->resource::make($model));
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $model = $this->repo->findByUuid($uuid);
        $this->authorize('update', $model);

        $data  = $this->repo->validate($request->all(), 'update', $model);
        $model = $this->repo->update($model, $data);

        return $this->success($this->resource::make($model));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $model = $this->repo->findByUuid($uuid);
        $this->authorize('delete', $model);

        $this->repo->delete($model);

        return $this->noContent();
    }
}
