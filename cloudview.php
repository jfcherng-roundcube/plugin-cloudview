<?php
/**
 * @version $Id$
 * Add a button to open attachments online with Zoho or Pixlr web service
 *
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */
class cloudview extends rcube_plugin
{
    const THIS_PLUGIN_DIR = 'plugins/cloudview/';

    /**
     * {@inheritdoc}
     */
    public $task = 'mail|settings';

    /**
     * The loaded configuration.
     *
     * @var rcube_config
     */
    private $config;

    /**
     * @var array
     */
    private $attachmentData = [];

    private $message;

    /**
     * Plugin initialization.
     */
    public function init(): void
    {
        $this->loadPluginConfig();

        // initialize the rcmail class
        $rcMail = rcmail::get_instance();

        // add include path for internal classes
        $includePath = $this->home . '/lib' . \PATH_SEPARATOR;
        $includePath .= \ini_get('include_path');
        \set_include_path($includePath);

        // per-user plugin enable
        if ($this->config->get('cloudview_enabled', true)) {
            // include javascript files
            $this->include_script('js/browserWindowSize.js');

            if ($rcMail->action == 'show' || $rcMail->action == 'preview') {
                $this->add_hook('message_load', [$this, 'messageLoad']);
                $this->add_hook('template_object_messagebody', [$this, 'htmlOutput']);
            } elseif (
                !$rcMail->output->framed &&
                (!$rcMail->action || $rcMail->action == 'list')
            ) {
                $this->include_script('js/openDocument.js');
            }

            $this->register_action('plugin.cloudview', [$this, 'viewDocument']);
        }

        // preference settings hooks
        if ($rcMail->task == 'settings') {
            $this->add_hook('preferences_list', [$this, 'preferencesList']);
            $this->add_hook('preferences_save', [$this, 'preferencesSave']);
        }
    }

    /**
     * Handler for user preferences form (preferences_list hook).
     *
     * @param array $args the arguments
     */
    public function preferencesList(array $args): array
    {
        // add our new preferences to the server settings page
        if ($args['section'] != 'server') {
            return $args;
        }

        // load localization and configuration
        $this->add_texts('locales/', true);

        // get disabled configuration parameters
        $dontOverride = $this->config->get('dont_override', []);

        // add enable editor check box
        if (!\in_array('cloudview_enabled', $dontOverride)) {
            $fieldId = '_cloudview_enabled';

            // get the current value
            $isEnabled = $this->config->get('cloudview_enabled', true);

            // crate the input field
            $checkBox = new html_checkbox(['name' => $fieldId, 'id' => $fieldId, 'value' => 1]);

            // add the new input filed to the argument list
            $args['blocks']['main']['options']['cloudview_enabled'] = [
                'title' => html::label($fieldId, rcmail::Q($this->gettext('plugin_enabled'))),
                'content' => $checkBox->show($isEnabled ? 1 : 0),
            ];
        }

        return $args;
    }

    /**
     * Handler for user preferences save (preferences_save hook).
     *
     * @param array $args the arguments
     */
    public function preferencesSave(array $args): array
    {
        // add our new preferences to the server settings page
        if ($args['section'] != 'server') {
            return $args;
        }

        // get disabled configuration parameters
        $dontOverride = $this->config->get('dont_override', []);

        // enable plugin
        if (!\in_array('cloudview_enabled', $dontOverride)) {
            $args['prefs']['cloudview_enabled'] = \filter_var(
                $_POST['_cloudview_enabled'],
                \FILTER_SANITIZE_STRING
            );
        }

        return $args;
    }

    /**
     * Check message bodies and attachments for supported documents.
     *
     * @param mixed $p
     */
    public function messageLoad($p): void
    {
        $this->message = $p['object'];

        // handle attachments
        foreach ((array) $this->message->attachments as $attachment) {
            if ($this->isSupportedDoc($attachment)) {
                $this->attachmentData[] = [
                    'mime_id' => $attachment->mime_id,
                    'mimetype' => $attachment->mimetype,
                    'filename' => $attachment->filename,
                ];
            }
        }

        if (!empty($this->attachmentData)) {
            $this->add_texts('locales/', true);
        }
    }

    /**
     * This callback function adds a box below the message content
     * if there is a supported document available.
     *
     * @param mixed $p
     */
    public function htmlOutput($p)
    {
        $html = '';

        foreach ($this->attachmentData as $documentInfo) {
            $isSupported = false;
            $jsonDocument = [];
            $jsonDocument['document'] = $documentInfo;

            $style =
                'margin:0.5em 1em; padding:0.2em 0.5em; border:1px solid #999; ' .
                'border-radius:4px; -moz-border-radius:4px; -webkit-border-radius:4px; width: auto';

            if (MimeHelper::isMimeTypeText($documentInfo['mimetype'])) {
                $isSupported = true;
                $icon = 'x-office-document.png';
            } elseif (MimeHelper::isMimeTypeSpreadsheet($documentInfo['mimetype'])) {
                $isSupported = true;
                $icon = 'x-office-spreadsheet.png';
            } elseif (MimeHelper::isMimeTypePresentation($documentInfo['mimetype'])) {
                $isSupported = true;
                $icon = 'x-office-presentation.png';
            } elseif (MimeHelper::isMimeTypePdf($documentInfo['mimetype'])) {
                $isSupported = true;
                $icon = 'x-application-pdf.png';
            }

            if ($isSupported) {
                $iconUrl = self::THIS_PLUGIN_DIR . $this->local_skin_path() . "/{$icon}";

                // add box below message body
                $html .= html::p(
                    ['style' => $style],
                    html::a(
                        [
                            'href' => 'javascript:;',
                            'onclick' => "return plugin_cloudview_view_document('" . rcube::JQ(\json_encode($jsonDocument)) . "')",
                            'title' => $this->gettext('open_document'),
                        ],
                        html::img([
                            'src' => $iconUrl,
                            'style' => 'vertical-align:middle',
                        ])
                    ) . ' ' . html::span(null, rcube::Q($documentInfo['filename']))
                );
            }
        }

        if ($html) {
            $html = '<hr>' . $html;

            $this->include_script('js/openDocument.js');
        }

        $p['content'] .= $html;

        return $p;
    }

    /**
     * Handler for request action.
     */
    public function viewDocument(): void
    {
        $this->load_config();

        // tell the plugin API where to search for texts
        $this->add_texts('locales/', true);

        // get the post values
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $jsonDocument = rcube_utils::get_input_value('_info', rcube_utils::INPUT_POST);

        if (!$uid || !$jsonDocument) {
            return;
        }

        $documentInfo = \json_decode($jsonDocument, true);

        // initialize the rcmail class
        $rcMail = rcmail::get_instance();

        $fileSuffix = \strtolower(\pathinfo($documentInfo['document']['filename'], \PATHINFO_EXTENSION));
        $fileBaseName = \hash('md5', $jsonDocument . $this->config->get('hash_salt'));
        $tempFile = self::THIS_PLUGIN_DIR . "temp/{$fileBaseName}.{$fileSuffix}";
        $tempFileFullPath = INSTALL_PATH . $tempFile;

        // save the attachment into temp directory
        if (!\is_file($tempFileFullPath)) {
            $document = $rcMail->imap->get_message_part($uid, $documentInfo['document']['mime_id']);
            \file_put_contents($tempFileFullPath, $document);
        }

        $fileUrl = CloudviewHelper::getSiteUrl() . $tempFile;
        
        // PDF: local site viewer
        if ($fileSuffix === 'pdf') {
            $viewerUrl = CloudviewHelper::getSiteUrl() . self::THIS_PLUGIN_DIR . 'js/pdfjs-dist/web/viewer.html';
            $viewUrl = $viewerUrl . '?' . \http_build_query(['file' => $fileUrl]);
        }
        // MS Office: external viewer
        else {
            if ($this->config->get('is_dev_mode')) {
                $fileUrl = $this->config->get('dev_mode_file_base_url') . $tempFile;
            }

            $viewUrl = \strtr($this->config->get('viewer_url'), [
                '{DOCUMENT_URL}' => \urlencode($fileUrl),
            ]);
        }

        $rcMail->output->command('plugin.cloudview', ['message' => ['url' => $viewUrl]]);
        $rcMail->output->send();
    }

    /**
     * Check if specified attachment contains a supported document.
     *
     * @param mixed $attachment
     */
    public function isSupportedDoc($attachment): bool
    {
        if (MimeHelper::isSupportedMimeType($attachment->mimetype)) {
            return MimeHelper::isSupportedMimeType($attachment->mimetype);
        }

        // use file name suffix with hard-coded mime-type map
        $fileSuffix = \pathinfo($attachment->filename, \PATHINFO_EXTENSION);
        $mimeExts = \is_file($mimeFile = RCMAIL_CONFIG_DIR . '/mimetypes.php') ? (require $mimeFile) : [];
        $mimeType = $mimeExts[$fileSuffix] ?? null;

        return MimeHelper::isSupportedMimeType($mimeType);
    }

    /**
     * Load plugin configuration.
     */
    private function loadPluginConfig(): void
    {
        $rcmail = rcmail::get_instance();

        $this->load_config('config.inc.php.dist');
        $this->load_config('config.inc.php');

        $this->config = $rcmail->config;
    }
}
