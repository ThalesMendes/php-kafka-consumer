<?php

namespace Kafka\Consumer\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Kafka\Consumer\Contracts\Consumer;
use Kafka\Consumer\Exceptions\InvalidCommitException;
use Kafka\Consumer\Exceptions\InvalidConsumerException;
use Kafka\Consumer\Validators\Commands\PhpKafkaConsumer\Validator;

class PhpKafkaConsumerCommand extends Command
{
    protected $signature = 'arquivei:php-kafka-consumer {--topic=*} {--consumer=} {--groupId=} {--commit=} {--dlq=} {--maxMessage=}';
    protected $description = 'An Apache Kafka consumer in PHP';

    private $dlq;
    private $topics;
    private $config;
    private $groupId;
    private $maxMessage;

    public function __construct()
    {
        parent::__construct();
        $this->config = config('php-kafka-consumer');
    }

    public function handle()
    {
        (new Validator())->validateOptions($this->options);
        $consumer = $this->option('consumer');

        $this->dlq = $this->option('dlq');
        $this->topics = $this->option('topic');
        $this->groupId = $this->option('groupId');
        $this->maxMessage = (int)$this->option('maxMessage');

        $config = new \Kafka\Consumer\Entities\Config(
            new \Kafka\Consumer\Entities\Config\Sasl(
                $this->config['sasl']['username'],
                $this->config['sasl']['password'],
                $this->config['sasl']['mechanisms']
            ),
            $this->getTopics(),
            $this->config['broker'],
            $this->option('commit'),
            $this->getGroupId(),
            new $consumer(),
            $this->config['securityProtocol'],
            $this->getDlq(),
            $this->getMaxMessage()
        );

        (new \Kafka\Consumer\Consumer($config))->consume();
    }

    private function getTopics(): array
    {
        return (is_array($this->topics) && !empty($this->topics)) ? $this->topics : [];
    }

    private function getGroupId(): string
    {
        return (is_string($this->groupId) && strlen($this->groupId) > 1) ? $this->groupId : $this->config['groupId'];
    }

    private function getMaxMessage(): int
    {
        return (is_int($this->maxMessage) && $this->maxMessage >= 1) ? $this->maxMessage : -1;
    }

    private function getDlq(): ?string
    {
        return (is_string($this->dlq) && strlen($this->dlq) > 1) ? $this->dlq : null;
    }
}
