@echo off
set "SCRIPT_DIR=%~dp0"
set "PROJECT_ROOT=%SCRIPT_DIR%..\.."
pushd "%PROJECT_ROOT%"
echo ========================================
echo KIỂM TRA TUÂN THỦ PSR-12 CHO DỰ ÁN PHP
echo ========================================
echo.

echo Đang kiểm tra các file PHP chính...
echo.

echo [1/5] Kiểm tra index.php...
C:\xampp\php\php.exe "%SCRIPT_DIR%phpcs.phar" --standard=PSR12 --report=summary index.php
echo.

echo [2/5] Kiểm tra indexdept.php...
C:\xampp\php\php.exe "%SCRIPT_DIR%phpcs.phar" --standard=PSR12 --report=summary indexdept.php
echo.

echo [3/5] Kiểm tra db_connect.php...
C:\xampp\php\php.exe "%SCRIPT_DIR%phpcs.phar" --standard=PSR12 --report=summary db_connect.php
echo.

echo [4/5] Kiểm tra contdb.php...
C:\xampp\php\php.exe "%SCRIPT_DIR%phpcs.phar" --standard=PSR12 --report=summary contdb.php
echo.

echo [5/5] Kiểm tra import.php...
C:\xampp\php\php.exe "%SCRIPT_DIR%phpcs.phar" --standard=PSR12 --report=summary import.php
echo.

echo ========================================
echo HOÀN THÀNH KIỂM TRA PSR-12
echo ========================================
echo.
echo Để sửa tự động các lỗi có thể sửa được, chạy:
echo C:\xampp\php\php.exe "%SCRIPT_DIR%phpcbf.phar" --standard=PSR12 [tên_file.php]
echo.
popd
pause
