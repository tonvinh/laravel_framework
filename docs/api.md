# API Documentation

**Base URL:** `http://localhost:8000/api`  
**Version:** v1  
**Format:** JSON (`Content-Type: application/json`, `Accept: application/json`)

---

## Authentication

API sử dụng **Bearer Token** (Laravel Sanctum). Thêm header vào mọi request cần xác thực:

```
Authorization: Bearer {token}
```

Token nhận được sau khi `login` hoặc `register`.

---

## Response Format

### Single resource
```json
{
  "data": { ... }
}
```

### Paginated list
```json
{
  "data": [ ... ],
  "links": {
    "first": "http://localhost:8000/api/v1/demos?page=1",
    "last":  "http://localhost:8000/api/v1/demos?page=5",
    "prev":  null,
    "next":  "http://localhost:8000/api/v1/demos?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page":    5,
    "per_page":     15,
    "total":        72
  }
}
```

### Error
```json
{ "message": "This action is unauthorized." }
```

### Validation error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200  | OK |
| 201  | Created |
| 204  | No Content |
| 401  | Unauthenticated |
| 403  | Unauthorized |
| 404  | Not Found |
| 422  | Validation Error |
| 500  | Server Error |

---

## Query Parameters (tất cả list endpoints)

| Param | Mô tả | Ví dụ |
|-------|-------|-------|
| `filter[field]` | Lọc theo field | `?filter[status]=published` |
| `sort` | Sắp xếp (prefix `-` = DESC) | `?sort=-created_at` |
| `include` | Load relationship | `?include=user` |
| `page[number]` | Số trang | `?page[number]=2` |
| `page[size]` | Số item/trang (mặc định 15) | `?page[size]=20` |

---

## Auth

### Register

```
POST /api/v1/auth/register
```

**Request body:**

| Field | Type | Bắt buộc | Mô tả |
|-------|------|----------|-------|
| `name` | string | ✓ | Tên người dùng (max 255) |
| `email` | string | ✓ | Email hợp lệ, chưa tồn tại |
| `password` | string | ✓ | Mật khẩu (min 8 ký tự) |
| `password_confirmation` | string | ✓ | Xác nhận mật khẩu |

**Ví dụ request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Response `201`:**
```json
{
  "data": {
    "token": "1|abc123xyz...",
    "user": {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Nguyen Van A",
      "email": "user@example.com",
      "created_at": "2026-05-16T04:00:00+00:00"
    }
  }
}
```

---

### Login

```
POST /api/v1/auth/login
```

**Request body:**

| Field | Type | Bắt buộc | Mô tả |
|-------|------|----------|-------|
| `email` | string | ✓ | Email đã đăng ký |
| `password` | string | ✓ | Mật khẩu |

**Ví dụ request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

**Response `200`:**
```json
{
  "data": {
    "token": "1|abc123xyz...",
    "user": {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Nguyen Van A",
      "email": "user@example.com",
      "created_at": "2026-05-16T04:00:00+00:00"
    }
  }
}
```

**Response `422` (sai credentials):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

---

### Logout

```
POST /api/v1/auth/logout
```

**Headers:** `Authorization: Bearer {token}` ✓

**Response `204`:** _(no content)_

---

### Get Current User

```
GET /api/v1/auth/me
```

**Headers:** `Authorization: Bearer {token}` ✓

**Response `200`:**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "created_at": "2026-05-16T04:00:00+00:00"
  }
}
```

---

## Demos

> Tất cả endpoints Demos yêu cầu `Authorization: Bearer {token}`.

### Demo Object

| Field | Type | Mô tả |
|-------|------|-------|
| `uuid` | string (uuid) | Identifier công khai |
| `title` | string | Tiêu đề (max 255) |
| `content` | string\|null | Nội dung |
| `status` | string (enum) | `draft` \| `published` \| `archived` |
| `user` | object\|null | Owner (chỉ có khi `?include=user`) |
| `created_at` | string (ISO 8601) | Thời gian tạo |
| `updated_at` | string (ISO 8601) | Thời gian cập nhật |

---

### List Demos

```
GET /api/v1/demos
```

**Query parameters khả dụng:**

| Param | Giá trị hợp lệ |
|-------|---------------|
| `filter[title]` | string (tìm theo tên) |
| `filter[status]` | `draft` \| `published` \| `archived` |
| `sort` | `title` \| `-title` \| `created_at` \| `-created_at` |
| `include` | `user` |
| `page[number]` | integer |
| `page[size]` | integer |

**Ví dụ request:**
```bash
curl "http://localhost:8000/api/v1/demos?filter[status]=published&sort=-created_at&include=user&page[size]=10" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response `200`:**
```json
{
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Hello World",
      "content": "Nội dung demo...",
      "status": "published",
      "user": {
        "uuid": "660e8400-e29b-41d4-a716-446655440001",
        "name": "Nguyen Van A",
        "email": "user@example.com",
        "created_at": "2026-05-16T04:00:00+00:00"
      },
      "created_at": "2026-05-16T10:30:00+00:00",
      "updated_at": "2026-05-16T10:30:00+00:00"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/demos?page=1",
    "last":  "http://localhost:8000/api/v1/demos?page=3",
    "prev":  null,
    "next":  "http://localhost:8000/api/v1/demos?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page":    3,
    "per_page":     10,
    "total":        25
  }
}
```

---

### Get Demo

```
GET /api/v1/demos/{uuid}
```

**Ví dụ request:**
```bash
curl "http://localhost:8000/api/v1/demos/550e8400-e29b-41d4-a716-446655440000?include=user" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response `200`:**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Hello World",
    "content": "Nội dung demo...",
    "status": "published",
    "user": null,
    "created_at": "2026-05-16T10:30:00+00:00",
    "updated_at": "2026-05-16T10:30:00+00:00"
  }
}
```

**Response `404`:**
```json
{ "message": "Resource not found." }
```

---

### Create Demo

```
POST /api/v1/demos
```

**Request body:**

| Field | Type | Bắt buộc | Mô tả |
|-------|------|----------|-------|
| `title` | string | ✓ | Tiêu đề (max 255) |
| `content` | string | | Nội dung |
| `status` | string | | `draft` (mặc định) \| `published` \| `archived` |
| `user_id` | integer | ✓ | ID của user sở hữu |

**Ví dụ request:**
```bash
curl -X POST http://localhost:8000/api/v1/demos \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Hello World",
    "content": "Nội dung demo đầu tiên",
    "status": "published",
    "user_id": 1
  }'
```

**Response `201`:**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Hello World",
    "content": "Nội dung demo đầu tiên",
    "status": "published",
    "user": null,
    "created_at": "2026-05-16T10:30:00+00:00",
    "updated_at": "2026-05-16T10:30:00+00:00"
  }
}
```

**Response `422`:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title":   ["The title field is required."],
    "user_id": ["The user id field is required."]
  }
}
```

---

### Update Demo

```
PUT /api/v1/demos/{uuid}
```

> Cũng hỗ trợ `PATCH`.

**Request body:** (các field muốn cập nhật)

| Field | Type | Mô tả |
|-------|------|-------|
| `title` | string | Tiêu đề mới (max 255) |
| `content` | string | Nội dung mới |
| `status` | string | `draft` \| `published` \| `archived` |
| `user_id` | integer | ID user sở hữu |

**Ví dụ request:**
```bash
curl -X PUT http://localhost:8000/api/v1/demos/550e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Tiêu đề đã sửa",
    "status": "archived",
    "user_id": 1
  }'
```

**Response `200`:**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Tiêu đề đã sửa",
    "content": "Nội dung demo đầu tiên",
    "status": "archived",
    "user": null,
    "created_at": "2026-05-16T10:30:00+00:00",
    "updated_at": "2026-05-16T11:00:00+00:00"
  }
}
```

---

### Delete Demo

```
DELETE /api/v1/demos/{uuid}
```

> Xóa mềm (soft delete) — record vẫn còn trong DB, chỉ set `deleted_at`.

**Ví dụ request:**
```bash
curl -X DELETE http://localhost:8000/api/v1/demos/550e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response `204`:** _(no content)_

---

## Error Reference

### 401 Unauthenticated
Thiếu hoặc sai token.
```json
{ "message": "Unauthenticated." }
```

### 403 Unauthorized
Token hợp lệ nhưng không có quyền thực hiện action.
```json
{ "message": "This action is unauthorized." }
```

### 404 Not Found
Resource không tồn tại hoặc đã bị xóa.
```json
{ "message": "Resource not found." }
```

### 422 Validation Error
Dữ liệu gửi lên không hợp lệ.
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message."]
  }
}
```

---

## Quick Start

```bash
# 1. Register
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password123","password_confirmation":"password123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

# 2. Lưu user id
USER_ID=$(curl -s http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print(d['data'].get('id',1))")

# 3. Tạo demo
curl -X POST http://localhost:8000/api/v1/demos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"title\":\"My First Demo\",\"status\":\"published\",\"user_id\":1}"

# 4. List demos
curl "http://localhost:8000/api/v1/demos?filter[status]=published&sort=-created_at" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```
