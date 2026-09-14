#!/bin/bash
echo "**********************************************"
time=$(date "+%Y-%m-%d %H:%M:%S")
#ymd=$(date -d "$1 day ago" "+%Y%m%d")
date_input=$1
len=${#date_input}
eight=8
#if [ $len -gt $zero ]; then
if [ $len -eq $eight ]; then
  ymd=$1
  echo "INPUT date:${ymd}"
else
  ymd=$(date -d "1 day ago" "+%Y%m%d")
  echo "auto date:${ymd}"
fi
dir=/data/logs/nginx
echo $(whoami) "${time} => ${ymd}"

cmd="ls ${dir}/log-access.${ymd}*.log"
echo "${cmd}"
ls -l "${dir}"/log-access."${ymd}"*.log

cmd="cat ${dir}/log-access.${ymd}*.log >> ${dir}/z_logs.${ymd}"
echo "${cmd}"
cat "${dir}"/log-access."${ymd}"*.log >>"${dir}"/z_logs."${ymd}"

cmd="rm -f ${dir}/*-access.${ymd}*.log"
echo "${cmd}"
rm -f "${dir}"/*-access."${ymd}"*.log

echo "**********************************************"
