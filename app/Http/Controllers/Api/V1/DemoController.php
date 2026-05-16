<?php

namespace App\Http\Controllers\Api\V1;

use App\Base\BaseApiController;
use App\Http\Resources\DemoResource;
use App\Repositories\DemoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoController extends BaseApiController
{
    protected string $repository = DemoRepository::class;
    protected string $resource   = DemoResource::class;

    /**
     * Danh sách demo
     *
     * Trả về danh sách demo có phân trang. Hỗ trợ lọc theo tiêu đề và trạng thái,
     * sắp xếp, load relationship và phân trang linh hoạt.
     *
     * @group Demo
     *
     * @queryParam filter[title] string Lọc theo tiêu đề (tìm kiếm gần đúng). Example: Hello
     * @queryParam filter[status] string Lọc theo trạng thái: draft, published, archived. Example: published
     * @queryParam sort string Sắp xếp theo title hoặc created_at (thêm tiền tố - để giảm dần). Example: -created_at
     * @queryParam include string Load thêm relationship. Giá trị hợp lệ: user. Example: user
     * @queryParam page[number] integer Số trang. Example: 1
     * @queryParam page[size] integer Số item mỗi trang (mặc định 15). Example: 15
     *
     * @response 200 {"data":[{"uuid":"550e8400-e29b-41d4-a716-446655440000","title":"Hello World","content":"Nội dung demo...","status":"published","user":null,"created_at":"2026-05-16T10:30:00+00:00","updated_at":"2026-05-16T10:30:00+00:00"}],"links":{"first":"http://localhost:8000/api/v1/demos?page=1","last":"http://localhost:8000/api/v1/demos?page=3","prev":null,"next":"http://localhost:8000/api/v1/demos?page=2"},"meta":{"current_page":1,"last_page":3,"per_page":15,"total":25}}
     * @response 401 {"message":"Unauthenticated."}
     */
    public function index(): JsonResponse
    {
        return parent::index();
    }

    /**
     * Xem chi tiết demo
     *
     * Trả về thông tin chi tiết của một Demo theo UUID.
     *
     * @group Demo
     *
     * @urlParam uuid string required UUID của demo. Example: 550e8400-e29b-41d4-a716-446655440000
     * @queryParam include string Load thêm relationship. Giá trị hợp lệ: user. Example: user
     *
     * @response 200 {"data":{"uuid":"550e8400-e29b-41d4-a716-446655440000","title":"Hello World","content":"Nội dung demo...","status":"published","user":null,"created_at":"2026-05-16T10:30:00+00:00","updated_at":"2026-05-16T10:30:00+00:00"}}
     * @response 404 {"message":"Resource not found."}
     */
    public function show(string $uuid): JsonResponse
    {
        return parent::show($uuid);
    }

    /**
     * Tạo demo mới
     *
     * Tạo một bản ghi Demo mới. Demo sẽ được gán cho người dùng đang đăng nhập.
     *
     * @group Demo
     *
     * @bodyParam title string required Tiêu đề (tối đa 255 ký tự). Example: Hello World
     * @bodyParam content string Nội dung chi tiết. Example: Nội dung demo đầu tiên.
     * @bodyParam status string Trạng thái: draft (mặc định), published, archived. Example: published
     *
     * @response 201 {"data":{"uuid":"550e8400-e29b-41d4-a716-446655440000","title":"Hello World","content":"Nội dung demo đầu tiên.","status":"published","user":null,"created_at":"2026-05-16T10:30:00+00:00","updated_at":"2026-05-16T10:30:00+00:00"}}
     * @response 422 {"message":"The given data was invalid.","errors":{"title":["The title field is required."]}}
     */
    public function store(Request $request): JsonResponse
    {
        // Force user_id to authenticated user — never accept from client
        $request->merge(['user_id' => $request->user()->id]);

        return parent::store($request);
    }

    /**
     * Cập nhật demo
     *
     * Cập nhật thông tin của một Demo. Hỗ trợ cả PUT (thay toàn bộ) và PATCH (cập nhật một phần).
     *
     * @group Demo
     *
     * @urlParam uuid string required UUID của demo. Example: 550e8400-e29b-41d4-a716-446655440000
     * @bodyParam title string Tiêu đề mới (tối đa 255 ký tự). Example: Tiêu đề đã sửa
     * @bodyParam content string Nội dung mới. Example: Nội dung đã cập nhật.
     * @bodyParam status string Trạng thái mới: draft, published, archived. Example: archived
     * @bodyParam user_id integer ID của user sở hữu. Example: 1
     *
     * @response 200 {"data":{"uuid":"550e8400-e29b-41d4-a716-446655440000","title":"Tiêu đề đã sửa","content":"Nội dung demo...","status":"archived","user":null,"created_at":"2026-05-16T10:30:00+00:00","updated_at":"2026-05-16T11:00:00+00:00"}}
     * @response 404 {"message":"Resource not found."}
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        return parent::update($request, $uuid);
    }

    /**
     * Xóa demo
     *
     * Xóa mềm (soft delete) một Demo. Bản ghi vẫn còn trong cơ sở dữ liệu
     * nhưng không còn truy cập được qua API.
     *
     * @group Demo
     *
     * @urlParam uuid string required UUID của demo. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 204 {}
     * @response 404 {"message":"Resource not found."}
     */
    public function destroy(string $uuid): JsonResponse
    {
        return parent::destroy($uuid);
    }
}
