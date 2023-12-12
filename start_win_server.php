<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/common.php';

/** 为了简化windows版本的操作，写了这个文件 */
try {
    $config = get_config();
} catch (\Exception $e) {
    echo "error:{$e}\n";
    exit;
}
/** 首先清理上一次的文件 */


$file = [];
/** 创建多个代理服务 */
if(isset($config['nat_list'])){
    foreach ($config['nat_list'] as $n_key => $n_value) {
       $file[]  = make_server_file($n_value);
    }
}else{
    /** windows 环境 只创建一个代理 */
    $file[] = build_server_woker($config);
}


$cmd = 'php ';
foreach ($file as $a=>$b){
    $cmd .=' '.$b;
}

echo  system($cmd);
/**
 * 生成服务端文件
 * @param $config
 * @return string
 */
function make_server_file($config){
    $content  = file_get_contents(__DIR__.'/server_for_win.php');
    $content = str_replace('#config#',json_encode($config),$content);
    $file = __DIR__.'/windows_server/'.rand(1,10000).'_server.php';
    touch($file);
    file_put_contents($file,$content);
    return $file;
}

function clean_temp_server()
{
    $temp_dir_list = scandir(__DIR__."/windows_server/");

    foreach ($temp_dir_list as $t_key => $t_value) {
        if($t_value != '.' && $t_value != '..' ){
            unlink(__DIR__."/windows_server/".$t_value);
        }
    }

}