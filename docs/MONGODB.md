# Mini Mart với MongoDB

Bản chuyển đổi giữ giao diện Blade và nghiệp vụ Laravel, sử dụng `mongodb/laravel-mongodb` 5.12. Dữ liệu nghiệp vụ nằm trong MongoDB; session/cache dùng file và queue chạy đồng bộ trong môi trường local.

## Chạy trên máy hiện tại

Mở PowerShell:

```powershell
cd D:\Mini_mart_php
.\tools\start.ps1
```

Mở http://127.0.0.1:8000. Script khởi động MongoDB riêng trên `127.0.0.1:27018`, replica set `minimart`, sau đó chạy Laravel. Dữ liệu nằm trong `.runtime/mongodb/data`; không xóa thư mục này nếu cần giữ dữ liệu. Nhấn Ctrl+C để dừng web; MongoDB tiếp tục chạy nền.

Tài khoản demo: `admin@minimart.test` hoặc `customer@minimart.test`, mật khẩu `Password@123`. Các tài khoản manager, staff, shipper trong README vẫn sử dụng được.

Nếu cổng web 8000 đang bận:

```powershell
.\tools\start.ps1 -Port 8001
```

Khi dùng cổng khác, cập nhật `APP_URL` và URI callback của Google/VNPay nếu sử dụng các tích hợp đó. Các khóa Google, Gemini và VNPay vẫn phải cấu hình riêng trong `.env`.

## Thiết lập lại trên máy Windows

Yêu cầu PHP 8.2+, Composer, MongoDB Server, mongosh và Node 20.19+ hoặc 22.12+. Máy hiện tại có PHP ở Laragon và MongoDB trong ổ D.

```powershell
.\tools\setup-windows.ps1
npm ci
npm run build
.\tools\php.ps1 artisan db:seed
.\tools\start.ps1
```

Nếu PHP/Composer ở vị trí khác:

```powershell
.\tools\setup-windows.ps1 -PhpBinary 'C:\path\php.exe' -ComposerPhar 'C:\path\composer.phar'
```

Helper tải extension MongoDB chính thức đúng PHP phiên bản, TS/NTS và kiến trúc x64 vào `.runtime`, không sửa `php.ini` toàn máy. `tools/php.ps1` nạp extension riêng của dự án. Cần có `mongod` và `mongosh` trong PATH, hoặc đặt `MINIMART_MONGOD_BINARY` cho đường dẫn mongod.

## Cấu hình

```dotenv
DB_CONNECTION=mongodb
DB_URI="mongodb://127.0.0.1:27018/?replicaSet=minimart"
DB_DATABASE=mini_mart
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Với Atlas, thay `DB_URI` bằng URI của cluster rồi chạy `tools/start.ps1 -SkipMongo`. MongoDB standalone không hỗ trợ transaction nhiều document; checkout và hoàn kho cần replica set hoặc sharded cluster. Replica set một node ở local phục vụ phát triển.

## Những phần đã chuyển

- Các model dùng MongoDB Eloquent; `User` giữ Laravel Auth, hashing mật khẩu, phân quyền và Google login.
- ID là chuỗi khi đi qua route, request, JavaScript và các trường tham chiếu. MongoDB tự tạo `_id` ObjectId cho document mới.
- Giá khuyến mãi, lọc/sắp xếp giá, thống kê và báo cáo dùng biểu thức/aggregation MongoDB.
- Những giá trị mặc định trước đây do MySQL cấp được đặt tại `DocumentDefaults` và seeder. Giá trị số từ form được lưu dưới dạng số; `decimal` của Product dùng BSON Decimal128.
- Checkout cập nhật tồn kho, giới hạn chiến dịch và số lượt coupon có điều kiện trong cùng transaction.
- Các transaction ở service con dùng chung transaction của caller. Xung đột ghi được thử lại tối đa năm lần; callback gửi thông báo của lượt thất bại được hủy.
- Thông báo dùng model MongoDB riêng, giữ chức năng chuông thông báo và đánh dấu đã đọc.
- Migration đang chạy ở `database/migrations` tạo collection/index MongoDB. Migration MySQL cũ được lưu tại `database/sql-migrations`, không chạy tự động.
- MongoDB không tự thực thi foreign key hay cascade delete. Luồng hiện tại chủ yếu xóa mềm; khi bổ sung xóa vĩnh viễn, cần xử lý document liên quan trong nghiệp vụ.

## Dữ liệu demo và kiểm thử

Seeder tạo danh mục, sản phẩm, lô tồn kho, tài khoản, coupon, khách hàng và đơn hàng mẫu. Không chạy seed lại trên dữ liệu cần giữ nguyên: seeder danh mục/sản phẩm/tồn kho được thiết kế cho demo.

```powershell
.\tools\php.ps1 artisan migrate
.\tools\php.ps1 artisan db:seed
.\tools\php.ps1 vendor/bin/phpunit
```

Test dùng database `mini_mart_testing` trên MongoDB. Trait `RefreshMongoDatabase` chỉ reset database có đuôi `_testing` trong môi trường `testing`; dữ liệu demo ở `mini_mart` được giữ riêng. Không chạy test song song cùng database này.

File backup `minimart.sql` được cung cấp trong phiên làm việc chỉ chứa cấu trúc bảng, không có dữ liệu INSERT. Bản chạy local này sử dụng dữ liệu demo theo lựa chọn của người dùng; chưa có dữ liệu MySQL thật được chuyển.

Các script và `.runtime` phục vụ chạy local, không phải cấu hình production. Khi triển khai, dùng MongoDB được xác thực và cấu hình các dịch vụ tích hợp theo môi trường.
