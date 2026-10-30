@echo off
cd /d "%~dp0"
title Link Checker - Khoi chay tren XAMPP Apache
echo ========================================================
echo   KHOI CHAY LINK CHECKER TREN XAMPP (APACHE + MYSQL)
echo ========================================================

REM Tim duong dan XAMPP
set "XAMPP_DIR=E:\xampp"
if not exist "E:\xampp" (
    if exist "C:\xampp" set "XAMPP_DIR=C:\xampp"
)

set "PHP_BIN=%XAMPP_DIR%\php\php.exe"
if not exist "%PHP_BIN%" set "PHP_BIN=php"

REM 1. Kiem tra MySQL
echo [1/4] Kiem tra MySQL...
netstat -ano | findstr ":3306 " >nul
if %ERRORLEVEL% NEQ 0 (
    echo       MySQL chua bat. Dang khoi dong MySQL qua XAMPP...
    if exist "%XAMPP_DIR%\mysql_start.bat" (
        start /b "" "%XAMPP_DIR%\mysql_start.bat"
        ping 127.0.0.1 -n 3 >nul
    )
) else (
    echo       MySQL dang chay tren cong 3306 [OK]
)

REM 2. Kiem tra Apache
echo [2/4] Kiem tra Apache...
netstat -ano | findstr ":80 " >nul
if %ERRORLEVEL% NEQ 0 (
    echo       Apache chua bat. Dang khoi dong Apache qua XAMPP...
    if exist "%XAMPP_DIR%\apache_start.bat" (
        start /b "" "%XAMPP_DIR%\apache_start.bat"
        ping 127.0.0.1 -n 3 >nul
    )
) else (
    echo       Apache dang chay tren cong 80 [OK]
)

REM 3. Kiem tra lien ket thu muc trong htdocs
echo [3/4] Kiem tra cau hinh htdocs...
set "HTDOCS_DIR=%XAMPP_DIR%\htdocs"
if not exist "%HTDOCS_DIR%\link-checker" (
    echo       Tao lien ket link-checker trong htdocs...
    mklink /J "%HTDOCS_DIR%\link-checker" "%~dp0public" 2>nul
) else (
    echo       Thu muc htdocs\link-checker da san sang [OK]
)

REM Tao them alias de tranh go nham URL
if not exist "%HTDOCS_DIR%\de-tai-13" (
    mklink /J "%HTDOCS_DIR%\de-tai-13" "%~dp0public" >nul 2>&1
)
if not exist "%HTDOCS_DIR%\link_checker" (
    mklink /J "%HTDOCS_DIR%\link_checker" "%~dp0public" >nul 2>&1
)

REM 4. Khoi chay Web test tren cong 8888
echo [4/4] Dang chay Website mau de kiem tra (cong 8888)...
netstat -ano | findstr ":8888 " >nul
if %ERRORLEVEL% NEQ 0 (
    start "Link Checker - Test Website (8888)" cmd /k ""%PHP_BIN%" -S 0.0.0.0:8888 -t test-website"
    ping 127.0.0.1 -n 2 >nul
) else (
    echo       Test Website dang chay tren cong 8888 [OK]
)

echo.
echo ========================================================
echo   KHOI DONG THANH CONG!
echo.
echo   - Dia chi Web App:      http://localhost/link-checker
echo   - Website mau de test:  http://localhost:8888
echo.
echo   Tai khoan dang nhap:
echo     Email:    admin@example.com
echo     Mat khau: password123
echo ========================================================

REM Mo trinh duyet
start http://localhost/link-checker

pause
