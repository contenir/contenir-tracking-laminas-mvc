# contenir/tracking-laminas-mvc

[![Continuous Integration](https://github.com/contenir/contenir-tracking-laminas-mvc/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-tracking-laminas-mvc/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-tracking-laminas-mvc/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-tracking-laminas-mvc)

The Laminas MVC view helper for [contenir/tracking](https://github.com/contenir/contenir-tracking). It writes a
site's analytics and advertising tags into its layout: Google Consent Mode v2 first, then the Google tag, Tag Manager,
the Meta pixel and LinkedIn Insight, each with the page's CSP nonce.

The tags are set in the CMS, by the [Tracking add-on](https://github.com/contenir-extension/tracking). Settings are
read from the site's `config['settings']`, as the CMS writes them, so there is nothing to configure on the site.

## Requirements

- PHP 8.3, 8.4 or 8.5
- laminas/laminas-view 2.30 or later

## Install

```bash
composer require contenir/tracking-laminas-mvc
```

Add `Contenir\Tracking\Laminas\Mvc` to the application's modules (laminas-component-installer offers to).

## Usage

In the layout, put `head()` as early in `<head>` as possible, since Consent Mode has to be set before any tag
loads, and `bodyStart()` straight after `<body>`:

```php
<head>
    <meta charset="utf-8">
    <?= $this->tracking()->head($nonce) ?>
    …
</head>
<body>
    <?= $this->tracking()->bodyStart() ?>
```

This replaces the gtag and Meta pixel snippets layouts print by hand today.

`head()` puts `$nonce` on every script. If the application registers a
`Contenir\Tracking\Laminas\Mvc\NonceProviderInterface` service, `head()` takes the nonce from it when given none.
Without a nonce, the scripts are written without one. `bodyStart()` writes only noscript fallbacks, which need no
nonce, and only when the tags don't wait for consent.

## What it writes, and when

- **Nothing unless the Tracking add-on is enabled** on the site, and the environment tracks: "Track on this
  environment" in the add-on's settings, kept in the environment's local settings.
- **The enabled add-ons** are read from `config/autoload/extensions.global.php` and `extensions.local.php`, as the
  CMS reads them: a list in the local file replaces the global one entirely. Laminas's own config merge would join the
  two lists, so the helper reads the files itself.
- **Gating.** When the Consent add-on is enabled, every script that isn't necessary is written inert
  (`type="text/plain" data-category="analytics|marketing"`) for the banner to switch on. Only the Consent Mode bridge
  runs at once.
- **Waiting for consent.** When the Consent add-on is enabled, the tags wait for consent whatever "Wait for consent"
  says, so only the banner's choice grants anything: Google's defaults are denied, the Meta pixel starts revoked and
  LinkedIn waits.
- **Advanced Consent Mode.** When the Consent add-on's settings choose Google's advanced Consent Mode, the Google tag
  and Tag Manager are left running. They see the denied defaults and send Google only cookieless pings until the
  visitor consents. The Meta pixel and LinkedIn stay gated.

The tags, the consent categories and the `contenir:consent` browser event are described in
[contenir/tracking](https://github.com/contenir/contenir-tracking#consent).

## Configuration

Nothing is needed. To change the defaults:

```php
return [
    'contenir_tracking' => [
        'extensions_config' => 'config/autoload', // where extensions.global.php and .local.php are
        'gate'              => true,              // gate scripts whether or not the Consent add-on is enabled
    ],
];
```

## Licence

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
