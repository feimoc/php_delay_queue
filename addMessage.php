<?php
require_once __DIR__ . '/vendor/autoload.php';

use Queue\RabbitQueueBase;
use Queue\RabbitDelayQueueBase;

//新增普通队列消息
function addMessage()
{
    $queue = new RabbitQueueBase('General');
    //插入10条测试数据
    for ($i = 1; $i <= 10; $i++) {
        $queue->addOne(["id" => $i, 'message' => "php是世界上最好的语言", 'time' => date('Y-m-d H:i:s')]);
    }
    echo "add general message success" . PHP_EOL;
}

//新增延时队列消息
function delayMessage()
{
    //设置队列延迟时间为10s，设置完毕之后该队列延迟时间不可修改
    //想要设置不同的延迟时间可以在config/RabbitMq新增一个队列配置，参考General、Delay
    //也可以针对每个消息设置延迟时间，消息延迟时间不能大于队列延迟时间，否则已队列延迟时间为主
    $delayQueue = new RabbitDelayQueueBase('Delay', 10000);
    //插入10条测试数据
    for ($i = 1; $i <= 10; $i++) {
        $delayQueue->addOne(["id" => $i, 'message' => "php是世界上最好的语言", 'time' => date('Y-m-d H:i:s')]);
        //设置消息延迟时间为5s
        //$delayQueue->addOne(["id" => $i, 'message' => "php是世界上最好的语言", 'time' => date('Y-m-d H:i:s')], 5000);

    }
    echo "add delay message success" . PHP_EOL;
}

addMessage();

delayMessage();

