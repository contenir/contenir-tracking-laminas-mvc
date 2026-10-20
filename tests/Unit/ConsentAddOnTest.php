<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc\Tests\Unit;

use Contenir\Tracking\Laminas\Mvc\ConsentAddOn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConsentAddOnTest extends TestCase
{
    /**
     * @return array<string, array{array<array-key, mixed>, bool}>
     */
    public static function settings(): array
    {
        return [
            'advanced'                => [['extensions' => ['consent' => ['google_consent_mode' => 'advanced']]], true],
            'basic'                   => [['extensions' => ['consent' => ['google_consent_mode' => 'basic']]], false],
            'no mode'                 => [['extensions' => ['consent' => []]], false],
            'consent not an array'    => [['extensions' => ['consent' => 'advanced']], false],
            'extensions not an array' => [['extensions' => 'consent'], false],
            'nothing'                 => [[], false],
        ];
    }

    #[Test]
    #[DataProvider('settings')]
    public function readsWhetherTheConsentAddOnAsksForAdvancedConsentMode(array $settings, bool $advanced): void
    {
        static::assertSame($advanced, ConsentAddOn::advanced($settings));
    }
}
