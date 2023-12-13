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
            "local_ip" => "www.baidu.com",
        ],
    ]
];