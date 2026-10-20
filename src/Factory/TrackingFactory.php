<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc\Factory;

use Contenir\Tracking\Laminas\Mvc\ConsentAddOn;
use Contenir\Tracking\Laminas\Mvc\EnabledAddOns;
use Contenir\Tracking\Laminas\Mvc\NonceProviderInterface;
use Contenir\Tracking\Laminas\Mvc\View\Helper\Tracking;
use Contenir\Tracking\Snippet\HtmlRenderer;
use Contenir\Tracking\Tags;
use Contenir\Tracking\TrackingConfig;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_bool;
use function is_string;

/**
 * Builds tracking() from the site's settings (config['settings'], as the
 * CMS writes them) and the add-ons it has enabled. Nothing is written unless
 * the Tracking add-on is enabled. When the Consent add-on is, scripts are
 * gated for its banner and the tags always wait for consent, so only the
 * banner's choice grants anything; in the Consent add-on's advanced Google
 * Consent Mode, Google's tags are left running, without cookies until the
 * visitor allows them. contenir_tracking in the config can change both:
 *
 *     'contenir_tracking' => [
 *         'extensions_config' => 'config/autoload', // where extensions.global.php and .local.php are
 *         'gate'              => true,              // gate scripts whatever is enabled
 *     ],
 *
 * @api
 */
final class TrackingFactory
{
    public const string HANDLE = 'tracking';

    public const string CONSENT = 'consent';

    public const string EXTENSIONS_CONFIG = 'config/autoload';

    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment The application's config is untyped; each part is checked here.
     */
    public function __invoke(ContainerInterface $container): Tracking
    {
        $config   = $container->get('config');
        $config   = is_array($config) ? $config : [];
        $settings = is_array($config['settings'] ?? null) ? $config['settings'] : [];
        $options  = is_array($config['contenir_tracking'] ?? null) ? $config['contenir_tracking'] : [];

        $directory = $options['extensions_config'] ?? self::EXTENSIONS_CONFIG;
        $enabled   = EnabledAddOns::fromDirectory(is_string($directory) ? $directory : self::EXTENSIONS_CONFIG);
        $gate      = $options['gate'] ?? null;

        $nonces = $container->has(NonceProviderInterface::class)
            ? $container->get(NonceProviderInterface::class)
            : null;

        $gated    = is_bool($gate) ? $gate : $enabled->has(self::CONSENT);
        $tracking = $enabled->has(self::HANDLE) ? TrackingConfig::fromSettings($settings) : new TrackingConfig();

        return new Tracking(
            new Tags($gated ? $tracking->waitingForConsent() : $tracking),
            new HtmlRenderer(
                gate: $gated,
                advancedConsentMode: $gated && ConsentAddOn::advanced($settings),
            ),
            $nonces instanceof NonceProviderInterface ? $nonces : null,
        );
    }
}
