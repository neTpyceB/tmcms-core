<?php

namespace neTpyceB\TMCms\RabbitMQ;

use neTpyceB\TMCms\Traits\singletonInstanceTrait;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;

class Connector
{
    use singletonInstanceTrait;

    private $connection_host = 'localhost';
    private $connection_port = 5672;
    private $connection_user = 'guest';
    private $connection_password = 'guest';

    /**
     * @var AMQPConnection
     */
    private $connection;
    /**
     * @var AMQPChannel
     */
    private $channel;

    private function __construct()
    {
        $this->connection = new AMQPConnection($this->connection_host, $this->connection_port, $this->connection_user, $this->connection_password);
        $this->channel = $this->connection->channel();

        $this->channel->queue_declare('task_queue_persistent', false, true, false, false);

        return $this;
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * @return AMQPConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}