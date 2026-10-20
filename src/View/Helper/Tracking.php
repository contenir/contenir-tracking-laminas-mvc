<?php

declare(strict_types=1);

namespace Contenir\Tracking\Laminas\Mvc\View\Helper;

use Contenir\Tracking\Laminas\Mvc\NonceProviderInterface;
use Contenir\Tracking\Snippet\HtmlRenderer;
use Contenir\Tracking\Snippet\Placement;
use Contenir\Tracking\Tags;

/**
 * `$this->tracking()` in a layout: the site's tags, written where each
 * belongs. Put head() as early in <head> as possible, since Consent Mode has
 * to be set before any tag loads, and bodyStart() straight after <body>.
 *
 *     <?= $this->tracking()->head() ?>
 *     …
 *     <body>
 *     <?= $this->tracking()->bodyStart() ?>
 *
 * head() takes the page's CSP nonce, or uses the NonceProviderInterface the
 * application registered.
 *
 * @api
 */
final readonly class Tracking
{
    public function __construct(
        private Tags $tags,
        private HtmlRenderer $renderer,
        private ?NonceProviderInterface $nonces = null,
    ) {}

    /**
     * The noscript fallbacks for visitors without scripts, written only
     * when the tags don't wait for consent. They aren't scripts, so they
     * need no nonce.
     */
    public function bodyStart(): string
    {
        return $this->renderer->render($this->tags->at(Placement::BodyStart));
    }

    /**
     * Consent Mode and the contenir:consent bridge, then the Google tag,
     * Tag Manager, the Meta pixel and LinkedIn Insight.
     */
    public function head(?string $nonce = null): string
    {
        return $this->renderer->render($this->tags->at(Placement::Head), $nonce ?? $this->nonces?->nonce());
    }

    public function __invoke(): self
    {
        return $this;
    }
}
