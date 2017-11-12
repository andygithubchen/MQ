#!/bin/bash

wget https://github.com/kr/beanstalkd/archive/v1.10.tar.gz --no-check-certificate
mv v1.10.tar.gz beanstalkd-1.10.tar.gz
tar zxvf beanstalkd-1.10.tar.gz
cd beanstalkd-1.10
make
make install
#make install PREFIX=/opt/modules/beanstalkd/ #指定安装路径

logPath=/var/log/beanstalkd/
mkdir -p $logPath
cp ./beanstalk /etc/init.d/beanstalkd
/etc/init.d/beanstalkd start

#beanstalkd -v #查看版本号
#beanstalkd -VVV  快速启动beanstalkd
#lsof -i:11300  #查看 该服务是否正常启动
