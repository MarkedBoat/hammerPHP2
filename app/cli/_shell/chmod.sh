#!/bin/bash

echo "**********************************************"
time=$(date "+%Y-%m-%d %H:%M:%S")
echo $(whoami) "${time} "
dirs[0]='/var/run'
dirs[1]='/var/run/*'
dirs[2]='/data/log/*'
for dir in ${dirs[*]}; do
  #echo "${dir}"
  chmod 777 "${dir}"
done
echo "**********************************************"