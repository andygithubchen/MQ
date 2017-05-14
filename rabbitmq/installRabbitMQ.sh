#!/bin/bash

erlang=otp_src_*
rabbitmq=rabbitmq-server


##安装erlang-----------------------------------------------------------
if [ ! -f ./${erlang}.tar.gz ];then
  wget http://erlang.org/download/otp_src_19.3.tar.gz
fi

tar -zxvf ./${erlang}.tar.gz

cd ${erlang}
./configure -prefix=/usr/local/erlang
make && make install
sed -i "s/PATH.*/&\:\/usr\/local\/erlang\/bin/" /etc/profile
export PATH=$PATH:/usr/local/erlang/bin
source /etc/profile
cd -


#安装rabbitmq---------------------------------------------------------
if [ ! -f ./${rabbitmq}*.tar.xz ];then
  wget http://www.rabbitmq.com/releases/rabbitmq-server/v3.6.9/rabbitmq-server-generic-unix-3.6.9.tar.xz
fi

xz -d ./${rabbitmq}*.tar.xz
mkdir ./${rabbitmq} && tar -xvf ${rabbitmq}*.tar -C ./${rabbitmq} --strip-components 1

mv ./${rabbitmq} /usr/local/rabbitmq
sed -i "s/PATH.*/&\:\/usr\/local\/rabbitmq\/sbin/" /etc/profile
export PATH=$PATH:/usr/local/rabbitmq/sbin
source /etc/profile
echo "[{rabbit, [{loopback_users, []}]}]." > /usr/local/rabbitmq/etc/rabbitmq/rabbitmq.config
rabbitmq-server -detached

echo "+-----------------------------------+"
echo "| 本机IP:15672"
echo "| User: guest  Pwd: guest"
echo "+-----------------------------------+"


#安装rabbitmq php 扩展-----------------------------------------------
rabbitmq=rabbitmq
if [ ! -f ./${rabbitmq}*.tar.gz ];then
  wget https://github.com/alanxz/rabbitmq-c/releases/download/v0.8.0/rabbitmq-c-0.8.0.tar.gz
fi
tar -zxvf ./${rabbitmq}*.tar.gz

cd ${rabbitmq}*
./configure
make && make install
cd -

pecl install amqp



#
# 一些常用的rabbitmq/sbin/下的命令
# 启动RabbitMQ            ./rabbitmq-server -detached
# 停止RabbitMQ            ./rabbitmqctl stop
# 查看已经安装的插件    ./rabbitmq-plugins list
# 启用监控插件               ./rabbitmq-plugins enable rabbitmq_management
# 关闭监控插件        ./rabbitmq-plugins disable rabbitmq_management
# 新增一个用户               rabbitmqctl  add_user  Username  Password
# 删除一个用户               rabbitmqctl  delete_user  Username
# 修改用户的密码            rabbitmqctl  change_password  Username  Newpassword
# 查看当前用户列表         rabbitmqctl  list_users
# 赋予超级管理员权限      ./rabbitmqctl set_user_tags newuser administrator
#



