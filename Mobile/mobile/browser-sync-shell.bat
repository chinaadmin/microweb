@echo off
rem browser-sync node.jsÄ£¿é
echo. 
echo    path = %~dp0
echo    browser-sync start --server --files "css/*.css,html/*.html"
echo.

browser-sync start --port 80 --server --files "css/*.css,*.html,html/*.html,html/*/*.html"

rem browser-sync start --port 80 --proxy "www.m.com:8088" --files "css/*.css,html/*.html,html/*/*.html"