@echo off
cd /d "%~dp0"
title Link Checker - Khoi dong he thong
echo ========================================================
echo   DANG KHOI DONG LINK CHECKER VA TEST WEBSITE
echo ========================================================

REM Tim duong dan PHP tu dong
set "PHP_BIN=php"
if exist "E:\xampp\php\php.exe" set "PHP_BIN=E:\xampp\php\php.exe"
if not exist "E:\xampp\php\php.exe" (
    if exist "C:\xampp\php\php.exe" set "PHP_BIN=C:\xampp\php\php.exe"
)

REM 1. Kiem tra MySQL
echo [1/3] Kiem tra MySQL...
netstat -ano | findstr ":3306 " >nul
if %ERRORLEVEL% NEQ 0 (
    echo       MySQL chua bat. Dang khoi dong MySQL qua XAMPP...
    if exist "E:\xampp\mysql_start.bat" (
        start /b "" "E:\xampp\mysql_start.bat"
        ping 127.0.0.1 -n 3 >nul
    ) else if exist "C:\xampp\mysql_start.bat" (
        start /b "" "C:\xampp\mysql_start.bat"
        ping 127.0.0.1 -n 3 >nul
    ) else (
        echo       [LUU Y] Hay bat MySQL trong XAMPP Control Panel!
    )
) else (
    echo       MySQL dang chay tren cong 3306 [OK]
)

REM 2. Khoi chay Web test tren cong 8888
echo [2/3] Dang chay Test Website tren cong 8888...
netstat -ano | findstr ":8888 " >nul
if %ERRORLEVEL% NEQ 0 (
    start "Link Checker - Test Website (8888)" cmd /k ""%PHP_BIN%" -S 0.0.0.0:8888 -t test-website"
) else (
    echo       Test Website dang chay tren cong 8888 [OK]
)

REM 3. Khoi chay Main App tren cong 8080
echo [3/3] Dang chay Link Checker Web App tren cong 8080...
netstat -ano | findstr ":8080 " >nul
if %ERRORLEVEL% NEQ 0 (
    start "Link Checker - Main App (8080)" cmd /k ""%PHP_BIN%" -S 0.0.0.0:8080 -t public"
) else (
    echo       Web App dang chay tren cong 8080 [OK]
)

REM Cho 2 giay de server khoi dong
ping 127.0.0.1 -n 3 >nul

echo.
echo ========================================================
echo   KHOI DONG THANH CONG!
echo   - Web App kiem tra:   http://localhost:8080
echo   - Web mau de test:    http://localhost:8888
echo.
echo   Tai khoan dang nhap:
echo     Email:    admin@example.com
echo     Mat khau: password123
echo ========================================================

start http://localhost:8080

