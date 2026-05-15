# Laravel API Platform

Nền tảng REST API thuần JSON, xây dựng trên **Laravel 13 / PHP 8.5**, theo mô hình **Base Class Inheritance** — viết base class một lần, child class chỉ khai báo phần đặc thù.

## Stack

| Thành phần | Package | Version |
|------------|---------|---------|
| Framework | `laravel/framework` | ^13.0 |
| PHP | — | ^8.5 |
| Auth | `laravel/sanctum` | ^4.3 |
| RBAC | `spatie/laravel-permission` | ^7.4 |
| DTO | `spatie/laravel-data` | ^4.0 |
| Query | `spatie/laravel-query-builder` | ^7.0 |
| Test | `pestphp/pest` | ^4.0 |
| Style | `laravel/pint` | ^1.0 |

## Cài đặt

```bash
git clone <repo-url>
cd <project>

composer install

cp .env.example .env
php artisan key:generate

# Cấu hình DB trong .env, sau đó:
php artisan migrate

php artisan serve
```

## Kiến trúc

### Nguyên tắc cốt lõi

```
Viết 1 lần ở Base  →  Child kế thừa, chạy ngay
                   →  Chỉ override khi có logic đặc biệt
```

Ba lớp base (không bao giờ sửa trực tiếp):

| Class | Trách nhiệm |
|-------|-------------|
| `BaseModel` | uuid, softDeletes, scopeActive, scopeSearch |
| `BaseRepository` | CRUD, paginate, filter, validate |
| `BaseApiController` | 5 actions: index / show / store / update / destroy |

### Thêm resource mới — 6 file, mỗi file vài dòng

```
Migration      →  định nghĩa bảng
Enum           →  (nếu có status) PHP backed enum trong app/Enums/
Model          →  extends BaseModel  — chỉ $fillable, $casts, relationships
Repository     →  extends BaseRepository  — $model, filters, rules()
Controller     →  extends BaseApiController  — $repository, $resource
Resource       →  extends JsonResource  — toArray(), expose uuid không expose id
Route          →  Route::apiResource(...) trong group auth:sanctum
```

### Ví dụ nhanh — tạo resource `Product`

```php
// ProductController.php — 2 dòng là đủ
class ProductController extends BaseApiController
{
    protected string $repository = ProductRepository::class;
    protected string $resource   = ProductResource::class;
}

// ProductRepository.php — khai báo model + filter + rules
class ProductRepository extends BaseRepository
{
    protected string $model          = Product::class;
    protected array  $allowedFilters = ['name', 'status'];
    protected array  $allowedSorts   = ['name', '-created_at'];

    public function rules(string $action, ?Model $model = null): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}

// routes/api.php — 1 dòng = 5 routes
Route::apiResource('products', V1\ProductController::class);
```

## API

### Authentication

```http
POST /api/v1/auth/login
POST /api/v1/auth/register
POST /api/v1/auth/logout    (Bearer token)
GET  /api/v1/auth/me        (Bearer token)
```

Mọi request sau login phải gửi kèm header:

```
Authorization: Bearer {token}
```

### Query params

```
# Filter
GET /api/v1/products?filter[status]=active

# Sort (prefix - = DESC)
GET /api/v1/products?sort=-created_at

# Include relationships
GET /api/v1/products?include=category

# Pagination
GET /api/v1/products?page[number]=2&page[size]=20
```

### Response format

```json
// Single resource
{ "data": { "uuid": "...", "name": "..." } }

// Paginated list
{
  "data": [...],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta":  { "current_page": 1, "last_page": 5, "per_page": 15, "total": 72 }
}

// Error
{ "message": "This action is unauthorized." }

// Validation error
{
  "message": "The given data was invalid.",
  "errors": { "name": ["The name field is required."] }
}
```

### HTTP Status Codes

| Tình huống | Code |
|------------|------|
| GET thành công | 200 |
| POST tạo mới | 201 |
| DELETE / no content | 204 |
| Validation error | 422 |
| Unauthenticated | 401 |
| Unauthorized | 403 |
| Not found | 404 |

## Lệnh thường dùng

```bash
# Dev
php artisan serve
php artisan route:list --path=api

# Test
./vendor/bin/pest
./vendor/bin/pest tests/Feature/Api/V1/ProductTest.php
./vendor/bin/pest --filter "creates a product"

# Code style
./vendor/bin/pint
./vendor/bin/pint --test

# DB
php artisan migrate
php artisan migrate:fresh --seed
```

## Testing

Mỗi resource cần 5 test cases: `index`, `show`, `store`, `update`, `destroy` + dataset validation.

```php
// tests/Feature/Api/V1/ProductTest.php
beforeEach(function (): void {
    loginAs();  // helper từ tests/Pest.php
});

it('returns paginated products', function (): void {
    Product::factory(5)->create();

    getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});
```

## Cấu trúc thư mục

```
app/
├── Base/                   ← base classes, không sửa trực tiếp
│   ├── BaseModel.php
│   ├── BaseRepository.php
│   ├── BaseApiController.php
│   └── Traits/
│       ├── HasUuidTrait.php
│       └── ApiResponseTrait.php
├── Http/
│   ├── Controllers/Api/V1/ ← child controllers
│   └── Resources/          ← JSON transform
├── Models/                 ← child models
├── Repositories/           ← child repositories
├── Enums/                  ← PHP 8.1+ backed enums
├── Data/                   ← Spatie DTO
├── Events/
├── Listeners/
└── Jobs/
routes/
└── api.php
tests/
├── Pest.php                ← global helpers: loginAs(), loginAsAdmin()
└── Feature/Api/V1/
```

## License

MIT
