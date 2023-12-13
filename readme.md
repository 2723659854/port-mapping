###  端口映射

###  项目介绍
本项目主要用来代理http服务，提供端口映射服务，可以作为网关使用。
比如可以将很多域名解析到本服务器，然后由本服务器代理到其他任意服务器。
比如一台连接了公网的服务器，然后很多台内网服务器，那么可以使用公网服务器转发请求的到内网服务器。

###  项目安装

```php 
composer create-project xiaosongshu/port-mapping
```
###  项目结构

~~~
|--config
  |-config.php              # 配置文件
|--temp_client_for_win      # windows环境 channel客户端运行目录
  |-tpl.php                 # windows环境 channel客户端模板文件
|--vendor                   # 扩展文件
  |-...
|--windows_server           # windows环境 服务端运行目录
  |-...
-   channel.php             # windows环境channel服务端文件
-   client.php              # linux环境channel客户端文件
-   client_for_win.bat      # windows环境启动文件
-   client_for_win.php      # windows环境 channel客户端生成器
-   common.php              # 公共函数
-   composer.json           # 项目依赖配置文件
-   composer.lock           # 项目依赖配置文件版本锁定文件
-   server.php              # linux环境服务端启动文件
-   server_for_win.php      # windows环境服务端模板文件
-   start_win_server.php    # windows环境服务端启动模板文件
~~~

#### 配置

见config/config.php 。
```php 
<?php

return [
    /** 代理ip 本服务器ip地址 */
    "server_ip" => "127.0.0.1",
    /** 代理端口 本服务器暴露的端口 nat_list为空生效 */
    "server_port" => 8001,
    /** 映射ip 被代理的服务器的ip地址  nat_list为空生效 */
    "local_ip" => "127.0.0.1",
    /** 映射端口 被代理的服务器的端口 nat_list为空生效*/
    "local_port" => 9501,
    /** channel 通道端口 */
    "channel_port" => 2206,
    /** 端口映射表 */
    "nat_list" => [
        [
            /** 访问端口 */
            "server_port" => 8000,
            /** 映射端口 */
            "local_port" => 9501,
            /** 映射IP */
            "local_ip" => "127.0.0.1",
        ],
        [
            /** 访问端口 */
            "server_port" => 8400,
            /** 映射端口 */
            "local_port" => 80,
            /** 映射IP */
            "local_ip" => "156.236.71.182",
        ],
    ]
];
```
###   启动

####  linux环境
启动服务端
```bash 
php server.php start (-d)
```
启动客户端
```bash 
php client.php start (-d)
```
####  windows环境

直接双击client_for_win.bat文件即可，
#### 关闭服务
在窗口按ctrl+c 可以关闭服务。

####  联系作者

2723659854@qq.com