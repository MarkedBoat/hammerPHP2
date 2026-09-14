#!/bin/awk -f
awk 'BEGIN {
dirs["runoob"]="www.runoob.com";
dirs["google"]="www.google.com"
print dirs["runoob"] "\n" sites["google"]
}'