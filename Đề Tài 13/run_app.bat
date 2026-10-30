@echo off
cd /d "%~dp0"
title Link Checker - Main App (Port 8080)
echo ========================================================
echo   Dang khoi chay Link Checker tren http://localhost:8080
echo   Nhan Ctrl+C de dung server
echo ========================================================

set "PHP_BIN=php"
if exist "E:\xampp\php\php.exe" set "PHP_BIN=E:\xampp\php\php.exe"
if not exist "E:\xampp\php\php.exe" (
    if exist "C:\xampp\php\php.exe" set "PHP_BIN=C:\xampp\php\php.exe"
)

"%PHP_BIN%" -S 0.0.0.0:8080 -t public
pause
