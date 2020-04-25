# Roundcube Plugin: Cloud View

[![Packagist](https://img.shields.io/packagist/dt/jfcherng-roundcube/cloudview?style=flat-square)](https://packagist.org/packages/jfcherng-roundcube/cloudview)
[![Packagist Version](https://img.shields.io/packagist/v/jfcherng-roundcube/cloudview?style=flat-square)](https://packagist.org/packages/jfcherng-roundcube/cloudview)
[![Project license](https://img.shields.io/github/license/jfcherng-roundcube/plugin-cloudview?style=flat-square)](https://github.com/jfcherng-roundcube/plugin-cloudview/blob/master/LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/jfcherng-roundcube/plugin-cloudview?style=flat-square&logo=github)](https://github.com/jfcherng-roundcube/plugin-cloudview/stargazers)
[![Donate to this project using Paypal](https://img.shields.io/badge/paypal-donate-blue.svg?style=flat-square&logo=paypal)](https://www.paypal.me/jfcherng/5usd)

A Roundcube plugin which lets you directly view mail attachments in the browser
with cloud viewers like Google Docs or Microsoft Office Web.

![demo](https://raw.githubusercontent.com/jfcherng-roundcube/plugin-cloudview/master/docs/screenshot/demo.png)

## Supported Formats

### Office Formats

- **Text**: doc, docx, odt<sup>[1]</sup>, ott<sup>[1]</sup>
- **Spreadsheet**: xls, xlsx, ods<sup>[1]</sup>, ots<sup>[1]</sup>
- **Presentation**: ppt, pptx, odp<sup>[1]</sup>, otp<sup>[1]</sup>

<sup>[1]</sup>: only available in Microsoft Office Web

### Other Formats

- pdf (via [PDF.js](https://github.com/mozilla/pdf.js/))

## Requirements

This plugin is tested in the following environment.

- Roundcube: `1.4`
- PHP: `7.1` (min requirement), `7.4`
- Skin: `Classic`, `Larry`, `Elastic`

Different environments may work as well without guarantee.

## How to install this plugin in Roundcube

### Install via Composer

This plugin has been published on [Packagist](https://packagist.org) by the name of [jfcherng-roundcube/cloudview](https://packagist.org/packages/jfcherng-roundcube/cloudview).

1. Go to your `ROUNDCUBE_HOME` (i.e., the root directory of your Roundcube).
2. Run `composer require jfcherng-roundcube/cloudview`.
3. You may edit the `config.inc.php` under this plugin's directory if you want to do some configurations.

### Install manually

1. Create folder `cloudview` in `ROUNDCUBE_HOME/plugins` if it does not exist.
2. Copy all plugin files there.
3. Copy `config.inc.php.dist` to `config.inc.php` and edit `config.inc.php` if you want.
4. Edit `ROUNDCUBE_HOME/conf/config.inc.php` locate `$config['plugins']` and add `'cloudview',` there:

```php
<?php

// some other codes...

$config['plugins'] = array(
    // some other plugins...
    'cloudview', // <-- add this
);
```

## Temporary Files

This plugin will extract attachments from messages into `plugins/cloudview/temp/`
so that remote cloud viewers can publicly access them. But those files will not
be deleted automatically. You will need to setup a cron job to periodically
delete them.

For example, execute `crontab -e` and add the following job

```text
# delete temporary files every day
0 0 * * * rm -f PATH_TO_ROUNDCUBE/plugins/cloudview/temp/*
```

## Acknowledgement

- The basic idea comes from https://github.com/brestows/cloudview-roundcube
- This plugin is sponsored by [@Galandrix](https://github.com/Galandrix).
