#!/bin/bash
echo "**********************************************"
dir='/home/kinglone/.kl.his'
time=$(date "+%Y-%m-%d %H:%M:%S")
cmd=$*
echo $(whoami) "${time} " >> "${dir}"
echo "${cmd}" >> "${dir}"
echo $(whoami) "${time} "
echo "${cmd}"
$1 $2 $3 $4 $5 $6 $7 $8 $9
echo "**********************************************"