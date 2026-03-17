@echo off
title Penguatkuasa System - One Click Deploy

echo ===============================
echo PENGUATKUASA SYSTEM DEPLOY
echo ===============================

cd /d C:\xampp_new\htdocs\penguatkuasa

echo.
set /p msg=Enter commit message:

git add .
git commit -m "%msg%"
git push

echo.
echo ===============================
echo DEPLOY COMPLETE
echo ===============================
pause