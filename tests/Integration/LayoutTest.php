<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc\Tests\Integration;

use Contenir\Tracking\Laminas\Mvc\Module;
use Contenir\Tracking\Laminas\Mvc\Tests\TestAsset\Settings;
use Contenir\Tracking\Laminas\Mvc\Tests\Trait\TempDirectoryTrait;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplatePathStack;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function preg_match;

/**
 * A layout calling tracking() through laminas-view, with the module's
 * configuration.
 */
#[Group('integration')]
final class LayoutTest extends TestCase
{
    use TempDirectoryTrait;

    #[Test]
    public function writesTheTagsInTheHeadAndTheFallbacksAfterTheBodyOpens(): void
    {
        $this->writeConfig('extensions.global.php', ['extensions' => ['enabled' => ['tracking']]]);

        $html = $this->renderer()->render('layout');

        static::assertSame(
            1,
            preg_match(
                '#<head>\n<script nonce="page-nonce">window\.dataLayer.*gtag/js\?id=G-ABC12345.*fbq.*</script></head>\n'
                    . '<body>\n<noscript><img [^>]*id=123456789012345"></noscript></body>#s',
                $html,
            ),
        );
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

    private function renderer(): PhpRenderer
    {
        $services = new ServiceManager();
        $services->setService('config', [
            'settings'          => Settings::googleAndMeta(),
            'contenir_tracking' => ['extensions_config' => $this->directory],
        ]);

        /** @var array{view_helpers: array<string, mixed>} $config */
        $config   = (new Module())->getConfig();
        $renderer = new PhpRenderer();
        $renderer->setHelperPluginManager(new HelperPluginManager($services, $config['view_helpers']));
        $renderer->setResolver(new TemplatePathStack(['script_paths' => [__DIR__ . '/../TestAsset/view']]));

        return $renderer;
    }
}
