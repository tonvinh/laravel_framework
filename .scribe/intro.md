# Giới thiệu

API JSON thuần được xây dựng bằng Laravel 13, xác thực qua Sanctum Bearer Token, hỗ trợ filter/sort/include linh hoạt với spatie/laravel-query-builder.

<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>

Tất cả request cần có header `Accept: application/json`.

**Định dạng response:**

| Loại | Cấu trúc |
|------|----------|
| Đối tượng đơn | `{"data": {...}}` |
| Danh sách phân trang | `{"data": [...], "links": {...}, "meta": {...}}` |
| Lỗi | `{"message": "..."}` hoặc `{"message": "...", "errors": {...}}` |

**HTTP Status Codes:**

| Code | Ý nghĩa |
|------|---------|
| 200 | Thành công |
| 201 | Tạo mới thành công |
| 204 | Không có nội dung trả về |
| 401 | Chưa xác thực — thiếu hoặc sai token |
| 403 | Không có quyền thực hiện hành động |
| 404 | Không tìm thấy resource |
| 422 | Dữ liệu gửi lên không hợp lệ |

**Query parameters dùng cho tất cả danh sách:**

| Param | Mô tả | Ví dụ |
|-------|-------|-------|
| `filter[field]` | Lọc theo field | `?filter[status]=published` |
| `sort` | Sắp xếp (prefix `-` = giảm dần) | `?sort=-created_at` |
| `include` | Load relationship | `?include=user` |
| `page[number]` | Số trang | `?page[number]=2` |
| `page[size]` | Số item mỗi trang (mặc định 15) | `?page[size]=20` |

