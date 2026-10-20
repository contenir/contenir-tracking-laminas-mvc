<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc\Tests\Integration;

use Contenir\Tracking\Laminas\Mvc\Factory\TrackingFactory;
use Contenir\Tracking\Laminas\Mvc\NonceProviderInterface;
use Contenir\Tracking\Laminas\Mvc\Tests\TestAsset\ArrayContainer;
use Contenir\Tracking\Laminas\Mvc\Tests\TestAsset\FixedNonce;
use Contenir\Tracking\Laminas\Mvc\Tests\TestAsset\Settings;
use Contenir\Tracking\Laminas\Mvc\Tests\Trait\TempDirectoryTrait;
use Contenir\Tracking\Laminas\Mvc\View\Helper\Tracking;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function chdir;
use function getcwd;
use function preg_match;
use function preg_match_all;

/**
 * tracking() built from a site's config and its extensions config files.
 */
#[Group('integration')]
final class TrackingFactoryTest extends TestCase
{
    use TempDirectoryTrait;

    #[Test]
    public function gatesGooglesTagsInBasicModeOrWhenTheSettingIsMissing(): void
    {
        $this->enable('tracking', 'consent');
        $basic                          = Settings::googleAndMeta();
        $basic['extensions']['consent'] = 'advanced';

        static::assertSame(
            '<script type="text/plain" data-category="analytics" src',
            $this->openingTags($this->helper(settings: $basic)->head())[1],
        );
    }

    #[Test]
    public function gatesScriptsWhenTheConsentAddOnIsEnabled(): void
    {
        $this->enable('tracking', 'consent');

        static::assertSame(
            [
                '<script>',
                '<script type="text/plain" data-category="analytics" src',
                '<script type="text/plain" data-category="analytics">',
                '<script type="text/plain" data-category="marketing">',
            ],
            $this->openingTags($this->helper()->head()),
        );
    }

    #[Test]
    public function gatesScriptsWhenTold(): void
    {
        $this->enable('tracking');

        static::assertSame(
            [
                '<script>',
                '<script type="text/plain" data-category="analytics" src',
                '<script type="text/plain" data-category="analytics">',
                '<script type="text/plain" data-category="marketing">',
            ],
            $this->openingTags($this->helper(['gate' => true])->head()),
        );
    }

    #[Test]
    public function ignoresANonceServiceOfTheWrongKind(): void
    {
        $this->enable('tracking');
        $helper = (new TrackingFactory())(new ArrayContainer([
            'config'                      => $this->config([]),
            NonceProviderInterface::class => 'not a provider',
        ]));

        static::assertSame('<script>', $this->openingTags($helper->head())[0] ?? null);
    }

    #[Test]
    public function leavesGooglesTagsRunningInTheConsentAddOnsAdvancedMode(): void
    {
        $this->enable('tracking', 'consent');

        static::assertSame(
            [
                '<script>',
                '<script src',
                '<script>',
                '<script type="text/plain" data-category="marketing">',
            ],
            $this->openingTags($this->helper(settings: $this->advanced())->head()),
        );
    }

    #[Test]
    public function readsAdvancedModeOnlyWhenTheConsentBannerGates(): void
    {
        $this->enable('tracking');

        static::assertSame(
            ['<script>', '<script src', '<script>', '<script>'],
            $this->openingTags($this->helper(settings: $this->advanced())->head()),
        );
    }

    #[Test]
    public function readsTheDefaultDirectoryWhenTheConfiguredOneIsNotText(): void
    {
        $this->enable('tracking');
        $helper = (new TrackingFactory())(new ArrayContainer([
            'config' => [
                'settings'          => Settings::googleAndMeta(),
                'contenir_tracking' => ['extensions_config' => ['not', 'a', 'path']],
            ],
        ]));

        static::assertSame('', $helper->head());
    }

    #[Test]
    public function readsTheExtensionsConfigFromTheSitesConfigDirectoryByDefault(): void
    {
        $this->writeConfig('config/autoload/extensions.global.php', ['extensions' => ['enabled' => ['tracking']]]);
        $container = new ArrayContainer(['config' => ['settings' => Settings::googleAndMeta()]]);

        $cwd = (string) getcwd();
        chdir($this->directory);
        try {
            $head = (new TrackingFactory())($container)->head();
        } finally {
            chdir($cwd);
        }

        static::assertCount(4, $this->openingTags($head));
    }

    #[Test]
    public function readsTheSitesSettingsAndNonceProvider(): void
    {
        $this->enable('tracking');

        static::assertSame(
            ['<script nonce="abc">', '<script src', '<script nonce="abc">', '<script nonce="abc">'],
            $this->openingTags($this->helper([], new FixedNonce('abc'))->head()),
        );
    }

    #[Test]
    public function waitsForConsentBehindTheConsentBannerWhateverTheSettingsSay(): void
    {
        $this->enable('tracking', 'consent');

        $head = $this->helper()->head();

        static::assertSame(
            [1, 0, 1],
            [
                preg_match('/"ad_storage":"denied"/', $head),
                preg_match('/"ad_storage":"granted"/', $head),
                preg_match("/state && state\\.ads \\? 'grant' : 'revoke'/", $head),
            ],
        );
    }

    #[Test]
    public function writesNothingWhenTheLocalListLeavesTrackingOut(): void
    {
        $this->enable('tracking');
        $this->writeConfig('extensions.local.php', ['extensions' => ['enabled' => ['seo']]]);

        static::assertSame('', $this->helper()->head());
    }

    #[Test]
    public function writesNothingWhenTrackingIsNotEnabled(): void
    {
        static::assertSame('', $this->helper()->head());
    }

    #[Test]
    public function writesNothingWithoutConfig(): void
    {
        static::assertSame('', (new TrackingFactory())(new ArrayContainer(['config' => null]))->head());
    }

    #[Test]
    public function writesNothingWithoutSettings(): void
    {
        $this->enable('tracking');
        $helper = (new TrackingFactory())(new ArrayContainer([
            'config' => [
                'settings'          => 'not settings',
                'contenir_tracking' => ['extensions_config' => $this->directory, 'gate' => 'yes'],
            ],
        ]));

        static::assertSame('', $helper->head());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->makeTempDirectory();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->removeTempDirectory();
    }

    /**
     * The site's settings with the Consent add-on in advanced Google Consent
     * Mode.
     *
     * @return array<string, mixed>
     */
    private function advanced(): array
    {
        $settings                                                 = Settings::googleAndMeta();
        $settings['extensions']['consent']['google_consent_mode'] = 'advanced';

        return $settings;
    }

    /**
     * @param array<string, mixed>  $options
     * @param ?array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    private function config(array $options, ?array $settings = null): array
    {
        return [
            'settings'          => $settings ?? Settings::googleAndMeta(),
            'contenir_tracking' => ['extensions_config' => $this->directory, ...$options],
        ];
    }

    private function enable(string ...$handles): void
    {
        $this->writeConfig('extensions.global.php', ['extensions' => ['enabled' => $handles]]);
    }

    /**
     * @param array<string, mixed>  $options
     * @param ?array<string, mixed> $settings
     */
    private function helper(
        array $options = [],
        ?NonceProviderInterface $nonces = null,
        ?array $settings = null,
    ): Tracking {
        $services = ['config' => $this->config($options, $settings)];
        if (null !== $nonces) {
            $services[NonceProviderInterface::class] = $nonces;
        }

        return (new TrackingFactory())(new ArrayContainer($services));
    }

    /**
     * Each script's opening tag, up to its src when it has one.
     *
     * @return list<string>
     */
    private function openingTags(string $html): array
    {
        preg_match_all('/<script[^>]*?(?: src|>)/', $html, $matches);

        return $matches[0];
    }
}
