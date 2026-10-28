# Mini Mart PHP

Ứng dụng cửa hàng mini xây dựng bằng Laravel, hỗ trợ duyệt sản phẩm, quản lý giỏ hàng và đặt hàng. Dự án có sẵn dữ liệu mẫu để chạy thử trên máy cá nhân.

## Chức năng hiện có

- Trang chủ và danh sách sản phẩm theo danh mục.
- Tìm kiếm, lọc và xem chi tiết sản phẩm.
- Thêm, cập nhật và xóa sản phẩm trong giỏ hàng.
- Thanh toán và tạo đơn hàng.
- Đăng ký, đăng nhập, đăng xuất và đặt lại mật khẩu.
- Phân quyền Super Admin, Quản lý, Nhân viên và Khách hàng.
- Dashboard quản trị và khu vực `/admin` responsive.
- CRUD danh mục và sản phẩm, upload ảnh, xóa mềm và khôi phục.
- Lưu ảnh trên ổ đĩa local hoặc Supabase Storage qua giao thức S3.
- Tìm kiếm, lọc và cập nhật trạng thái đơn hàng.
- Kiểm tra tồn kho khi thêm giỏ/checkout, trừ kho theo lô và ghi lịch sử xuất kho.
- Tạo bản ghi thanh toán COD khi đặt hàng.
- Quản lý sản phẩm, danh mục, tồn kho theo kho và lô hàng ở tầng dữ liệu.
- Seeder cung cấp danh mục, sản phẩm và tài khoản mẫu.
- Giao diện responsive sử dụng Blade và Bootstrap 5.

## Công nghệ

- PHP 8.2 trở lên
- Laravel 12
- SQLite (mặc định) hoặc MySQL 8
- Node.js và npm
- Vite 7, Bootstrap 5

## Cài đặt nhanh

```powershell
git clone https://github.com/NguyenEvelyn/Mini_mart_php.git
cd Mini_mart_php
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Mở `http://127.0.0.1:8000` trong trình duyệt.

Với macOS hoặc Linux, thay lệnh sao chép file môi trường bằng:

```bash
cp .env.example .env
```

## Cơ sở dữ liệu

Cấu hình mặc định trong `.env.example` sử dụng SQLite. Laravel sẽ sử dụng file `database/database.sqlite` trên máy local; file này không được đưa lên GitHub.

Để dùng MySQL với Laragon, tạo database `mini_mart`, sau đó cập nhật `.env`:

```env
APP_NAME="Mini Mart"
APP_LOCALE=vi
APP_FALLBACK_LOCALE=vi

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_mart
DB_USERNAME=root
DB_PASSWORD=
```

Sau đó chạy:

```powershell
php artisan migrate:fresh --seed
php artisan storage:link
```

Hướng dẫn Laragon chi tiết nằm trong [`docs/LARAGON.md`](docs/LARAGON.md). Sơ đồ dữ liệu nằm trong [`docs/ERD.md`](docs/ERD.md).

## Tài khoản dữ liệu mẫu

Khi chạy seeder, các tài khoản sau được tạo với mật khẩu dùng chung `Password@123`:

| Vai trò | Email |
|---|---|
| Super Admin | `admin@minimart.test` |
| Quản lý | `manager@minimart.test` |
| Nhân viên | `staff@minimart.test` |
| Khách hàng | `customer@minimart.test` |

> Các tài khoản trên chỉ dành cho môi trường phát triển. Không sử dụng mật khẩu mẫu khi triển khai production.

## Chạy trong lúc phát triển

Chạy toàn bộ web server, queue, log viewer và Vite:

```powershell
composer run dev
```

Hoặc chạy backend và frontend ở hai terminal:

```powershell
php artisan serve
npm run dev
```

## Kiểm thử

### Mã giảm giá

Quản lý và Super Admin quản trị mã tại `/admin/coupons`: tạo, sửa và bật/tắt mã,
giảm theo phần trăm hoặc số tiền, đơn tối thiểu, mức giảm tối đa, thời gian hiệu lực
và giới hạn tổng lượt dùng. Khách nhập mã tại trang thanh toán, bấm Áp dụng để xem
tổng tiền mới hoặc Bỏ mã. Mã được kiểm tra lại trong giao dịch tạo đơn, áp dụng cho
cả COD và VNPay. Mỗi đơn dùng một mã; giảm trên tiền hàng, không giảm phí giao.
Ngưỡng miễn phí giao hàng tính trên tiền hàng trước khi áp dụng mã.

Lượt dùng được giữ khi tạo đơn, trả lại một lần khi hủy đơn. Thanh toán thất bại
vẫn giữ lượt đến khi đơn được hủy. Không xóa mã đã dùng; tắt mã để giữ lịch sử.
`WELCOME15` là mã mẫu giảm 15% cho đơn từ 300.000đ, không giới hạn đơn đầu tiên.
Trên cơ sở dữ liệu đã có, chạy `php artisan db:seed --class=CouponSeeder` để thêm mã
mẫu mà không đặt lại dữ liệu khác. Seeder không ghi đè mã đã tồn tại.

```powershell
composer test
```

Bộ test kiểm tra trang cửa hàng, bảo vệ route quản trị, phân quyền khách hàng và quy trình checkout tạo đơn/thanh toán/trừ kho.

## Thanh toán VNPay Sandbox

Đăng ký merchant thử nghiệm tại `https://sandbox.vnpayment.vn/devreg`, sau đó thêm thông tin được VNPay gửi vào `.env`:

```env
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_TMN_CODE=ma_website_cua_ban
VNPAY_HASH_SECRET=chuoi_bi_mat_cua_ban
VNPAY_RETURN_URL=
```

Khi chạy local có thể để trống `VNPAY_RETURN_URL`; ứng dụng tự tạo URL `/thanh-toan/vnpay/return`. VNPay IPN chỉ gọi được khi website có địa chỉ HTTPS công khai; URL IPN là `/thanh-toan/vnpay/ipn`.

Đơn VNPay trừ kho ngay khi tạo. Nếu khách bỏ dở thanh toán, lệnh `orders:expire-unpaid` hủy đơn chưa thanh toán sau 30 phút (liên kết VNPay hết hạn sau 15 phút) và hoàn kho. Lệnh được lên lịch chạy 5 phút một lần, cần giữ scheduler chạy:

```powershell
php artisan schedule:work
```

Trên server, thêm cron `* * * * * php /duong-dan/artisan schedule:run`. Có thể chạy tay: `php artisan orders:expire-unpaid --minutes=30`.

## Đăng nhập bằng Google

Tạo OAuth Client (loại *Web application*) trong Google Cloud Console, thêm vào **Authorized redirect URIs** đúng địa chỉ callback, rồi khai báo trong `.env`:

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://localhost:8000/dang-nhap/google/callback
```

Google chỉ cho phép `http` với `localhost`/`127.0.0.1` và **không nhận tên miền `.test`** của Laragon (báo lỗi `400: invalid_request`). Vì vậy hãy chạy `php artisan serve` (cổng 8000) khi đăng nhập Google. Nếu đang mở web bằng địa chỉ khác (ví dụ `mini-mart-laravel.test`), nút "Tiếp tục với Google" sẽ tự chuyển sang `GOOGLE_REDIRECT_URI` để hoàn tất đăng nhập. Muốn dùng địa chỉ khác, thêm đúng địa chỉ đó vào Google Console và sửa `GOOGLE_REDIRECT_URI`.

## Khu vực quản trị

Sau khi seed dữ liệu, đăng nhập bằng `admin@minimart.test` với mật khẩu `Password@123`, sau đó mở `/admin`. Tài khoản quản lý có quyền danh mục, sản phẩm và đơn hàng; nhân viên có thể vận hành sản phẩm và đơn hàng nhưng không được thay đổi danh mục.

## Lưu ý bảo mật

- Không commit file `.env`, database local, log, cache, `vendor/` hoặc `node_modules/`.
- Chỉ dùng `.env.example` làm mẫu cấu hình và không ghi secret thật vào file này.
- Thay thông tin đăng nhập mẫu và đặt `APP_DEBUG=false` trước khi triển khai production.

## Cấu trúc chính

```text
app/                 Controllers, models và service của ứng dụng
config/              Cấu hình Laravel
database/            Migration, factory và seeder
docs/                Tài liệu cài đặt và sơ đồ dữ liệu
public/               Điểm vào web và tài nguyên public
resources/            Blade views, CSS và JavaScript
routes/               Khai báo route
tests/                Kiểm thử tự động
```

## Giấy phép

Dự án sử dụng giấy phép MIT.
