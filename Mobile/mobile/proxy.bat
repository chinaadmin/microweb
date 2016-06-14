@echo off
rem http-server node.jsÄ£¿é
echo. 
echo    path = %~dp0
echo    http-server -p 8686 -a 127.0.0.1 --cors --proxy="http://api.t-bihaohuo.cn"
echo.
http-server -p 8686 -a api.tp-bihaohuo.cn --cors --proxy="http://api.t-bihaohuo.cn"