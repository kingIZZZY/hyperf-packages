<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Queue\Connectors;

use Hyperf\Database\ConnectionResolverInterface;
use SwooleTW\Hyperf\Queue\Contracts\Queue;
use SwooleTW\Hyperf\Queue\DatabaseQueue;

class DatabaseConnector implements ConnectorInterface
{
    /**
     * Create a new connector instance.
     */
    public function __construct(
        protected ConnectionResolverInterface $connections
    ) {
    }

    /**
     * Establish a queue connection.
     */
    public function connect(array $config): Queue
    {
        return new DatabaseQueue(
            $this->connections,
            $config['connection'] ?? null,
            $config['table'],
            $config['queue'],
            $config['retry_after'] ?? 60,
            $config['after_commit'] ?? false
        );
    }
}