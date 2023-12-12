<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../common.php';

use Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;

$config = json_decode('{"server_port":8080,"local_port":9502,"local_ip":"127.0.0.1","name":"9502<->8080","password":"phpnb","channel_port":2206,"server_ip":"127.0.0.1"}',true);


$inside_worker = new Worker();

$inside_worker->onWorkerStart = function() use ($inside_worker,$config){

    // Channel客户端连接到Channel服务端
    Channel\Client::connect($config['server_ip'], $config['channel_port']);

    Channel\Client::on('cs_connect'.$config['local_ip'].":".$config['local_port'], function($event_data) use($inside_worker,$config){

        $local_host_name = "tcp://".$config['local_ip'].":".$config['local_port'];

        $connection_to_local = new AsyncTcpConnection($local_host_name);

        $connection_to_local->onConnect = function($connection) use ($event_data,$config){

            $connect_data['connection'] = [
                'ip'=>$connection->getRemoteIp(),
                'port'=>$connection->getRemotePort(),
                'c_connection_id'=>$event_data['connection']['c_connection_id']
            ];
            Channel\Client::publish('sc_connect'.$config['local_ip'].":".$config['local_port'],$connect_data);
        };

        $connection_to_local->onMessage = function($connection,$data) use($config,$event_data){
            // $message_data['session'] = $_SESSION;
            $message_data['data'] = $data;
            $message_data['connection'] = [
                'ip'=>$connection->getRemoteIp(),
                'port'=>$connection->getRemotePort(),
                'c_connection_id'=>$event_data['connection']['c_connection_id']
            ];

            Channel\Client::publish('sc_message'.$config['local_ip'].":".$config['local_port'],$message_data);
        };

        $connection_to_local->onClose = function($connection) use($event_data,$config){
            // $close_data['session'] = $_SESSION;
            $close_data['connection'] = [
                'ip'=>$connection->getRemoteIp(),
                'port'=>$connection->getRemotePort(),
                'c_connection_id'=>$event_data['connection']['c_connection_id']
            ];

            Channel\Client::publish('sc_close'.$config['local_ip'].":".$config['local_port'],$close_data);
        };

        $connection_to_local->connect();

        $inside_worker->connections[$event_data['connection']['c_connection_id']] = $connection_to_local;

    });

    Channel\Client::on('cs_message'.$config['local_ip'].":".$config['local_port'],function($event_data)use($inside_worker){
        $inside_worker->connections[$event_data['connection']['c_connection_id']]->send($event_data['data']);
    });
    Channel\Client::on('cs_close'.$config['local_ip'].":".$config['local_port'],function($event_data)use($inside_worker){
        $inside_worker->connections[$event_data['connection']['c_connection_id']]->close();
    });

};

Worker::runAll();