<?php

/**
 * 获取配置
 * @return mixed
 * @throws Exception
 */
function get_config()
{
    $config = require_once __DIR__.'/config/config.php';
    $config = check_config($config);
    $config = check_config_port($config);
    return $config;
}


/**
 * check_config_port
 * 检查端口是否重复
 * @param mixed $config 
 * @return mixed 
 */
function check_config_port($config)
{
    
    $config = check_config($config);
    
    if(isset($config['nat_list'])){

        $local_used_host_list = [];
        $server_used_host_list = [];
        
        foreach ($config['nat_list'] as $c_key => $c_value) {
            $local_host = "{$c_value['local_port']}:{$c_value['local_port']}";
            if(!in_array($local_host,$local_used_host_list)){
                $local_used_host_list[] = $local_host;
            }else{
                throw new \Exception("客户端转发地址配置项重复:{$local_host}");
            }

            $server_host = "{$c_value['server_port']}:{$c_value['server_port']}";
            if(!in_array($server_host,$server_used_host_list)){
                $server_used_host_list[] = $server_host;
            }else{
                throw new \Exception("服务端转发地址配置项重复:{$local_host}");
            }
        }
    }

    return $config;

}

/**
 * check_config
 * 检查配置完整性
 * @param mixed $config 
 * @return mixed 
 */
function check_config($config){

    //必选配置
    $tartget_key_array = [
        'local_ip'=>'',
        'local_port'=>'',
        'server_ip'=>'',
        'server_port'=>'',
    ];



    // 是否设置多个服务
    if(isset($config['nat_list'])){

        foreach ($config['nat_list'] as $c_key => $c_value) {
            
            // nat_list里没有设置但是应当设置的key
            $not_set_key_list = array_diff_key($tartget_key_array,$c_value);
            
            foreach ($not_set_key_list as $n_key => $n_value) {
                if(!isset($config[$n_key])){
                    // nat_list没有,一级配置也没有的设置,错误!
                    throw new \Exception("一级和二级配置缺少配置项:{$n_key}.只有一级配置完整,二级配置项才可以省略.");
                }else{
                    //把总设置赋给nat_list的设置项里
                    $config['nat_list'][$c_key][$n_key] = $config[$n_key];
                }

                if(!isset($config['nat_list'][$c_key]['name'])){
                    $config['nat_list'][$c_key]['name'] = $config['nat_list'][$c_key]['local_port']."<->".$config['nat_list'][$c_key]['server_port'];
                }
                if(!isset($config['nat_list'][$c_key]['password'])){
                    $config['nat_list'][$c_key]['password'] = "phpnb";
                }
                if(!isset($config['nat_list'][$c_key]['channel_port'])){
                    $config['nat_list'][$c_key]['channel_port'] = 2206;
                }

            }
        }
    }else{
        // 如果只是单个服务,那么一级配置一定要齐全
        foreach ($tartget_key_array as $k_key => $k_value) {
            if(!isset($config[$k_key])){
                throw new \Exception('未配置选项:'.$k_key.".windows下必须配齐所有一级配置.");
            }
        }
        if(!isset($config['name'])){
            $config['name'] = $config['local_port']."<->".$config['server_port'];
        }
        if(!isset($config['password'])){
            $config['password'] = "phpnb";
        }
        if(!isset($config['channel_port'])){
            $config['channel_port'] = "2206";
        }
    }

    return $config;

}

/**
 * 检查端口是否可以被绑定
 * @author flynetcn
 */
function check_port_bindable($host, $port, &$errno=null, &$errstr=null)
{
  $socket = stream_socket_server("tcp://$host:$port", $errno, $errstr);
  if (!$socket) {
    return false;
  }
  fclose($socket);
  unset($socket);
  return true;
}

/**
 * 是否windows环境
 * @return bool
 */
function is_win()
{
    if (DIRECTORY_SEPARATOR === '\\') {
        return true;
    }else{
        return false;
    }
}