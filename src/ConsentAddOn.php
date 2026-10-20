<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc;

use function is_array;

/**
 * What the Consent add-on's settings say about the tags, read from the
 * site's settings by the keys contenir/consent's SettingKeys names, so
 * neither package needs the other.
 *
 * @api
 */
final class ConsentAddOn
{
    /**
     * Where the Consent add-on keeps Google Consent Mode's setting: basic or
     * advanced.
     */
    public const array GOOGLE_CONSENT_MODE = ['extensions', 'consent', 'google_consent_mode'];

    /**
     * Whether the settings ask for Google's advanced Consent Mode, so
     * Google's tags run before visitors consent, without cookies.
     *
     * @param array<array-key, mixed> $settings
     *
     * @mago-expect analysis:mixed-assignment Settings are untyped; each level is checked here.
     */
    public static function advanced(array $settings): bool
    {
        $value = $settings;
        foreach (self::GOOGLE_CONSENT_MODE as $segment) {
            $value = is_array($value) ? $value[$segment] ?? null : null;
        }

        return 'advanced' === $value;
    }
}
