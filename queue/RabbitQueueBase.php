<?php
namespace Queue;
use \Config\RabbitMq;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitQueueBase
{
    public $channel              = "";
    protected $connection        = "";
    protected $queueKey          = "";
    protected $exchange          = "";
    protected $queueConf         = [];
    protected $arrCurrentConf    = [];
    protected $consumerTag       = "";
    protected $_durable          = false;
    protected $_exchange_durable = false;
    protected $_no_ack           = true;

    /**
     * RabbitQueueBase constructor.
     * @param string $queueName 队列名称
     * @param array  $options 可选参数
     * @throws \Exception
     */
    public function __construct($queueName, array $options = [])
    {
        if (method_exists($this, 'getServerConfig')) {
            $this->queueConf = $this->getServerConfig();
        } else {
            throw new \Exception("Func getServerConfig is needed", 1);
        }
        $this->queueKey = $this->queueConf['info'][$queueName]['queueKey'];
        $this->exchange = $this->queueConf['info'][$queueName]['exchange'];
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
        $this->connection($options);
    }

    public function getServerConfig()
    {
        return RabbitMq::serverConfig();
    }

    public function connection($options)
    {
        $default_options  = [
            'connection_timeout' => 1,
            'heartbeat'          => 30,
        ];
        $this->connection = AMQPStreamConnection::create_connection($this->arrCurrentConf, array_merge($default_options, $options));
        $this->channel    = $this->connection->channel();
        $this->channel->exchange_declare($this->exchange, 'fanout', false, $this->_exchange_durable, false);
        $this->channel->queue_declare($this->queueKey, false, $this->_durable, false, false);
        $this->channel->basic_qos(null, 1, true);
        $this->channel->queue_bind($this->queueKey, $this->exchange);
    }

    public function addOne($arrContent)
    {
        $messageBody = json_encode($arrContent);
        $message     = new AMQPMessage(
            $messageBody,
            ['content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        return $this->channel->basic_publish($message, $this->exchange);
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