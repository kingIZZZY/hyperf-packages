<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Foundation\Bootstrap;

use Hyperf\Contract\ConfigInterface;
use SwooleTW\Hyperf\Foundation\Contracts\Application as ApplicationContract;

class RegisterProviders
{
    /**
     * Register App Providers.
     */
    public function bootstrap(ApplicationContract $app): void
    {
        $providers = $app->get(ConfigInterface::class)
            ->get('app.providers', []);

        foreach ($providers as $providerClass) {
            $app->register($providerClass);
        }
    }
}
