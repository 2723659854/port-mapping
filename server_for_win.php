<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../common.php';

use Workerman\Worker;

$config = json_decode('#config#',true);

/** 本地创建一个http服务器，用于处理浏览器发送的http请求 */
$outside_worker = new Worker('tcp://0.0.0.0:' . $config['server_port']);
$outside_worker->name = 'port-mapping';

/** 定义http代理服务器启动事件 */
$outside_worker->onWorkerStart = function () use ($outside_worker, $config) {

    /** channel 登陆上面的channel服务器 */
    Channel\Client::connect('127.0.0.1', $config['channel_port']);

    /** 定义服务端的channel客户端事件 */

    /** 定义channel 客户端接收消息事件，这里是用来接收client.php里面的channel客户端返回的http请求结果的  */
    Channel\Client::on('sc_message' . $config['local_ip'] . ":" . $config['local_port'], function ($event_data) use ($outside_worker) {
        /** 本客户端接收到数据后返回给http浏览器客户端 */
        $outside_worker->connections[$event_data['connection']['c_connection_id']]->send($event_data['data']);
    });

    /** 定义channel 客户端关闭事件 client.php里面的channel客户端完成了http请求后，会发布sc_client事件要求关闭server.php里面的客户端的连接 */
    Channel\Client::on('sc_close' . $config['local_ip'] . ":" . $config['local_port'], function ($event_data) use ($outside_worker) {
        /** 关闭浏览器的连接 */
        if (isset($outside_worker->connections[$event_data['connection']['c_connection_id']])) {
            $outside_worker->connections[$event_data['connection']['c_connection_id']]->close();
        }
    });

    /** 定义channel 客户端连接事件 */
    Channel\Client::on('sc_connect' . $config['local_ip'] . ":" . $config['local_port'], function ($event_data) use ($outside_worker) {
        //var_dump("已连上channel服务器");
    });

};

/** 定义http代理服务器连接事件 */
$outside_worker->onConnect = function ($connection) use ($config) {

    /** 获取浏览器http连接的信息 */
    $connection_data['connection'] = [
        'ip' => $connection->getRemoteIp(),
        'port' => $connection->getRemotePort(),
        'c_connection_id' => $connection->id
    ];

    /** 广播channel 客户端连接事件 通知client.php的channel客户端创建异步http客户端 */
    Channel\Client::publish('cs_connect' . $config['local_ip'] . ":" . $config['local_port'], $connection_data);

    /** 定义浏览器http连接消息事件 */
    $connection->onMessage = function ($connection, $data) use ($config) {

        /** 获取浏览器http连接的连接信息 */
        $message_data['connection'] = [
            'ip' => $connection->getRemoteIp(),
            'port' => $connection->getRemotePort(),
            'c_connection_id' => $connection->id
        ];
        /** 组装需要转发的数据 */
        $message_data['data'] = $data;
        /** 通知client.php里面的channel客户端 使用异步http客户端发送http请求 */
        Channel\Client::publish('cs_message' . $config['local_ip'] . ":" . $config['local_port'], $message_data);

    };
    /** 定义浏览器http连接关闭事件 */
    $connection->onClose = function ($connection) use ($config) {
        /** 获取连接信息 */
        $close_data['connection'] = [
            'ip' => $connection->getRemoteIp(),
            'port' => $connection->getRemotePort(),
            'c_connection_id' => $connection->id
        ];
        /** 通知client.php的channel客户端，关闭异步http客户端 */
        Channel\Client::publish('cs_close' . $config['local_ip'] . ":" . $config['local_port'], $close_data);
    };
};

\Workerman\Worker::runAll();
