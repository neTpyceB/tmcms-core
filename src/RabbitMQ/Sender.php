<?php

namespace TMCms\RabbitMQ;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Sender {
    /**
     * @var self
     */
    private static $_instance;

    /**
     * @var AMQPConnection
     */
    private $connection;

    public static function getInstance() {
        if (!self::$_instance) self::$_instance = new self;

        return self::$_instance;
    }

    private function __construct() {
        $this->connection = Connector::getInstance()->getConnection();
        $this->channel = $this->connection->channel();

        return $this;
    }

    /**
     * @param string $class_name
     * @param mixed $params
     * @return $this
     */
    public function runTask($class_name, $params = array()) {
        $msg = new AMQPMessage(
            serialize(array(
                'class' => $class_name,
                'params' => $params
            )),
            array('delivery_mode' => 2) // Persistent
        );
        $this->channel->basic_publish($msg, '', 'task_queue_persistent');

        return $this;
    }
}