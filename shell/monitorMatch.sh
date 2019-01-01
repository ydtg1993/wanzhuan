#!/bin/sh

flagMaster=`ps -ef|grep -i matchingMaster|grep -v grep|wc -l`
if [ $flagMaster -le 0 ]
then
  /www/server/php/72/bin/php /www/wwwroot/api-dev.wanzhuanhuyu.cn/public/cmq-sdk/matchingMaster.php &
else
  echo "prog is running..."
fi

flagYuewan=`ps -ef|grep -i matchingYuewan|grep -v grep|wc -l`
if [ $flagYuewan -le 0 ]
then
  /www/server/php/72/bin/php /www/wwwroot/api-dev.wanzhuanhuyu.cn/public/cmq-sdk/matchingYuewan.php &
else
  echo "prog is running..."
fi
