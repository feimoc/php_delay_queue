<?php
namespace Config;

class RabbitMq{
    //rabbitmq 配置
    public static function serverConfig(){
        return [
            'server' => [
                [
                    'host'  => '127.0.0.1',
                    'port'  => '5672',
                    'user'  => 'feimo',//默认gust
                    'pass'  => 'feimo',//默认gust
                    'vhost' => '/',
                ],
            ],

            'info' => [
                //普通队列
                'General' => [
                    'queueKey' => 'general', //队列名称
                    'exchange' => 'general', //交换机名称
                    'durable'  => true,      //持久化
                    'no_ack'   => false,
                ],
                //延时队列
                'Delay' => [
                    'queueKey' => 'discovery', //队列名称
                    'exchange' => 'discovery', //交换机名称
                    'durable'  => true,        //持久化
                    'no_ack'   => false,
                ],

            ]
        ];
    }
}
