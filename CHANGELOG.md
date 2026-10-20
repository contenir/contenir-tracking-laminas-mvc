# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased] - 2026-10-21

### Changed

- Requires contenir/tracking `^0.1` from Packagist, where it required `0.1.x-dev` through a `vcs` repository entry.
  A project at stable stability can now install the adapter.

## [0.1.0] - 2026-10-20

### Added

- The `tracking()` view helper. `head()` writes Consent Mode and the `contenir:consent` bridge first, then the
  Google tag, Tag Manager, the Meta pixel and LinkedIn Insight, with the page's CSP nonce on every script.
  `bodyStart()` writes the noscript fallbacks.
- The helper reads the site's settings from `config['settings']`, under the keys the CMS saves, and writes nothing
  unless the Tracking add-on is enabled. The enabled list is read local over global, as the CMS reads it
  (`EnabledAddOns`). Scripts are gated for a consent banner when the Consent add-on is enabled, or when
  `contenir_tracking.gate` says so.
- `NonceProviderInterface`, so an application can give `head()` the page's nonce without passing it in.
