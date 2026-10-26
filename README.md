# FreeMagento_Base

[![Packagist](https://img.shields.io/packagist/v/freemagento/module-base.svg)](https://packagist.org/packages/freemagento/module-base) ![Licence](https://img.shields.io/badge/licence-MIT-blue.svg)

The shared foundation every FreeMagento extension depends on. Small on purpose.

## What it does

- **Module registry** – finds installed `FreeMagento_*` modules and their versions (works for `app/code` and `vendor/` installs).
- **Update checks** – every few hours a cron fetches the FreeMagento package feed and compares versions. New releases
  appear as Magento admin-inbox notifications (security releases as critical). `bin/magento freemagento:updates:check` shows the same table on the command line.
- **Admin page** – *FreeMagento › Extensions & updates*: installed packages, latest versions, licence status, "Check now".
- **Licence key** – one key per store (Stores › Configuration › FreeMagento › Base & updates). Only Pro packages use it.
  Free packages never need one and nothing is disabled without one.

## What it sends, and how to stop it

With *Include installed versions in the request* on (the default), the update check POSTs: the store's base domain,
Magento version and edition, PHP major.minor, the licence key if set, and the list of installed FreeMagento packages
with versions. That's all - it lets the feed flag security releases that apply to you and keeps Pro licences in sync.
Turn it off and the check becomes an anonymous GET; turn *Check for updates* off and nothing is sent at all.

## Install

```
composer require freemagento/module-base
bin/magento module:enable FreeMagento_Base
bin/magento setup:upgrade
```

You rarely install it directly - every FreeMagento package requires it.

## For extension authors

Depend on it in `composer.json` (`"freemagento/module-base": "^1.0"`) and `etc/module.xml` (`<module name="FreeMagento_Base"/>` in `<sequence>`).
Keep `name` and `version` in your package's `composer.json`; that's what the registry reads for app/code installs.

## Licence

MIT. Made by [FreeMagento](https://freemagento.co.uk), a Mentalworkz Ltd project.
