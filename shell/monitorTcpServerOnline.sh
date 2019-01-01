#!/bin/sh

count=`ps -fe |grep "wzhy_server_2.php" | grep -v "grep" | wc -l`

echo $count
if [ $count -lt 1 ]
then
ps -eaf |grep "wzhy_server_2.php" | grep -v "grep"| awk '{print $2}'|xargs kill -9
sleep 2
ulimit -c unlimited
/usr/bin/php /data/release/wz_api/public/swoole/wzhy_server_2.php &
fi