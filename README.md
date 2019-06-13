#基于RabbitMQ ThinkPHP 的队列封装

##安装使用

```
//composer.json 增加存储库地址
"repositories": [
    {
          "type": "git",
          "url": "http://adcbguo.gicp.net:9999/hznt/mall_php_queue.git",
          "reference": "master"
        }
]
```


```
//增加auth.json文件
{
  "http-basic": {
    "adcbguo.gicp.net:9999": {
      "username": "账户",
      "password": "密码"
    }
  }
}
```


```
composer require hznt/mall_php_queue
```
##配置文件(安装后自动生成默认配置)
```
config/rabbit_mq.php
```


##推送消息到队列

```
use queue;

queue::pash('队列消耗类@消耗方法',[传递数据],1,['exchange' => 'mall_dev_log', 'queue' => 'mall_dev_log'])
```




##配置进程监听消耗


开发模式使用监听模式(会自动加载最新代码)

```
php think queue:listen --memory=128 --tries=3
```

生产模式使用工作模式(代码发布需要重启)

```
php think queue:work --memory=128 --tries=3
```

















