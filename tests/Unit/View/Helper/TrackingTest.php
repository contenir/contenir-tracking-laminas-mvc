<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Tracking\Laminas\Mvc\Tests\TestAsset\FixedNonce;
use Contenir\Tracking\Laminas\Mvc\Tests\TestAsset\Settings;
use Contenir\Tracking\Laminas\Mvc\View\Helper\Tracking;
use Contenir\Tracking\Snippet\HtmlRenderer;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Tags;
use Contenir\Tracking\TrackingConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TrackingTest extends TestCase
{
    #[Test]
    public function givesItselfToTheTemplate(): void
    {
        $helper = new Tracking($this->tags(), new HtmlRenderer());

        static::assertSame($helper, $helper());
    }

    #[Test]
    public function prefersTheNonceItIsGiven(): void
    {
        $helper = new Tracking($this->tags(), new HtmlRenderer(), new FixedNonce('from-provider'));

        static::assertSame(
            (new HtmlRenderer())->render($this->tags()->at(Placement::Head), 'given'),
            $helper->head('given'),
        );
    }

    #[Test]
    public function writesTheFallbacksAtTheStartOfTheBody(): void
    {
        $helper = new Tracking($this->tags(), new HtmlRenderer(), new FixedNonce('n'));

        static::assertSame(
            '<noscript><img height="1" width="1" style="display:none" alt="" '
                . 'src="https://www.facebook.com/tr?ev=PageView&amp;noscript=1&amp;id=123456789012345"></noscript>',
            $helper->bodyStart(),
        );
    }

    #[Test]
    public function writesTheHeadTagsWithoutANonceWhenThereIsNone(): void
    {
        $helper = new Tracking($this->tags(), new HtmlRenderer(gate: true));

        static::assertSame(
            (new HtmlRenderer(gate: true))->render($this->tags()->at(Placement::Head)),
            $helper->head(),
        );
    }

    #[Test]
    public function writesTheHeadTagsWithTheProvidersNonce(): void
    {
        $helper = new Tracking($this->tags(), new HtmlRenderer(), new FixedNonce('n0nce'));

        static::assertSame(
            (new HtmlRenderer())->render($this->tags()->at(Placement::Head), 'n0nce'),
            $helper->head(),
        );
    }

    private function tags(): Tags
    {
        return new Tags(TrackingConfig::fromSettings(Settings::googleAndMeta()));
    }
}
