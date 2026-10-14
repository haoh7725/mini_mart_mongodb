# Cài đặt trên Laragon

## Yêu cầu

- PHP 8.3 trở lên
- Composer 2
- MySQL 8
- Laragon 6 trở lên

## Thiết lập

1. Đặt thư mục dự án trong `D:\laragon\www` hoặc thêm thư mục hiện tại vào Laragon.
2. Tạo database MySQL tên `mini_mart` với charset `utf8mb4`.
3. Sao chép `.env.example` thành `.env` và cấu hình:

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

4. Chạy các lệnh:

```powershell
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
```

5. Mở `http://mini-mart-laravel.test` nếu dùng Auto Virtual Hosts của Laragon.

## Tài khoản mẫu

Mật khẩu chung: `Password@123`

| Vai trò | Email |
|---|---|
| Super Admin | admin@minimart.test |
| Quản lý | manager@minimart.test |
| Nhân viên | staff@minimart.test |
| Khách hàng | customer@minimart.test |
