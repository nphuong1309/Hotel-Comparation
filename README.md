# JoyTix Hotel Tool

Ứng dụng PHP 8.2 + MySQL dùng để tìm kiếm, so sánh và quản lý khách sạn; người dùng có thể đăng bài, bình luận và thích bài trong trang cộng đồng.

Tài liệu chi tiết về nhiệm vụ từng file, luồng dữ liệu và quan hệ giữa các thành phần nằm trong [`TAI_LIEU_KIEN_TRUC.md`](TAI_LIEU_KIEN_TRUC.md).

## Cài đặt

1. Bật Apache và MySQL trong XAMPP.
2. Tạo database `hoteltool`, sau đó import `database.sql`.
3. Mở `http://localhost/hoteltool/`.

Thông tin kết nối không còn nằm rải rác trong mã. Có thể cấu hình các biến môi trường sau; giá trị mặc định phù hợp với XAMPP cài mới:

```text
HOTELTOOL_DB_HOST=localhost
HOTELTOOL_DB_PORT=3306
HOTELTOOL_DB_NAME=hoteltool
HOTELTOOL_DB_USER=root
HOTELTOOL_DB_PASSWORD=
HOTELTOOL_SERPAPI_KEY=your_key_here
```

Với Apache, có thể khai báo bằng `SetEnv` trong cấu hình virtual host rồi khởi động lại Apache. Không commit khóa API hoặc mật khẩu thật vào repository.

Nếu nâng cấp một database đang chạy từ phiên bản cũ, sao lưu database rồi chạy `migrations/20260717_hardening.sql` đúng một lần. Database import mới đã có sẵn các ràng buộc này.

## Kiểm tra nhanh

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Các luồng cần smoke-test khi triển khai: đăng ký/đăng nhập/đăng xuất, tìm kiếm và so sánh, admin thêm-sửa-xóa khách sạn, cộng đồng đăng bài-upload ảnh-bình luận-thích-xóa bài.
