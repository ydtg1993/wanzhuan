#!/bin/sh

flagMaster=`ps -ef|grep -i matchingMasterOnline|grep -v grep|wc -l`
if [ $flagMaster -le 0 ]
then
  /usr/bin/php /data/release/wz_api/public/cmq-sdk/matchingMasterOnline.php &
else
  echo "prog is running..."
fi

flagYuewan=`ps -ef|grep -i matchingYuewanOnline|grep -v grep|wc -l`
if [ $flagYuewan -le 0 ]
then
  /usr/bin/php /data/release/wz_api/public/cmq-sdk/matchingYuewanOnline.php &
else
  echo "prog is running..."
fi

flagRedis=`ps -ef|grep -i matchingMasterRedisOnline|grep -v grep|wc -l`
if [ $flagRedis -le 0 ]
then
  /usr/bin/php /data/release/wz_api/public/cmq-sdk/matchingMasterRedisOnline.php &
else
  echo "prog is running..."
fi
