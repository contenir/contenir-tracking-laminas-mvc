<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc\Tests\Unit;

use Contenir\Tracking\Laminas\Mvc\ConfigProvider;
use Contenir\Tracking\Laminas\Mvc\Factory\TrackingFactory;
use Contenir\Tracking\Laminas\Mvc\Module;
use Contenir\Tracking\Laminas\Mvc\View\Helper\Tracking;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function registersTheViewHelperAsTracking(): void
    {
        static::assertSame(
            [
                'view_helpers' => [
                    'aliases'   => ['tracking' => Tracking::class, 'Tracking' => Tracking::class],
                    'factories' => [Tracking::class => TrackingFactory::class],
                ],
            ],
            (new ConfigProvider())(),
        );
    }

    #[Test]
    public function servesTheProvidersConfigurationAsTheModules(): void
    {
        static::assertSame((new ConfigProvider())(), (new Module())->getConfig());
    }
}
