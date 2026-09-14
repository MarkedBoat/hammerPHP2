#!/bin/bash
echo "**********************************************"
time=$(date "+%Y-%m-%d %H:%M:%S")
echo $(whoami) "${time} cmd $1"
for line in `cat /home/users/yangjl/hosts.txt`
do
  str="ssh $line -p 5044 '$1'"
  echo str
  ssh "${line}" -p 5044 '$1'
  host=$line
  hosts[$c]=$host
  ((c++))
done

for dir in ${dirs[*]}; do
  #echo "${dir}"
  chmod 777 "${dir}"
done
echo "**********************************************"

