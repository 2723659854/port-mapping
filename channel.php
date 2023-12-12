<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/common.php';

use Workerman\Worker;

try {
    $config = get_config();
} catch (\Exception $e) {
    echo "error:{$e}\n";
    exit;
}

/** 首选创建一个 channel 服务器 */
$channel_server = new Channel\Server('0.0.0.0', $config['channel_port']);
/** 为了简化windows操作 所以谢了这个文件 */
Worker::runAll();