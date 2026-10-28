@echo off
echo === PAWLUNE CatCare - Setup Laragon ===
echo 1) Pastikan Laragon sudah START (Apache/Nginx + MySQL).
call composer install --no-interaction
if not exist .env copy .env.example .env
call php artisan key:generate --force
echo 2) Membuat database catcare_db (jika mysql ada di PATH Laragon)...
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS catcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" 2>nul
call php artisan migrate:fresh --seed --force
call php artisan storage:link
call php artisan optimize:clear
echo.
echo SELESAI. Buka: http://catcare.test  (atau  php artisan serve  lalu http://127.0.0.1:8000)
pause
