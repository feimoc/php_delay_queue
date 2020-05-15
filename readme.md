## php + RabbitMq 实现延时队列

### 延时队列使用场景

那么什么时候需要用延时队列呢？考虑一下以下场景：

1. 订单在十分钟之内未支付则自动取消。
2. 新创建的店铺，如果在十天内都没有上传过商品，则自动发送消息提醒。
3. 账单在一周内未支付，则自动结算。
4. 用户注册成功后，如果三天内没有登陆则进行短信提醒。
5. 用户发起退款，如果三天内没有得到处理则通知相关运营人员。
6. 预定会议后，需要在预定的时间点前十分钟通知各个与会人员参加会议。

### 环境要求
- 需要安装rabbitmq
- 为了方便安装，我们选择docker的方式安装rabbitmq。如果没安装docker请参考:[安装docker](https://www.runoob.com/docker/centos-docker-install.html) 

#### 安装rabbitmq
- 拉取镜像

```docker
docker pull rabbitmq:3.7.7-management
```
- 查看镜像id 

```docker
sudo docker images
```
![](https://upload-images.jianshu.io/upload_images/9930928-f710f0b7f31ad049.png)

- 启动容器

```docker
docker run -d --name rabbitmq3.7.7 -p 5672:5672 -p 15672:15672 -v `pwd`/data:/var/lib/rabbitmq --hostname myRabbit -e RABBITMQ_DEFAULT_VHOST=/ -e RABBITMQ_DEFAULT_USER=gust -e RABBITMQ_DEFAULT_PASS=gust  2888deb59dfc
```
说明：

-d 后台运行容器；

--name 指定容器名；

-p 指定服务运行的端口（5672：应用访问端口；15672：控制台Web端口号）；

-v 映射目录或文件；

--hostname  主机名（RabbitMQ的一个重要注意事项是它根据所谓的 “节点名称” 存储数据，默认为主机名）；

-e 指定环境变量；（RABBITMQ_DEFAULT_VHOST：默认虚拟机名；RABBITMQ_DEFAULT_USER：默认的用户名；RABBITMQ_DEFAULT_PASS：默认用户名的密码）

### 查看容器运行成功

```docker
   sudo docker ps -a
```
![](https://upload-images.jianshu.io/upload_images/9930928-a734d94678df8625.png)
访问 http://Server-IP:15672 帐号gust 密码gust
![](https://upload-images.jianshu.io/upload_images/9930928-088465582d2d0669.png)

#### 下载项目
```php
git clone https://github.com/feimoc/php_delay_queue.git
```
#### 运行项目

- 设置composer国内源

```php
composer config repo.packagist composer https://mirrors.aliyun.com/composer/
```

- 安装php-amqplib 

```php
composer require php-amqplib/php-amqplib
```

### 新增消息

```php
php addMessage.php 或者浏览器访问
```
![](https://upload-images.jianshu.io/upload_images/9930928-987c99058fef70b8.png)
### 消费消息
- 消费延迟队列消息

```php
php consumeDelayQueue.php
```
![](https://upload-images.jianshu.io/upload_images/9930928-b5b6e8a88a150520.png)

- 消费普通队列消息

```php
php consumeQueue.php
```

> 参考
> 
> -  [php-amqplib](https://github.com/php-amqplib/php-amqplib)
> -  [一文带你搞定rabbitmq死信队列](https://www.cnblogs.com/mfrank/p/11184929.html)
> -  [一文带你搞定rabbitmq延迟队列](https://www.cnblogs.com/mfrank/p/11260355.html)
> -  [深入理解AMQP协议](https://blog.csdn.net/weixin_37641832/article/details/83270778)


