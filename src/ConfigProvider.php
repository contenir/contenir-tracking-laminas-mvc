<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc;

use Contenir\Tracking\Laminas\Mvc\Factory\TrackingFactory;
use Contenir\Tracking\Laminas\Mvc\View\Helper\Tracking;

/**
 * The view helper the module registers, as tracking() and Tracking().
 *
 * @api
 */
final class ConfigProvider
{
    /**
     * @return array<string, array<string, array<string, string>>>
     */
    public function __invoke(): array
    {
        return [
            'view_helpers' => [
                'aliases'   => [
                    'tracking' => Tracking::class,
                    'Tracking' => Tracking::class,
                ],
                'factories' => [
                    Tracking::class => TrackingFactory::class,
                ],
            ],
        ];
    }
}
