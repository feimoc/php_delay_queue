<?php
namespace Queue;

use Config\RabbitMq;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;


class RabbitDelayQueueBase
{
    public $channel = "";
    protected $connection = "";
    protected $queueKey = ""; //队列名称
    protected $exchange = ""; //交换机名称
    protected $queueConf = [];
    protected $arrCurrentConf = [];
    protected $consumerTag = "";
    protected $_durable = false;
    protected $_exchange_durable = false;
    protected $_no_ack = true;
    protected $deadLetterQueueKey = ""; //死信队列名称
    protected $deadLetterExchange = ""; //死信交换机名称
    protected $deadLetterPrefix = 'DLX_'; //死信队列前缀

    /**
     * RabbitDelayQueueBase constructor.
     * @param string $queueName  队列名称
     * @param int $expiration 默认延迟时间:15s 单位:毫秒
     * @param array $options 可选参数
     * @throws \Exception
     */
    public function __construct($queueName, int $expiration = 15000, array $options = [])
    {
        if (method_exists($this, 'getServerConfig')) {
            $this->queueConf = $this->getServerConfig();
        } else {
            throw new \Exception("Func getServerConfig is needed", 1);
        }
        $this->queueKey           = $this->queueConf['info'][$queueName]['queueKey'];
        $this->exchange           = $this->queueConf['info'][$queueName]['exchange'];
        $this->deadLetterQueueKey = $this->deadLetterPrefix . $this->queueConf['info'][$queueName]['queueKey'];
        $this->deadLetterExchange = $this->deadLetterPrefix . $this->queueConf['info'][$queueName]['exchange'];
        $this->expiration         = $expiration;
        if (isset($this->queueConf['info'][$queueName]['durable'])) {
            $this->_durable = $this->queueConf['info'][$queueName]['durable'];
        }
        if (isset($this->queueConf['info'][$queueName]['exchange_durable'])) {
            $this->_exchange_durable = $this->queueConf['info'][$queueName]['exchange_durable'];
        } else {
            $this->_exchange_durable = $this->_durable;
        }
        if (isset($this->queueConf['info'][$queueName]['no_ack'])) {
            $this->_no_ack = $this->queueConf['info'][$queueName]['no_ack'];
        }

        $this->getRandServer();
        $this->connection($options, $expiration);
    }

    public function getServerConfig()
    {
        return RabbitMq::serverConfig();

    }

    public function connection($options, $expiration)
    {
        $default_options  = [
            'connection_timeout' => 1,
            'heartbeat'          => 30,
        ];
        $this->connection = AMQPStreamConnection::create_connection($this->arrCurrentConf, array_merge($default_options, $options));
        $this->channel    = $this->connection->channel();

        $this->channel->exchange_declare($this->deadLetterExchange, 'direct', false, $this->_exchange_durable, false); //死信交换机
        $this->channel->exchange_declare($this->exchange, 'direct', false, $this->_exchange_durable, false); //普通交换机

        $tale = new AMQPTable();
        $tale->set('x-dead-letter-exchange', $this->exchange);
        $tale->set('x-dead-letter-routing-key', $this->exchange);
        $tale->set('x-message-ttl', $this->expiration); //设置过期时间
        $this->channel->queue_declare($this->deadLetterQueueKey, false, $this->_durable, false, false, false, $tale);//死信队列
        $this->channel->queue_bind($this->deadLetterQueueKey, $this->deadLetterExchange, '');


        $this->channel->queue_declare($this->queueKey, false, $this->_durable, false, false, false); //普通队列
        $this->channel->queue_bind($this->queueKey, $this->exchange, $this->exchange);
        $this->channel->basic_qos(null, 1, true);

    }

    public function addOne($arrContent, $expiration = 0)
    {
        $messageBody = json_encode($arrContent);
        $properties  = ['content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT];
        if ($expiration > 0) {
            $properties['expiration'] = $expiration;
        }
        $message = new AMQPMessage(
            $messageBody,
            $properties

        );
        return $this->channel->basic_publish($message, $this->deadLetterExchange);
    }

    public function getOne($funcCallback)
    {
        $this->channel->basic_consume($this->queueKey, $this->consumerTag, false, $this->_no_ack, false, false, $funcCallback);
    }

    public function getQueueReadyCount()
    {
        return $this->channel->queue_declare($this->queueKey, false, $this->_durable, false, false);
    }

    public function startConsume()
    {
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function getRandServer()
    {
        if (!$this->queueConf) {
            $this->queueConf = $this->getServerConfig();
        }
        $serverInfos = $this->queueConf['server'];
        shuffle($serverInfos);

        $this->arrCurrentConf = array_map(function ($item) {
            $item['password'] = $item['pass'];
            unset($item['pass']);
            return $item;
        }, $serverInfos);
    }

    public static function ack($message)
    {
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }

    public static function nack($message)
    {
        $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag']);
    }

    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }


}
