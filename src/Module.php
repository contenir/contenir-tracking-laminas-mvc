<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc;

/**
 * Registers the tracking() view helper with a Laminas MVC application.
 *
 * @api
 */
final class Module
{
    /**
     * @return array<string, array<string, array<string, string>>>
     */
    public function getConfig(): array
    {
        return (new ConfigProvider())();
    }
}
