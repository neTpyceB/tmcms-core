<?php

namespace TMCms\RabbitMQ;

use PhpAmqpLib\Connection\AMQPConnection;

class Receiver {
    /**
     * @var self
     */
    private static $_instance;

    /**
     * @var AMQPConnection
     */
    private $connection;

    private $callback;

    public static function getInstance() {
        if (!self::$_instance) self::$_instance = new self;

        return self::$_instance;
    }

    private function __construct() {
        $this->connection = Connector::getInstance()->getConnection();
        $this->channel = $this->connection->channel();

        return $this;
    }

    public function setCallback($callback) {
        $this->callback = $callback;

        return $this;
    }

    public function processMessages($callback = NULL) {
        if ($callback) $this->callback = $callback;

        $this->channel->basic_qos(null, 1, null); // Send messages by 1
        $this->channel->basic_consume('task_queue_persistent', '', false, false, false, false, $this->callback);

        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        return $this;
    }
}