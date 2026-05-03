@echo off
:loop
php -f "C:\xampp\htdocs\khsanxuat\check_completion_status.php"
timeout /t 3600 /nobreak
goto loop 