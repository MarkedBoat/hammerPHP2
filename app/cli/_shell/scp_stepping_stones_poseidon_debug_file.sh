#!/bin/bash
#本脚本是从开发DEV服务器 -> Stepping Stones 跳板机  --> 正式服务器同步 debug文件
#思路:文件必须带 debug.后缀 ,所有文件都放到指定目录中，项目目录中建立软连接，方便删除和管理
#参数1 开发文件 参数2
echo "$0>>>**********************************************"
time=$(date "+%Y-%m-%d %H:%M:%S")
echo "$0>>>" $(whoami) "${time} file: $1 "
var=$1
filename=${var##*/}
dir=${var%/*}
dir=${dir//\/data\/code\/poseidon_server\//}
echo "DIR:${dir} FILE:${filename}"
#ssh -p5044 -i /data/upload/YANGJL -o PubkeyAcceptedKeyTypes=+ssh-dss yangjl@192.168.8.100 ls -l
echo ">>>upload  1/3 上传文件到8.100   >>$0"
scp -i /data/upload/YANGJL -o PubkeyAcceptedKeyTypes=+ssh-dss -P 5044 "/data/code/poseidon_server/${dir}/${filename}" yangjl@192.168.8.100:/home/users/yangjl/dev_upload/
echo "ok"
echo ""
echo ""
echo ">>>upload to cms 2/3 上传文件到 yangjl@31  >>$0"
ssh -p5044 -i /data/upload/YANGJL -o PubkeyAcceptedKeyTypes=+ssh-dss yangjl@192.168.8.100 "scp -P 5044  ~/dev_upload/${filename}  yangjl@10.1.6.31:/home/users/yangjl/dev_upload/"
echo "ok"
echo ""
echo ""

echo ">>>cms cp 3/3 移动文件到  nginx@31 >>$0"
ssh -p5044 -i /data/upload/YANGJL -o PubkeyAcceptedKeyTypes=+ssh-dss yangjl@192.168.8.100 "ssh -p 5044 yangjl@10.1.6.31 sh yangjl_file_to_nginx.sh ${filename} ${dir}"
echo "ok"
echo ""
echo ""
echo "$0>>>**********************************************"
