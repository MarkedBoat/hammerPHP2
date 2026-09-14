#!/bin/bash
echo "$0>>>**********************************************"
time=$(date "+%Y-%m-%d %H:%M:%S")
src_file=$1
src_file=${src_file//~/\/home\/users\/yangjl\//}
to_file=$2
dir_flag="/"
grep_dir=$(echo "${src_file}" | grep "${dir_flag}")
if [[ "${grep_dir}" == "" ]]; then
  echo "$0>>>加home"
  src_file="/home/users/yangjl/${src_file}"
else
  echo "$0>>>放行:${grep_debug}"
fi
if [[ "${to_file}" == "" ]]; then
  echo "$0>>>to dir"
  to_file="/data/code/porter/hammer/static/download/"
fi
echo "$0>>>" $(whoami) "${time} src: ${src_file} to:${to_file}"
#ssh -p5044 -i /data/upload/YANGJL -o PubkeyAcceptedKeyTypes=+ssh-dss yangjl@192.168.8.100 ls -l
scp -i /data/upload/YANGJL -o PubkeyAcceptedKeyTypes=+ssh-dss -P 5044 "yangjl@192.168.8.100:${src_file}" "${to_file}"
echo "$0>>> scp download"
echo "$0>>>**********************************************"
