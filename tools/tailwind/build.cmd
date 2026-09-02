@echo off
REM Build Tailwind cho 5 modal indexdept -> assets/css/tailwind.css
REM   tools\tailwind\build.cmd          : build 1 lan (minify)
REM   tools\tailwind\build.cmd watch    : build lai moi khi sua partial/JS
REM Chay lai MOI KHI them/sua class tw-* trong indexdept.php, views/indexdept/partials/*.php
REM hoac assets/js/indexdept/*.js, roi commit ca assets/css/tailwind.css.

setlocal
set ROOT=%~dp0..\..
set CLI=%~dp0tailwindcss.exe

if not exist "%CLI%" (
    echo [LOI] Khong tim thay %CLI%
    echo Tai tailwindcss-windows-x64.exe v3.4.x theo huong dan trong tools\tailwind\README.md
    exit /b 1
)

pushd "%ROOT%"
if /i "%~1"=="watch" (
    "%CLI%" -c tailwind.config.js -i assets/css/tailwind/tailwind.src.css -o assets/css/tailwind.css --watch
) else (
    "%CLI%" -c tailwind.config.js -i assets/css/tailwind/tailwind.src.css -o assets/css/tailwind.css --minify
)
set CODE=%ERRORLEVEL%
popd
endlocal & exit /b %CODE%
