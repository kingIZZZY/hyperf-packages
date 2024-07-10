<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Foundation\Console;

use Psr\Container\ContainerInterface;
use SwooleTW\Hyperf\Foundation\Console\Contracts\Kernel as KernelContract;

class ApplicationFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $kernel = $container->get(KernelContract::class);
        $kernel->bootstrap();

        return $kernel;
    }
}
