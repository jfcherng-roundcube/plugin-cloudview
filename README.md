# Roundcube Plugin: Cloud View

A Roundcube plugin which lets you view documents, spreadsheets, presentations and
PDFs in the browser with cloud viewers like Google Docs or Microsoft Office Web.

## Supported Attachment Formats

### Microsoft Office Formats

- doc / docx - Microsoft Word
- xls / xlsx - Microsoft Excel
- ppt / pptx - Microsoft PowerPoint

### Other Formats

- pdf - Adobe Portable Document Format

## Requirements

This plugin is tested in the following environment.

- Roundcube 1.4.0
- PHP 7.1 and 7.4

Different environments may work as well without guarantee.

## How to install this plugin in Roundcube

### Install via Composer

This plugin has been published on [Packagist](https://packagist.org) by the name of [jfcherng/roundcube-plugin-cloudview](https://packagist.org/packages/jfcherng-roundcube/cloudview).

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
