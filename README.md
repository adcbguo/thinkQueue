#基于RabbitMQ ThinkPHP 的队列封装

##安装使用



首先需要有RabbitMQ服务组件




开发模式使用监听模式(会自动加载最新代码)

```
php think queue:listen --memory=128 --tries=3
```

生产模式使用工作模式(代码发布需要重启)

```
php think queue:work --memory=128 --tries=3
```

















