#! /bin/bash
# author: 来源网络
# desc: 监控workerman主进程
# crontab */1 * * * * root /bin/sh /home/wwwroot/lftjxg/project/default/cli/workermanMonitor.sh > /dev/null 2>&1 &
count=`ps -ef|grep 'WorkerMan'|grep -v 'grep'|grep 'master'|wc -l`
echo $count;
if [ $count -lt 1 ];then
cd /home/wwwroot/lftjxg/project/default/cli/
php timingplan.php start -d
echo "restart";
echo $(date +%Y-%m-%d_%H:%M:%S) >/home/wwwroot/lftjxg/project/default/log/workermanmonitor.log
fi

