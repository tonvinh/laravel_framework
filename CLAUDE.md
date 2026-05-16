# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- **Laravel 13** / **PHP 8.5** — pure JSON API, no Blade views, no web routes
- **Auth**: `laravel/sanctum` — token-based (`Authorization: Bearer {token}`)
- **RBAC**: `spatie/laravel-permission` — roles & permissions
- **DTO**: `spatie/laravel-data`
- **Query**: `spatie/laravel-query-builder` — filter/sort/include via query params
- **Tests**: `pestphp/pest` v4
- **Style**: `laravel/pint` (PSR-12)

## Commands

```bash
php artisan serve                        # Start dev server
php artisan route:list --path=api        # List all API routes
php artisan make:migration create_X_table
php artisan migrate

./vendor/bin/pest                        # Run all tests
./vendor/bin/pest tests/Feature/Api/V1/ProductTest.php  # Single test file
./vendor/bin/pest --filter "creates a product"           # Single test

./vendor/bin/pint                        # Fix code style
./vendor/bin/pint --test                 # Check style without fixing
```

## Laravel 13 Gotchas

- `App\Http\Controllers\Controller` is **empty** in Laravel 13 — `authorize()` is NOT included by default. `BaseApiController` adds `AuthorizesRequests` via `Controller.php` using `use \Illuminate\Foundation\Auth\Access\AuthorizesRequests`.
- `spatie/laravel-query-builder` v7 uses **variadic args**: `allowedFilters(string ...$f)` — NOT array. `BaseRepository::query()` uses spread operator `...$this->allowedFilters`.
- Child repositories must declare `public string $model` (not `protected`) because `BaseRepository::$model` is `public` and PHP forbids decreasing visibility in subclasses.
- Test helpers (`getJson`, `postJson`, etc.) are in `Pest\Laravel` namespace — import with `use function Pest\Laravel\getJson;` in each test file (already imported in `tests/Pest.php` for convenience but each file still needs its own imports).

## Architecture: Base Class Inheritance

**Core principle**: write base classes once, child classes declare only what's specific.

### Three base classes (write once, never modify):

| Class | Location | Responsibility |
|-------|----------|----------------|
| `BaseModel` | `app/Base/BaseModel.php` | uuid, softDeletes, scopeActive, scopeSearch |
| `BaseRepository` | `app/Base/BaseRepository.php` | CRUD, paginate, filter, validate |
| `BaseApiController` | `app/Base/BaseApiController.php` | 5 CRUD actions: index/show/store/update/destroy |

Traits: `HasUuidTrait` (auto-uuid, route key = uuid), `ApiResponseTrait` (success/created/error/noContent).

### Adding a new resource — always 6 files:

```
Migration      → define table (always include id, uuid, timestamps, softDeletes)
Enum           → (if status field exists) PHP 8.1+ backed enum in app/Enums/
Model          → extends BaseModel, only $fillable + $casts + relationships
Repository     → extends BaseRepository, declare $model + $allowedFilters + $allowedSorts + $allowedIncludes + rules()
Controller     → extends BaseApiController, declare $repository + $resource only
Resource       → extends JsonResource, toArray() — expose uuid not id
Route          → Route::apiResource(...) inside auth:sanctum group in routes/api.php
```

### Child class conventions:

**Model** (`app/Models/`): only `$fillable`, `$casts`, relationships. Nothing else.

**Repository** (`app/Repositories/`): validation rules live in `rules(string $action, ?Model $model)`. Never create FormRequest. Logic goes in repository methods, not Action classes.

**Controller** (`app/Http/Controllers/Api/V1/`): only `$repository` and `$resource` properties. Add methods only for non-CRUD actions.

**Resource** (`app/Http/Resources/`): always expose `uuid`, never `id`.

## API Conventions

**Route keys**: always `uuid` — `Route::apiResource()` uses `getRouteKeyName()` which returns `'uuid'` from `HasUuidTrait`.

**Response format**:
- Single resource: `{"data": {...}}`
- Paginated list: `{"data": [...], "links": {...}, "meta": {...}}`
- Error: `{"message": "..."}` or `{"message": "...", "errors": {...}}`

**HTTP codes**: 200 GET, 201 POST, 204 DELETE, 422 validation, 401 unauthenticated, 403 unauthorized, 404 not found.

**Query params** (spatie/laravel-query-builder):
```
?filter[status]=active
?sort=-created_at
?include=category
?page[number]=2&page[size]=20
```

## Database Conventions

Every migration must include: `$table->id()`, `$table->uuid('uuid')->unique()`, `$table->timestamps()`, `$table->softDeletes()`.

Use `string` column + PHP Enum cast — never MySQL ENUM type. Foreign keys: `{table_singular}_id`.

## Testing (Pest v4)

`tests/Pest.php` provides global helpers `loginAs(?User)` and `loginAsAdmin()`. Tests use `RefreshDatabase`.

Every resource needs 5 test cases: `index`, `show`, `store`, `update`, `destroy`, plus a dataset for validation errors. Tests live in `tests/Feature/Api/V1/{Resource}Test.php`.

## Permission Naming

Format: `{resource}.{action}` — e.g. `products.view`, `products.create`, `orders.approve`.
