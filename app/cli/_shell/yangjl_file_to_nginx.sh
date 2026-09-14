#!/bin/bash
echo "$0>>>**********************************************"
echo "$0>>>to dir:$2 file:$1"
str=$1
debug_flag='debug.php'
grep_debug=$(echo "${str}" | grep "${debug_flag}")
if [[ "$grep_debug" == "" ]]; then
  echo "$0>>>只能是dbeug.php文件"
  exit
else
  echo "$0>>>放行:${grep_debug}"
fi
echo "chmod ~/dev_upload/$1  >>$0";
chmod 777 ~/dev_upload/$1
ls -l ~/dev_upload/$1
echo "ok"
echo ""
echo ""

tmp_dir="/tmp/dev_upload_yangjl"
if [[ -d "${tmp_dir}" ]]; then
  echo "tmp目录已经存在 ${tmp_dir}"
else
  echo "尝试创建tmp目录 ${tmp_dir}"
  mkdir /tmp/dev_upload_yangjl
  chmod 777 /tmp/dev_upload_yangjl
fi
ls -l /tmp/dev_upload_yangjl
sudo -u nginx ls -l /tmp/dev_upload_yangjl

echo "ok"
echo ""
echo ""

echo "文件复制到tmp /home/users/yangjl/dev_upload/$1  /tmp/dev_upload_yangjl/$1  >>$0"
cp /home/users/yangjl/dev_upload/$1 /tmp/dev_upload_yangjl/$1
chmod 777 /tmp/dev_upload_yangjl/$1
ls -l /tmp/dev_upload_yangjl/$1
sudo -u nginx ls -l /tmp/dev_upload_yangjl/$1
echo "ok"
echo ""
echo ""

dist_dir="/usr/website_server_alpha/$2"
echo "文件复制到nginx /tmp/dev_upload_yangjl/$1 ${dist_dir}/  >> $0"
if [[ -d "${dist_dir}" ]]; then
  echo "nginx目录已经存在 ${dist_dir}"
else
  echo "nginx尝试创建目录 ${dist_dir}"
  sudo -u nginx mkdir "${dist_dir}"
fi
sudo -u nginx ls -l "/tmp/dev_upload_yangjl/$1"
#sudo -u nginx ls -l "${dist_dir}"


sudo -u nginx cp "/tmp/dev_upload_yangjl/$1" "${dist_dir}"
echo "cp ok"
echo "********************************************** >> $0"
