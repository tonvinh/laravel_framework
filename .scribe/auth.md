# Xác thực

Để xác thực request, thêm header **`Authorization`** với giá trị **`"Bearer {YOUR_AUTH_KEY}"`**.

Các endpoint yêu cầu xác thực được đánh dấu bằng badge `requires authentication` trong tài liệu bên dưới.

Gọi <code>POST /api/v1/auth/register</code> hoặc <code>POST /api/v1/auth/login</code> để lấy token, sau đó truyền vào header <code>Authorization: Bearer {token}</code>.
