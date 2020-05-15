<?php
require_once __DIR__ . '/vendor/autoload.php';
use Queue\RabbitDelayQueueBase;


function handle()
{
    $rabbitMq = new RabbitDelayQueueBase('Delay', 10000);
    echo 'Waiting for message. To exit press CTRL+C ' . PHP_EOL;
    $rabbitMq->getOne(function ($message) {
        $handleData = json_decode($message->body, true);
        print_r($handleData);
        // $this->something($handleData);
        RabbitDelayQueueBase::ack($message);
    });

    $rabbitMq->startConsume();

}

/**TODO:Something
 * @param $handleData
 */
function something($handleData)
{

}

handle();
