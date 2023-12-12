<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/common.php';

use Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;


try{
    $config = get_config();
}catch(\Exception $e){
    echo "error:{$e}\n";
}

if(isset($config['nat_list'])){

    clean_temp_cient();

    foreach ($config['nat_list'] as $n_key => $n_value) {
        $unique_key = $n_key;
        $nat_client_list['nat_client_worker_'.$n_key] = build_client_for_win_woker($n_value,$n_key);
    }
    $cmd = build_cmd();
    echo system($cmd);
}else{
    $worker = build_client_woker($config);
}

Worker::runAll();


function build_client_woker($config){
    
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
        if($inside_worker->connections[$event_data['connection']['c_connection_id']]){
            $inside_worker->connections[$event_data['connection']['c_connection_id']]->close();
        }
    });

    };


}


function build_client_for_win_woker($config,$key)
{
    $tpl_code = file_get_contents(__DIR__."/temp_client_for_win/tpl.php");
    $tpl_code = str_replace('#config#',json_encode($config),$tpl_code);
    file_put_contents(__DIR__."/temp_client_for_win/temp_client_".$key.".php",$tpl_code);
}

function clean_temp_cient()
{
    $temp_dir_list = scandir(__DIR__."/temp_client_for_win/");

    foreach ($temp_dir_list as $t_key => $t_value) {
        if($t_value != '.' && $t_value != '..' && $t_value != 'tpl.php' ){
            unlink(__DIR__."/temp_client_for_win/".$t_value);
        }
    }

}

function build_cmd()
{
    $cmd = "php ";
    $temp_dir_list = scandir(__DIR__."/temp_client_for_win/");

    foreach ($temp_dir_list as $t_key => $t_value) {
        if($t_value != '.' && $t_value != '..' && $t_value != 'tpl.php' ){
            $cmd .= __DIR__."/temp_client_for_win/".$t_value." ";
        }
    }

    return $cmd;
}