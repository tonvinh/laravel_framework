# ARCHITECTURE.md
# Laravel API Platform — Base Class Inheritance Pattern
# Version: Laravel 13 / PHP 8.3 / May 2026

> **Dành cho AI**: Đây là source of truth. Khi sinh code, tuân thủ tuyệt đối.
> Nguyên tắc cốt lõi: **viết Base class 1 lần, child class chỉ khai báo phần đặc biệt**.
> Không dùng Form, không có Blade view — thuần API JSON.

---

## 1. STACK & VERSIONS

| Thành phần  | Package                            | Version  | Ghi chú                        |
|-------------|------------------------------------|----------|--------------------------------|
| Framework   | laravel/framework                  | ^13.0    | Released March 2026            |
| PHP         | —                                  | ^8.5     | Required by Laravel 13         |
| Database    | MySQL                              | 9.7+     |                                |
| Cache/Queue | Redis                              | 7+       |                                |
| Auth        | laravel/sanctum                    | ^4.0     | Token-based, không session     |
| Permission  | spatie/laravel-permission          | ^7.0     | RBAC                           |
| DTO         | spatie/laravel-data                | ^4.0     | latest: 4.23.0                 |
| Query       | spatie/laravel-query-builder       | ^7.0     | latest: 7.2.1, requires L12+  |
| Test        | pestphp/pest                       | ^4.0     | requires PHP 8.5               |
| Test        | pestphp/pest-plugin-laravel        | ^4.0     | latest: v4.1.0                 |
| Style       | laravel/pint                       | ^1.0     | PSR-12                         |
| IDE Helper  | barryvdh/laravel-ide-helper        | ^3.0     | dev only                       |

### `composer.json` — copy để dùng ngay

```json
{
    "require": {
        "php": "^8.5",
        "laravel/framework": "^13.0",
        "laravel/sanctum": "^4.3",
        "spatie/laravel-permission": "^7.4",
        "spatie/laravel-data": "^4.0",
        "spatie/laravel-query-builder": "^7.0"
    },
    "require-dev": {
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "laravel/pint": "^1.0",
        "barryvdh/laravel-ide-helper": "^3.0"
    }
}
```

---

## 2. TRIẾT LÝ KIẾN TRÚC

```
Viết 1 lần ở Base  →  Child kế thừa, chạy ngay
                   →  Chỉ override khi có logic đặc biệt
```

**Ba lớp Base (viết 1 lần duy nhất):**

| Class                | Trách nhiệm                                     |
|----------------------|-------------------------------------------------|
| `BaseModel`          | uuid, softDelete, scopes, helpers dùng chung    |
| `BaseRepository`     | CRUD + filter + paginate + validate rules       |
| `BaseApiController`  | 5 actions chuẩn: index/show/store/update/destroy|

**Thêm resource mới — chỉ cần 4 file, mỗi file vài dòng:**

```
Migration   → định nghĩa bảng
Model       (3-5 dòng)  extends BaseModel        → $fillable, $casts, relationships
Repository  (5-10 dòng) extends BaseRepository   → $model, filters, rules()
Controller  (3 dòng)    extends BaseApiController → $repository, $resource
Resource    (1 method)  extends JsonResource      → transform output
Route       (1 dòng)    Route::apiResource(...)
```

---

## 3. DIRECTORY STRUCTURE

```
app/
├── Base/
│   ├── BaseModel.php              ← viết 1 lần: uuid, softDelete, scopes
│   ├── BaseApiController.php      ← viết 1 lần: index/show/store/update/destroy
│   ├── BaseRepository.php         ← viết 1 lần: CRUD/filter/paginate/validate
│   └── Traits/
│       ├── ApiResponseTrait.php   ← success(), created(), error(), noContent()
│       └── HasUuidTrait.php       ← auto-generate uuid khi tạo record
│
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/                ← child controllers (3 dòng/file)
│   │           ├── AuthController.php
│   │           └── {Resource}Controller.php
│   └── Resources/                 ← JSON transform
│       └── {Resource}Resource.php
│
├── Models/                        ← child models (3-5 dòng/file)
│   └── {Resource}.php
│
├── Repositories/                  ← child repositories (5-10 dòng/file)
│   └── {Resource}Repository.php
│
├── Data/                          ← spatie/laravel-data DTOs (khi cần)
├── Enums/                         ← PHP 8.1+ backed enums
├── Events/                        ← domain events
├── Listeners/                     ← event listeners (async qua Queue)
└── Jobs/                          ← queued jobs

routes/
└── api.php                        ← tất cả routes, 1 dòng/resource

bootstrap/
└── app.php                        ← đăng ký routes, exception handlers

database/
├── migrations/
├── factories/
└── seeders/

tests/
├── Pest.php                       ← global setup, helpers
└── Feature/
    └── Api/
        └── V1/
            └── {Resource}Test.php
```

---

## 4. BASE CLASSES — Viết 1 lần, dùng mãi mãi

### 4.1 `App\Base\Traits\HasUuidTrait`

```php
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

    // Dùng uuid làm route key thay vì id
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
```

### 4.2 `App\Base\Traits\ApiResponseTrait`

```php
<?php

namespace App\Base\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseTrait
{
    protected function success(mixed $data, int $status = 200): JsonResponse
    {
        $payload = match (true) {
            $data instanceof ResourceCollection => $data->response()->getData(assoc: true),
            $data instanceof JsonResource       => $data->response()->getData(assoc: true),
            default                             => ['data' => $data],
        };

        return response()->json($payload, $status);
    }

    protected function created(mixed $data): JsonResponse
    {
        return $this->success($data, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
```

### 4.3 `App\Base\BaseModel`

```php
<?php

namespace App\Base;

use App\Base\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseModel extends Model
{
    use HasUuidTrait;
    use SoftDeletes;

    // Child bắt buộc khai báo: protected $fillable = [...]
    // Child khai báo thêm:     protected $casts    = [...]

    protected $hidden = ['id', 'deleted_at'];

    // ── Scopes dùng chung ──────────────────────────────────────────
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term, array $fields = ['name']): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term, $fields): void {
            foreach ($fields as $field) {
                $q->orWhere($field, 'LIKE', "%{$term}%");
            }
        });
    }

    // ── Helpers ────────────────────────────────────────────────────
    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}
```

### 4.4 `App\Base\BaseRepository`

```php
<?php

namespace App\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseRepository
{
    // ── Child khai báo bắt buộc ────────────────────────────────────
    // protected string $model          = Product::class;
    // protected array  $allowedFilters  = ['name', 'status'];
    // protected array  $allowedSorts    = ['name', '-created_at'];
    // protected array  $allowedIncludes = ['category'];
    //
    // Child override rules() để khai báo validation rules
    // Child thêm method đặc thù khi cần query riêng

    protected string $model;
    protected array  $allowedFilters  = [];
    protected array  $allowedSorts    = ['-created_at', 'created_at'];
    protected array  $allowedIncludes = [];

    // ── Query ──────────────────────────────────────────────────────
    protected function query(): QueryBuilder
    {
        return QueryBuilder::for($this->model)
            ->allowedFilters($this->allowedFilters)
            ->allowedSorts($this->allowedSorts)
            ->allowedIncludes($this->allowedIncludes);
    }

    // ── Read ───────────────────────────────────────────────────────
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

    // ── Write ──────────────────────────────────────────────────────
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

    // ── Validate ───────────────────────────────────────────────────
    // Child override để định nghĩa rules theo resource
    public function rules(string $action, ?Model $model = null): array
    {
        return [];
    }

    // 'create' | 'update' — dùng $action để phân biệt required vs sometimes
    public function validate(array $data, string $action, ?Model $model = null): array
    {
        $rules = $this->rules($action, $model);

        if ($rules === []) {
            return $data;
        }

        return validator($data, $rules)->validate();
    }
}
```

### 4.5 `App\Base\BaseApiController`

```php
<?php

namespace App\Base;

use App\Base\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    use ApiResponseTrait;

    // ── Child khai báo bắt buộc ────────────────────────────────────
    // protected string $repository = ProductRepository::class;
    // protected string $resource   = ProductResource::class;
    //
    // Child override action nếu cần logic đặc biệt

    protected string $repository;
    protected string $resource;
    protected BaseRepository $repo;

    public function __construct()
    {
        $this->repo = app($this->repository);
    }

    // ── CRUD — 5 actions chuẩn ────────────────────────────────────
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
```

---

## 5. CHILD CLASSES — Kế thừa là chạy ngay

### 5.1 Model — chỉ khai báo đặc thù

```php
<?php

namespace App\Models;

use App\Base\BaseModel;
use App\Enums\ProductStatusEnum;

class Product extends BaseModel
{
    protected $fillable = [
        'name',
        'price',
        'status',
        'description',
        'category_id',
    ];

    protected $casts = [
        'price'  => 'decimal:2',
        'status' => ProductStatusEnum::class,
    ];

    // Chỉ thêm relationships và scopes đặc thù
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

### 5.2 Enum — PHP 8.1+ backed enum

```php
<?php

namespace App\Enums;

enum ProductStatusEnum: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Draft    = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Đang bán',
            self::Inactive => 'Ngừng bán',
            self::Draft    => 'Nháp',
        };
    }
}
```

### 5.3 Repository — khai báo model + filters + rules

```php
<?php

namespace App\Repositories;

use App\Base\BaseRepository;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends BaseRepository
{
    protected string $model = Product::class;

    protected array $allowedFilters  = ['name', 'status', 'category_id'];
    protected array $allowedSorts    = ['name', 'price', '-created_at', 'created_at'];
    protected array $allowedIncludes = ['category'];

    public function rules(string $action, ?Model $model = null): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'status'      => ['required', 'in:active,inactive,draft'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
        ];
    }

    // Thêm method đặc thù khi thực sự cần — ví dụ:
    // public function featured(): Collection
    // {
    //     return $this->query()->where('is_featured', true)->get();
    // }
}
```

### 5.4 Controller — 2 dòng là đủ

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Base\BaseApiController;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductRepository;

class ProductController extends BaseApiController
{
    protected string $repository = ProductRepository::class;
    protected string $resource   = ProductResource::class;

    // index/show/store/update/destroy đã có từ BaseApiController
    // Chỉ thêm method khi có action ngoài CRUD chuẩn, ví dụ:
    //
    // public function featured(): JsonResponse
    // {
    //     return $this->success(
    //         ProductResource::collection($this->repo->featured())
    //     );
    // }
}
```

### 5.5 Resource — transform output

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'price'       => $this->price,
            'status'      => $this->status,
            'description' => $this->description,
            // Chỉ include khi được ?include=category
            'category'    => CategoryResource::make($this->whenLoaded('category')),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
```

### 5.6 Migration — cấu trúc chuẩn

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();              // public identifier — không expose id
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('status')->default('active');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();                       // audit trail mặc định
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

---

## 6. ROUTES — Khai báo đầy đủ

### `routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1;

/*
|--------------------------------------------------------------------------
| Auth — public, không cần token
|--------------------------------------------------------------------------
*/
Route::prefix('v1/auth')->group(function (): void {
    Route::post('login',    [V1\AuthController::class, 'login']);
    Route::post('register', [V1\AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout',  [V1\AuthController::class, 'logout']);
        Route::get('me',       [V1\AuthController::class, 'me']);
        Route::post('refresh', [V1\AuthController::class, 'refresh']);
    });
});

/*
|--------------------------------------------------------------------------
| API v1 — yêu cầu auth:sanctum
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {

    // ── CRUD chuẩn — 1 dòng = 5 routes ──────────────────────────
    Route::apiResource('products',   V1\ProductController::class);
    Route::apiResource('categories', V1\CategoryController::class);
    Route::apiResource('orders',     V1\OrderController::class);
    Route::apiResource('users',      V1\UserController::class);

    // ── Nested resource — quan hệ cha-con ────────────────────────
    Route::apiResource('orders.items', V1\OrderItemController::class)
         ->shallow();
    // shallow() sinh ra:
    // GET  /v1/orders/{order}/items     → index
    // POST /v1/orders/{order}/items     → store
    // GET  /v1/items/{item}             → show   (không lồng sâu)
    // PUT  /v1/items/{item}             → update (không lồng sâu)
    // DEL  /v1/items/{item}             → destroy

    // ── Action đặc biệt ngoài CRUD — dùng POST ───────────────────
    Route::post('orders/{uuid}/approve',   [V1\OrderController::class,   'approve']);
    Route::post('orders/{uuid}/cancel',    [V1\OrderController::class,   'cancel']);
    Route::post('products/{uuid}/feature', [V1\ProductController::class, 'feature']);

    // ── Giới hạn actions ─────────────────────────────────────────
    Route::apiResource('reports', V1\ReportController::class)
         ->only(['index', 'show']);          // read-only

    Route::apiResource('settings', V1\SettingController::class)
         ->except(['store', 'destroy']);     // không cho tạo mới hay xoá

    // ── Group theo role ───────────────────────────────────────────
    Route::middleware('role:admin')->group(function (): void {
        Route::apiResource('roles', V1\RoleController::class);
    });

});
```

### Các pattern route hay dùng

```php
// 1. CRUD chuẩn — 5 routes tự động
Route::apiResource('products', V1\ProductController::class);
// GET    /api/v1/products           → index
// POST   /api/v1/products           → store
// GET    /api/v1/products/{uuid}    → show
// PUT    /api/v1/products/{uuid}    → update
// DELETE /api/v1/products/{uuid}    → destroy

// 2. Chỉ một số actions
Route::apiResource('products', V1\ProductController::class)
     ->only(['index', 'show']);

Route::apiResource('products', V1\ProductController::class)
     ->except(['destroy']);

// 3. Đặt tên parameter rõ nghĩa
Route::apiResource('products', V1\ProductController::class)
     ->parameters(['products' => 'uuid']);
// → /api/v1/products/{uuid}

// 4. Nested resource
Route::apiResource('products.reviews', V1\ReviewController::class);
// GET  /api/v1/products/{product}/reviews
// POST /api/v1/products/{product}/reviews
// ...

// 5. Nested shallow — tránh URL lồng quá sâu
Route::apiResource('products.reviews', V1\ReviewController::class)
     ->shallow();

// 6. Rate limiting
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::apiResource('products', V1\ProductController::class);
});

// 7. Versioning — v2 trỏ class khác
Route::prefix('v2')->middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('products', \App\Http\Controllers\Api\V2\ProductController::class);
});
```

### Đăng ký route trong `bootstrap/app.php`

```php
// bootstrap/app.php
->withRouting(
    api: __DIR__.'/../routes/api.php',
    apiPrefix: 'api',
    health: '/up',
)
->withExceptions(function (Exceptions $exceptions): void {

    // Tất cả exception handling cho API tập trung ở đây
    $exceptions->render(function (ModelNotFoundException $e, Request $request) {
        if ($request->is('api/*')) {
            return response()->json(['message' => 'Resource not found.'], 404);
        }
    });

    $exceptions->render(function (AuthorizationException $e, Request $request) {
        if ($request->is('api/*')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }
    });

})
```

### Kiểm tra routes

```bash
php artisan route:list --path=api          # tất cả routes API
php artisan route:list --path=api/v1       # chỉ v1
php artisan route:list --name=products     # routes tên chứa "products"
```

---

## 7. API CONVENTIONS

### Response format — nhất quán tuyệt đối

```json
// Single resource
{ "data": { "uuid": "...", "name": "..." } }

// Paginated list
{
  "data": [...],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta":  { "current_page": 1, "last_page": 5, "per_page": 15, "total": 72 }
}

// Validation error
{
  "message": "The given data was invalid.",
  "errors": { "name": ["The name field is required."] }
}

// Generic error
{ "message": "This action is unauthorized." }
```

### HTTP Status Codes

| Tình huống          | Code |
|---------------------|------|
| GET thành công      | 200  |
| POST tạo mới        | 201  |
| DELETE / no content | 204  |
| Validation error    | 422  |
| Unauthenticated     | 401  |
| Unauthorized        | 403  |
| Not found           | 404  |
| Server error        | 500  |

### Query Params — spatie/laravel-query-builder v7

```
# Filter
GET /api/v1/products?filter[status]=active
GET /api/v1/products?filter[category_id]=3

# Sort (prefix - = DESC)
GET /api/v1/products?sort=-created_at
GET /api/v1/products?sort=name,-price

# Include relationships
GET /api/v1/products?include=category
GET /api/v1/products?include=category,reviews

# Pagination
GET /api/v1/products?page[number]=2&page[size]=20
```

---

## 8. DATABASE CONVENTIONS

### Migration chuẩn

```php
Schema::create('products', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();           // public — dùng cho API
    $table->string('name');
    $table->decimal('price', 10, 2);
    $table->string('status')->default('active');
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();                    // luôn bật — hỗ trợ audit trail
});
```

### Naming conventions

| Thứ                | Convention                   | Ví dụ                          |
|--------------------|------------------------------|--------------------------------|
| Tên bảng           | `snake_case`, số nhiều       | `products`, `order_items`      |
| Foreign key        | `{table_singular}_id`        | `product_id`, `category_id`    |
| Enum column        | `string` + PHP Enum cast     | không dùng MySQL ENUM          |
| Timestamp tùy chỉnh| `{action}_at`                | `approved_at`, `published_at`  |

### Model conventions

```php
class Product extends BaseModel
{
    // Luôn khai báo $fillable — không dùng $guarded = []
    protected $fillable = ['name', 'price', 'status'];

    // Dùng $casts cho type conversion — không dùng accessor cho việc này
    protected $casts = [
        'status' => ProductStatusEnum::class,
        'price'  => 'decimal:2',
        'meta'   => 'array',
    ];
}
```

---

## 9. AUTHENTICATION — Sanctum + Spatie v7

### Auth flow

```
POST /api/v1/auth/login
→ trả về { token: "..." }
→ client gửi kèm Header: Authorization: Bearer {token}
→ middleware auth:sanctum xác thực mỗi request
```

### AuthController (không kế thừa BaseApiController)

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Base\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request): JsonResponse
    {
        $data = validator($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ])->validate();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $this->success([
            'token' => $user->createToken('api')->plainTextToken,
            'user'  => UserResource::make($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(UserResource::make($request->user()));
    }
}
```

### Permission naming

```
{resource}.view      {resource}.create
{resource}.update    {resource}.delete
{resource}.{action}  ← custom action
```

Ví dụ: `products.view`, `products.create`, `orders.approve`, `reports.export`

---

## 10. TESTING — Pest PHP v4

### `tests/Pest.php` — setup global

```php
<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class)->in('Feature');

// Helper dùng chung cho tất cả tests
function loginAs(?User $user = null): User
{
    $user ??= User::factory()->create();
    Sanctum::actingAs($user);
    return $user;
}

function loginAsAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');
    Sanctum::actingAs($user);
    return $user;
}
```

### Feature test mẫu

```php
<?php

// tests/Feature/Api/V1/ProductTest.php

use App\Models\{Category, Product, User};

beforeEach(function (): void {
    loginAs();  // dùng helper từ Pest.php
});

it('returns paginated products', function (): void {
    Product::factory(5)->create();

    getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('creates a product', function (): void {
    $category = Category::factory()->create();

    postJson('/api/v1/products', [
        'name'        => 'Test Product',
        'price'       => 99.99,
        'status'      => 'active',
        'category_id' => $category->id,
    ])->assertCreated()
      ->assertJsonPath('data.name', 'Test Product');

    expect(Product::where('name', 'Test Product')->exists())->toBeTrue();
});

it('shows a single product', function (): void {
    $product = Product::factory()->create();

    getJson("/api/v1/products/{$product->uuid}")
        ->assertOk()
        ->assertJsonPath('data.uuid', $product->uuid);
});

it('updates a product', function (): void {
    $product = Product::factory()->create();

    putJson("/api/v1/products/{$product->uuid}", [
        'name'  => 'Updated Name',
        'price' => 150.00,
    ])->assertOk()
      ->assertJsonPath('data.name', 'Updated Name');
});

it('deletes a product', function (): void {
    $product = Product::factory()->create();

    deleteJson("/api/v1/products/{$product->uuid}")
        ->assertNoContent();

    expect(Product::find($product->id))->toBeNull();
});

// Dataset — test nhiều field validation cùng lúc
it('validates required fields', function (string $field): void {
    $data = [
        'name'        => 'Product',
        'price'       => 99.99,
        'status'      => 'active',
        'category_id' => Category::factory()->create()->id,
    ];

    unset($data[$field]);

    postJson('/api/v1/products', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);

})->with(['name', 'price', 'status', 'category_id']);
```

---

## 11. VIBE CODING RULES — Quy tắc cho AI

> Khi nhận yêu cầu sinh code cho project này, AI **bắt buộc** tuân theo:

1. **Thêm resource mới** → tạo đúng 6 file: Migration + Enum (nếu có) + Model + Repository + Controller + Resource
2. **Model** → chỉ có `$fillable`, `$casts`, relationships. Không có gì khác
3. **Repository** → khai báo `$model`, `$allowedFilters`, `$allowedSorts`, `$allowedIncludes`, `rules()`. Thêm method chỉ khi cần query đặc thù
4. **Controller** → chỉ khai báo `$repository` và `$resource`. Thêm method chỉ khi có action ngoài CRUD
5. **Route** → `Route::apiResource()` trong nhóm `auth:sanctum`. Thêm route riêng cho custom action
6. **Validation** → nằm trong `Repository::rules()` — không tạo FormRequest riêng
7. **Logic** → nằm trong Repository method hoặc Controller method — không dùng Action class
8. **Không có** Blade view, web routes — thuần JSON API
9. **Route key** → luôn dùng `uuid`, ẩn `id` trong Resource output
10. **Test** → Pest v4, viết đủ 5 cases: index + show + store + update + destroy + dataset validation
11. **PHP style** → dùng PHP 8.5 features: typed properties, readonly, enums, match, named args, arrow functions
12. **Closure** → luôn có return type: `function (): void`, `function (): JsonResponse`

### Prompt mẫu — copy và dùng ngay

```
Dựa trên ARCHITECTURE.md (Laravel 13, PHP 8.5), thêm resource "Order" vào API platform.

Tạo đủ:
- Migration (bảng orders với các field phù hợp)
- Enum OrderStatusEnum
- Model Order (extends BaseModel)
- OrderRepository (extends BaseRepository, với rules + custom query nếu cần)
- OrderController (extends BaseApiController)
- OrderResource
- Route trong api.php (trong group auth:sanctum)
- Pest test: index, show, store, update, destroy, dataset validation

Tuân thủ tất cả conventions trong ARCHITECTURE.md.
```
