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
