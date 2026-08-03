# JoyTix Hotel Tool

JoyTix là ứng dụng web hỗ trợ tìm kiếm, xem chi tiết và so sánh khách sạn tại Cần Thơ. Hệ thống còn có khu vực cộng đồng để người dùng chia sẻ trải nghiệm và trang quản trị để quản lý dữ liệu khách sạn.

## Công nghệ sử dụng

- PHP 8.2
- MySQL/MariaDB
- HTML, CSS và JavaScript
- Apache (khuyến nghị chạy bằng XAMPP)

## Cài đặt và chạy ứng dụng

1. Đặt thư mục dự án tại `C:\xampp\htdocs\hoteltool`.
2. Mở XAMPP và khởi động Apache cùng MySQL.
3. Mở phpMyAdmin tại `http://localhost/phpmyadmin/`.
4. Import `schema.sql` để tạo cơ sở dữ liệu `hoteltool` và toàn bộ cấu trúc bảng.
5. Import `sample-data.sql` để thêm dữ liệu dùng thử.
6. Truy cập `http://localhost/hoteltool/` để sử dụng ứng dụng.

Thứ tự import bắt buộc:

```text
schema.sql
sample-data.sql
```

## Cấu hình

Ứng dụng mặc định sử dụng cấu hình MySQL của XAMPP: máy chủ `localhost`, cổng `3306`, tài khoản `root`, mật khẩu trống và cơ sở dữ liệu `hoteltool`.

Khi cần thay đổi, cấu hình các biến môi trường sau:

```text
HOTELTOOL_DB_HOST=localhost
HOTELTOOL_DB_PORT=3306
HOTELTOOL_DB_NAME=hoteltool
HOTELTOOL_DB_USER=root
HOTELTOOL_DB_PASSWORD=
```

Hai khóa API dưới đây chỉ phục vụ chức năng quản trị viên tự động lấy thông tin khách sạn từ Google Maps và hỗ trợ tạo mô tả. Các luồng tìm kiếm, so sánh và cộng đồng vẫn hoạt động khi chưa cấu hình chúng.

```text
HOTELTOOL_SERPAPI_KEY=your_serpapi_key
HOTELTOOL_GROQ_API_KEY=your_groq_api_key
```

Không lưu khóa API hoặc mật khẩu thật trực tiếp trong mã nguồn.

## Tài khoản dùng thử

| Vai trò | Tên đăng nhập | Mật khẩu |
| --- | --- | --- |
| Quản trị viên | `admin` | `Admin@123` |
| Người dùng | `demo` | `Demo@123` |

## Luồng sử dụng chính

### Khách chưa đăng nhập

1. Truy cập trang chủ để xem danh sách và thông tin nổi bật của khách sạn.
2. Tìm kiếm theo tên, mức giá, số sao, phong cách hoặc tiện ích.
3. Mở trang chi tiết để xem mô tả, phòng, giá và hình ảnh.
4. Chọn nhiều khách sạn và mở trang so sánh.
5. Đăng ký tài khoản nếu muốn tham gia cộng đồng hoặc lưu hoạt động cá nhân.

### Người dùng đã đăng nhập

1. Đăng nhập bằng tài khoản đã đăng ký.
2. Tìm kiếm, xem chi tiết và so sánh khách sạn.
3. Đăng bài chia sẻ trải nghiệm, đính kèm hình ảnh và liên kết khách sạn.
4. Thích, bình luận hoặc xóa bài viết do mình tạo.
5. Mở trang tài khoản để xem lại lịch sử so sánh và các bài đã đăng.

### Quản trị viên

1. Đăng nhập bằng tài khoản có vai trò quản trị viên.
2. Mở trang quản trị để xem danh sách khách sạn.
3. Thêm khách sạn, phòng, tiện ích và hình ảnh.
4. Sửa hoặc xóa khách sạn hiện có.
5. Quản lý nội dung cộng đồng; quản trị viên có quyền xóa bài viết vi phạm.

## Các tệp chính

| Tệp hoặc thư mục | Chức năng |
| --- | --- |
| `index.php` | Trang chủ và biểu mẫu tìm kiếm |
| `search.php` | Hiển thị kết quả tìm kiếm |
| `detail.php` | Hiển thị chi tiết khách sạn |
| `compare.php` | So sánh các khách sạn đã chọn |
| `auth.php` | Đăng ký, đăng nhập và đăng xuất |
| `profile.php` | Thông tin và lịch sử hoạt động của người dùng |
| `community.php` | Bài đăng, hình ảnh và bình luận cộng đồng |
| `admin.php` | Trang quản lý khách sạn |
| `adminadd.php` | Thêm khách sạn mới |
| `edit_hotel.php` | Chỉnh sửa khách sạn |
| `api.php` | Xử lý các yêu cầu bất đồng bộ của hệ thống |
| `includes/` | Kết nối cơ sở dữ liệu, cấu hình và thành phần dùng chung |
| `css/`, `js/` | Giao diện và xử lý phía trình duyệt |
| `uploads/` | Hình ảnh do hệ thống sử dụng hoặc người dùng tải lên |
| `schema.sql` | Cấu trúc cơ sở dữ liệu |
| `sample-data.sql` | Dữ liệu mẫu để chạy thử |

## Kiểm tra sau khi cài đặt

Sau khi mở ứng dụng, nên kiểm tra lần lượt:

1. Trang chủ hiển thị khách sạn và hình ảnh.
2. Tìm kiếm và so sánh trả về đúng dữ liệu.
3. Tài khoản `demo` đăng nhập được và có thể đăng bài, bình luận, thích bài.
4. Tài khoản `admin` mở được trang quản trị và có thể thêm, sửa khách sạn.
5. Hình ảnh tải lên được lưu trong thư mục `uploads/`.
