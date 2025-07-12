@echo off
echo ========================================
echo TỰ ĐỘNG SỬA CÁC LỖI PSR-12 CÓ THỂ SỬA
echo ========================================
echo.

echo CẢNH BÁO: Script này sẽ thay đổi code của bạn!
echo Hãy đảm bảo bạn đã backup code trước khi tiếp tục.
echo.
set /p confirm="Bạn có muốn tiếp tục? (y/N): "
if /i not "%confirm%"=="y" (
    echo Đã hủy bỏ.
    pause
    exit /b
)

echo.
echo Đang sửa các file PHP...
echo.

echo [1/5] Sửa db_connect.php...
C:\xampp\php\php.exe phpcbf.phar --standard=PSR12 db_connect.php
echo.

echo [2/5] Sửa contdb.php...
C:\xampp\php\php.exe phpcbf.phar --standard=PSR12 contdb.php
echo.

echo [3/5] Sửa index.php (có thể mất thời gian)...
C:\xampp\php\php.exe phpcbf.phar --standard=PSR12 index.php
echo.

echo [4/5] Sửa indexdept.php (có thể mất thời gian)...
C:\xampp\php\php.exe phpcbf.phar --standard=PSR12 indexdept.php
echo.

echo [5/5] Sửa import.php...
C:\xampp\php\php.exe phpcbf.phar --standard=PSR12 import.php
echo.

echo ========================================
echo HOÀN THÀNH SỬA LỖI PSR-12
echo ========================================
echo.
echo Chạy lại check-psr12.bat để xem kết quả sau khi sửa.
echo.
pause
