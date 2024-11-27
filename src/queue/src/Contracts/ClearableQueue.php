<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Queue\Contracts;

interface ClearableQueue
{
    /**
     * Delete all of the jobs from the queue.
     */
    public function clear(string $queue): int;
}