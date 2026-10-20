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
use function preg_match_all;

/**
 * tracking() built from a site's config and its extensions config files.
 */
#[Group('integration')]
final class TrackingFactoryTest extends TestCase
{
    use TempDirectoryTrait;

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
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function config(array $options): array
    {
        return [
            'settings'          => Settings::googleAndMeta(),
            'contenir_tracking' => ['extensions_config' => $this->directory, ...$options],
        ];
    }

    private function enable(string ...$handles): void
    {
        $this->writeConfig('extensions.global.php', ['extensions' => ['enabled' => $handles]]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function helper(array $options = [], ?NonceProviderInterface $nonces = null): Tracking
    {
        $services = ['config' => $this->config($options)];
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
