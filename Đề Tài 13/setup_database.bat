@echo off
cd /d "%~dp0"
title Link Checker - Khoi tao Database SQL
echo ========================================================
echo   KHOI TAO CO SO DU LIEU CHO LINK CHECKER
echo ========================================================

REM Tim duong dan PHP tu dong (E:\xampp hoac C:\xampp)
set "PHP_BIN=php"
if exist "E:\xampp\php\php.exe" set "PHP_BIN=E:\xampp\php\php.exe"
if not exist "E:\xampp\php\php.exe" (
    if exist "C:\xampp\php\php.exe" set "PHP_BIN=C:\xampp\php\php.exe"
)

echo Dang su dung PHP tai: %PHP_BIN%
echo.

REM Kiem tra MySQL dang chay
netstat -ano | findstr ":3306 " >nul
if %ERRORLEVEL% NEQ 0 (
    echo [CANH BAO] MySQL chua chay!
    echo Vui long mo XAMPP Control Panel va bam Start MySQL truoc.
    echo.
    pause
    exit /b 1
)

echo [1/2] Dang tao Database va cac bang (Migration)...
"%PHP_BIN%" database/migrate.php
if %ERRORLEVEL% NEQ 0 (
    echo [LOI] Khong the chay migration. Kiem tra lai MySQL!
    pause
    exit /b 1
)

echo.
echo [2/2] Dang nap du lieu mau (Seeding)...
"%PHP_BIN%" database/seed.php

echo.
echo ========================================================
echo   HOAN TAT KHOI TAO CSDL!
echo   Tai khoan mau:
echo     Admin: admin@example.com / password123
echo     User:  user@example.com  / password123
echo ========================================================
pause
