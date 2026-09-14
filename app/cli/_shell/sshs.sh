#!/bin/bash
echo "**********************************************"
echo ""
time=$(date "+%Y-%m-%d %H:%M:%S")
echo $(whoami) "${time}"
echo "cmd:$1"
echo "hosts_file:$2"
echo ""
echo "**********************************************"
d=$1
cmd_dir=$(awk -v dir="$1" 'BEGIN {
dirs["{web_log_today}"]="/data/web_log/nginx/access.log"
dirs["{cli_vip_error}"]="/data/log/vip/vipscript.log.wf"
dirs["{web_log}"]="/data/web_log/nginx/access."
dirs["{web_po_error}"]="/data/log/home/po.log.wf"
dirs["{web_po}"]="/data/log/home/po.log"
dirs["{web_plets_error}"]="/data/log/plets/plets.log.wf"
dirs["{web_plets}"]="/data/log/plets/plets.log"
dirs["{web_pvip_error}"]="/data/log/vip/vip.log.wf"
dirs["{web_pvip}"]="/data/log/vip/vip.log"
dirs["{web_papi_error}"]="/data/log/papi/papi.log.wf"
dirs["{web_papi}"]="/data/log/papi/papi.log"
#print "原路径" dir "\n"
for (key in dirs) {
      #print key  dirs[key] "ddd\n"
       gsub(key, dirs[key], dir);
    }
#print "新路径" dir "\n"
print dir
}')

echo "dir:${cmd_dir}"
hosts_file=$2
#for line in `cat /home/users/yangjl/hosts.txt`
for line in $(cat $hosts_file); do
  echo ""
  str="ssh $line -p 5044 '${cmd_dir}'"
  echo ">>>${str}"
  echo ""
  ssh $line -p 5044 "${cmd_dir}"
  host=$line
  hosts[$c]=$host
  ((c++))
done
