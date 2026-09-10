# Diễn đàn UTH (PHP + MySQL)

Website diễn đàn nội bộ cho sinh viên, giảng viên và quản trị viên Trường Đại học Giao thông Vận tải TP.HCM (UTH).

## Tính năng đã triển khai

**Sinh viên**
- Đăng ký / đăng nhập (mật khẩu băm `password_hash`), phiên làm việc qua session.
- Đăng bài bằng trình soạn thảo rich text (đậm, nghiêng, gạch chân, danh sách, liên kết).
- Bình luận, thả tim (like AJAX), sửa/xóa bài viết của chính mình.
- Tham gia lớp học bằng mã mời (invite code).
- Chat 1-1 và chat nhóm lớp (AJAX polling mỗi 3 giây).

**Giảng viên**
- Tạo lớp học, hệ thống tự sinh mã mời.
- Đăng thông báo khẩn cấp (gắn cờ nổi bật, tự động duyệt — không cần chờ admin).
- Tải lên tài liệu / bài tập nhiều định dạng (PDF, Word, Excel, ảnh, zip...).
- Xóa bình luận sai phạm, trả lời câu hỏi học thuật trong lớp mình quản lý.

**Quản trị viên**
- Dashboard thống kê: tổng người dùng, bài viết, lớp học, tệp tin, dung lượng lưu trữ, lượt truy cập theo ngày.
- Quản lý danh sách người dùng có phân trang, đổi vai trò, khóa/mở khóa tài khoản.
- Hàng đợi duyệt bài: phê duyệt / từ chối bài đăng của sinh viên trước khi hiển thị công khai.
- Quản lý tệp tin: xem, xóa, theo dõi dung lượng lưu trữ.

## Cài đặt

1. **Yêu cầu**: PHP 8+, MySQL/MariaDB, extension `pdo_mysql`.
2. Tạo database và nạp schema:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
3. Mở `config/db.php` và cập nhật `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
4. Tạo mật khẩu thật cho tài khoản admin mẫu:
   ```bash
   php -r "echo password_hash('Admin@123', PASSWORD_DEFAULT);"
   ```
   rồi cập nhật vào bảng `users` (hoặc đăng ký tài khoản mới rồi tự đổi `role` thành `admin` trong DB).
5. Đảm bảo thư mục `uploads/materials` có quyền ghi (`chmod -R 775 uploads`).
6. Chạy bằng PHP built-in server để thử nhanh:
   ```bash
   php -S localhost:8000
   ```
   rồi mở `http://localhost:8000`.

## Cấu trúc thư mục

```
config/     kết nối database, cấu hình upload
sql/        schema.sql — toàn bộ cấu trúc bảng
includes/   auth.php, functions.php, header.php, footer.php dùng chung
css/, js/   giao diện & rich text editor / chat polling
uploads/    nơi lưu tài liệu do giảng viên / sinh viên tải lên
class/      tạo lớp, tham gia lớp, xem lớp, tải tài liệu
teacher/    dashboard danh sách lớp của giảng viên
admin/      dashboard thống kê, quản lý user, duyệt bài, quản lý file
chat/       chat 1-1 và chat nhóm (AJAX polling)
```

## Giới hạn hiện tại / gợi ý mở rộng

- Chat dùng AJAX polling (3 giây/lần) để đơn giản hóa triển khai; có thể nâng cấp lên WebSocket
  (Pusher, Socket.io qua Node phụ trợ, hoặc Laravel Reverb) nếu cần thời gian thực tức thì.
- Rich text editor dùng `contenteditable` + `execCommand` (nhẹ, không phụ thuộc thư viện ngoài);
  có thể thay bằng TinyMCE/Quill nếu cần định dạng phong phú hơn.
- Thống kê "lượt truy cập" hiện đếm mỗi lần tải trang chủ; với traffic lớn nên chuyển sang
  Redis/queue để tránh ghi DB trên mỗi request.
- Chưa có xác thực email, khôi phục mật khẩu, hay đăng nhập Google/Facebook — có thể bổ sung sau.
