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

## Installing `cloudview`

After downloading `cloudview`, extract (unzip) it in RoundCube's plugins directory.
Doing this correctly should result in a subdirectory `plugins/cloudview` which contains
all of the plugin's files.

Install it by adding its directory name to the config option plugins,
as an array element. Editing your local "config/main.inc.php" file and
add `'cloudview'` into the `$config['plugins']` array.

To uninstall `cloudview`, just remove it from the list.

## Enabling `cloudview`

After this plugin has been installed, it's enabled by default.
To disable it, you can find the switch in your preferences:
`Settings -> Server Settings -> Main -> Enable "cloudview" plugin for mail attachments`.

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

https://github.com/brestows/cloudview-roundcube
