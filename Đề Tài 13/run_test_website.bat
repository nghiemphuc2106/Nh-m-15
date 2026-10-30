@echo off
cd /d "%~dp0"
title Link Checker - Test Website (Port 8888)
echo ========================================================
echo   Dang khoi chay Test Website tren http://localhost:8888
echo   Nhan Ctrl+C de dung server
echo ========================================================

set "PHP_BIN=php"
if exist "E:\xampp\php\php.exe" set "PHP_BIN=E:\xampp\php\php.exe"
if not exist "E:\xampp\php\php.exe" (
    if exist "C:\xampp\php\php.exe" set "PHP_BIN=C:\xampp\php\php.exe"
)

"%PHP_BIN%" -S 0.0.0.0:8888 -t test-website
pause
