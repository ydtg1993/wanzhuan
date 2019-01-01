#!/bin/sh

flagSocketEvent=`ps -ef|grep -i socketEvent|grep -v grep|wc -l`
if [ $flagSocketEvent -le 0 ]
then
  /usr/bin/php /data/release/wz_api/public/cmq-sdk/socketEvent.php &
else
  echo "prog is running..."
fi

endOrderEvent=`ps -ef|grep -i endOrderEvent|grep -v grep|wc -l`
if [ $endOrderEvent -le 0 ]
then
  /usr/bin/php /data/release/wz_api/public/cmq-sdk/endOrderEvent.php &
else
  echo "prog is running..."
fi