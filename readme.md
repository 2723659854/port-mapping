###  端口映射

#### 配置

见config/config.php 。
```php 
<?php

return [
    /** 代理ip，比如一个公网ip */
    "server_ip" => "127.0.0.1",
    /** 代理端口 */
    "server_port" => 80,
    /** 映射ip 比如一个内网ip */
    "local_ip" => "127.0.0.1",
    /** 映射端口 */
    "local_port" => 9501,
    /** channel 通道端口 */
    "channel_port" => 2206,
    /** 端口映射表 */
    "nat_list" => [
        [
            /** 将80端口代理到9501端口 */
            "server_port" => 80,
            "local_port" => 9501,
        ],
        [
            /** 将8080端口代理到 9503端口 */
            "server_port" => 8080,
            "local_port" => 9503,
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

直接双加client_for_win.bat文件即可，在窗口按ctrl+c 可以关闭服务。