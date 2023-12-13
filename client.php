<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/common.php';

use Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;

/** 读取配置 */
try {
    $config = get_config();
} catch (\Exception $e) {
    echo "error:{$e}\n";
    exit;
}
/** 如果是linux环境 则开启多个客户端 */
if (isset($config['nat_list']) && !is_win()) {
    foreach ($config['nat_list'] as $n_key => $n_value) {
        $unique_key = $n_key;
        $nat_client_list['nat_client_worker_' . $n_key] = build_client_woker($n_value);
    }
} else {
    /** windows 只启动一个客户端 */
    $worker = build_client_woker($config);
}

Worker::runAll();

/**
 * 负责向真实的服务器发送异步http请求，并返回数据给server.php的channel客户端
 * @param $config
 * @return void
 */
function build_client_woker($config)
{

    /** 启动一个worker服务  */
    $inside_worker = new Worker();
    /** 定义服务启动事件 */
    $inside_worker->onWorkerStart = function () use ($inside_worker, $config) {

        /**  channel客户端去登陆channel服务端 */
        Channel\Client::connect($config['server_ip'], $config['channel_port']);
        /** 定义channel客户端连接事件 */
        Channel\Client::on('cs_connect' . $config['local_ip'] . ":" . $config['local_port'], function ($event_data) use ($inside_worker, $config) {
            /** 拼接真实的http服务器地址 这个地址是被代理的地址 */
            $local_host_name = "tcp://" . $config['local_ip'] . ":" . $config['local_port'];
            /** 创建一个异步的http连接 */
            $connection_to_local = new AsyncTcpConnection($local_host_name);
            /** http客户端定义连接事件 */
            $connection_to_local->onConnect = function ($connection) use ($event_data, $config) {
                /** 获取http异步客户端的相关参数 */
                $connect_data['connection'] = [
                    'ip' => $connection->getRemoteIp(),
                    'port' => $connection->getRemotePort(),
                    'c_connection_id' => $event_data['connection']['c_connection_id']
                ];
                /** 告知server.php的channel客户端，http连接已经建立 */
                Channel\Client::publish('sc_connect' . $config['local_ip'] . ":" . $config['local_port'], $connect_data);
            };
            /** 定义http客户端接收到消息事件 */
            $connection_to_local->onMessage = function ($connection, $data) use ($config, $event_data) {
                /** 组装需要转发的数据 */
                $message_data['data'] = $data;
                $message_data['connection'] = [
                    'ip' => $connection->getRemoteIp(),
                    'port' => $connection->getRemotePort(),
                    'c_connection_id' => $event_data['connection']['c_connection_id']
                ];
                /** 将http结果转发给server.php的channel客户端， */
                Channel\Client::publish('sc_message' . $config['local_ip'] . ":" . $config['local_port'], $message_data);
            };

            /** 定义http客户端的关闭事件 */
            $connection_to_local->onClose = function ($connection) use ($event_data, $config) {

                $close_data['connection'] = [
                    'ip' => $connection->getRemoteIp(),
                    'port' => $connection->getRemotePort(),
                    'c_connection_id' => $event_data['connection']['c_connection_id']
                ];
                /** 通知server.php的channel客户端，异步http客户端已关闭 */
                Channel\Client::publish('sc_close' . $config['local_ip'] . ":" . $config['local_port'], $close_data);
            };
            /** http异步客户端连接真实的服务器 */
            $connection_to_local->connect();
            /** 将这个http客户端保存起来 */
            $inside_worker->connections[$event_data['connection']['c_connection_id']] = $connection_to_local;

        });

        /** 以下是定义的客户端channel的两个事件  */
        /** 定义channel客户端接收到消息事件 定义的是cs_message 不是sc_message */
        Channel\Client::on('cs_message' . $config['local_ip'] . ":" . $config['local_port'], function ($event_data) use ($inside_worker,$config) {
            /** 异步客户端向真实的服务器发送http报文 */
            $inside_worker->connections[$event_data['connection']['c_connection_id']]->send($event_data['data']);
        });
        /** 定义channel关闭客户端事件 */
        Channel\Client::on('cs_close' . $config['local_ip'] . ":" . $config['local_port'], function ($event_data) use ($inside_worker) {
            /** 将异步http客户端关闭 */
            if(isset($inside_worker->connections[$event_data['connection']['c_connection_id']])){
                $inside_worker->connections[$event_data['connection']['c_connection_id']]->close();
            }
        });
    };


}