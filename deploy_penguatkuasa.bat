@echo off
title Penguatkuasa System - Deploy

echo ===============================
echo PENGUATKUASA SYSTEM DEPLOY
echo ===============================

cd /d C:\xampp_new\htdocs\penguatkuasa

echo.
set /p msg=Enter commit message:
if "%msg%"=="" set msg=auto update

git add .
git commit -m "%msg%" || echo No changes to commit

git push origin main

echo.
echo ===============================
echo DEPLOY COMPLETE (PENGUATKUASA)
echo ===============================

pause