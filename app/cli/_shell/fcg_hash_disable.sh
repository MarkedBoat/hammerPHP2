#!/bin/bash
# 在31上运行，对下线异常数据的
pwd
echo "**********************************************"
time=$(date "+%Y-%m-%d %H:%M:%S")
echo "${time} arg0:$1 arg1:$2  arg2:$3 arg3:$4"
echo "**********************************************"
echo "___________________GIT________________________"
cmd_connect_mysql="mysql -h10.2.6.1 -ufcg_repository -pWcIbemH91E -P3306"
database_kw="db_fcg_repository"
source_sql=""
cmd_connect_redis="/usr/bin/redis-cli -h 10.1.6.7 -p 6370"
show_keys="keys papi\/push*"

tmp_input=$1
len=${#tmp_input}
zero=0
if [ $len -gt $zero ]; then

  sql_file=$1
  echo "INPUT)sql file:${sql_file}"
else
  date=$(date -d yesterday +"%Y%m%d")
  sql_file="fcg_hash_disable_$date.sql"
  echo $sql_file | xargs -I file sh -c 'ls -l file;rm -f  file; wget http://content.fengmi.tv/output/file; ls -l file; '
  sql_file="/home/nginx/${sql_file}"
  echo "***************************************************************************************"
  echo "download sql file:${sql_file}"
  echo "***************************************************************************************"
fi
expect -c "
spawn pwd;
expect {
        \"not found\"{ send \"pwd\r\"; }
}
"

echo "***************************************************************************************"
echo "SOURCE SQL"
echo "***************************************************************************************"

expect <<EOF
spawn $cmd_connect_mysql;
expect "mysql"
send "show databases;\r"
expect	"| db_fcg_repository\r"
send "use $database_kw;\r"
expect "Database changed"
send "source $sql_file;\r"
expect "mysql"
send "exit\r"
expect eof;
EOF

echo "***************************************************************************************"
echo "PHP RUNING"
echo "***************************************************************************************"
str1=$2
len=${#str1}
if [ $len -gt $zero ]; then
  #将,替换为空格
  arg1=(${str1//,/ })
  mtype=${arg1[0]}
  cp_name=${arg1[1]}
  qid=${arg1[2]}
  ids_file=${arg1[3]}
  cmd_lrange="LRANGE papi/push_retry:video:0:${cp_name}:${qid} 0 200"

  echo $ids_file | xargs -I file sh -c 'ls -l file;rm -f  file; wget http://content.fengmi.tv/output/file; ls -l file; '

  #exit
  cd /usr/website_server_alpha/papi.funshion.com/script/

  echo "***************************************************************************************"
  echo "before flush queue:check redis "
  echo "***************************************************************************************"
  echo $cmd_connect_redis $show_keys
  echo $cmd_connect_redis $cmd_lrange
  $cmd_connect_redis $show_keys
  $cmd_connect_redis $cmd_lrange
  #/usr/local/php5.3.11/bin/php cp_mv_enqueue.php  -mtype video -cps milike  -idfile /home/nginx/$ids_file  -qid milike
  cmd_php_1="/usr/local/php5.3.11/bin/php cp_mv_enqueue.php  -mtype ${mtype} -cps ${cp_name}  -idfile /home/nginx/${ids_file}  -qid ${qid}"
  #	/usr/local/php5.3.11/bin/php cp_mv_enqueue.php  -mtype $mtype -cps $cp_name  -idfile /home/nginx/$ids_file  -qid $qid
  echo $cmd_php_1
  $cmd_php_1
  echo "***************************************************************************************"
  echo "after flush queue:check redis "
  echo "***************************************************************************************"
  $cmd_connect_redis $show_keys
  echo $cmd_connect_redis $show_keys
  $cmd_connect_redis $cmd_lrange
  echo $cmd_connect_redis $cmd_lrange
  #/usr/local/php5.3.11/bin/php push_retry.php -mtype $mtype -unit 0 -cp $cp_name -qid $qid
  cmd_php_2="/usr/local/php5.3.11/bin/php push_retry.php -mtype ${mtype} -unit 0 -cp ${cp_name} -qid ${qid}"
  echo "$cmd_php_2"
  $cmd_php_2
  echo "***************************************************************************************"
  echo "after retry :check redis "
  echo "***************************************************************************************"
  $cmd_connect_redis "$show_keys"
  echo "$cmd_connect_redis" "$show_keys"
  $cmd_connect_redis "$cmd_lrange"
  echo "$cmd_connect_redis" "$cmd_lrange"
else
  echo "no params,stop"
fi
