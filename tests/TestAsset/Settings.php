<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc\Tests\TestAsset;

/**
 * A site's settings as config['settings'] holds them.
 */
final class Settings
{
    /**
     * A Google tag and the Meta pixel, tracked here, not waiting for consent
     * unless told to.
     *
     * @return array<string, mixed>
     */
    public static function googleAndMeta(bool $wait = false): array
    {
        return [
            'google'     => ['analytics' => ['key' => 'G-ABC12345']],
            'facebook'   => ['analytics' => ['key' => '123456789012345']],
            'extensions' => ['tracking' => ['wait_for_consent' => $wait, 'track_environment' => '1']],
        ];
    }
}
