<?php

class cloudview extends rcube_plugin
{
    const THIS_PLUGIN_DIR = 'plugins/cloudview/';

    const VIEWER_MICROSOFT_OFFICE_WEB = 'microsoft_office_web';
    const VIEWER_GOOGLE_DOCS = 'google_docs';

    /**
     * Cloud viewer URLs.
     *
     * @var string[]
     */
    const VIEWER_URLS = [
        self::VIEWER_GOOGLE_DOCS => 'https://docs.google.com/viewer?embedded=true&url={DOCUMENT_URL}',
        self::VIEWER_MICROSOFT_OFFICE_WEB => 'https://view.officeapps.live.com/op/view.aspx?src={DOCUMENT_URL}',
    ];

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
     * @var array[]
     */
    private $attachmentDatas = [];

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
            if ($rcMail->action === 'show' || $rcMail->action === 'preview') {
                $this->add_hook('message_load', [$this, 'messageLoad']);
                $this->add_hook('template_object_messageattachments', [$this, 'attachmentListHook']);
            }

            $this->register_action('plugin.cloudview', [$this, 'viewDocument']);
        }

        // preference settings hooks
        if ($rcMail->task === 'settings') {
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
        $this->add_texts('locales/', false);

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

        // add cloud viewer select
        if (!\in_array('cloudview_viewer', $dontOverride)) {
            $fieldId = '_cloudview_viewer';

            // get the current value
            $viewerName = $this->config->get('cloudview_viewer', 'microsoft_web');

            // crate the input field
            $select = new html_select(['name' => $fieldId, 'id' => $fieldId]);
            $select->add(
                [
                    $this->gettext('viewer_microsoft_office_web'),
                    $this->gettext('viewer_google_docs'),
                ],
                [
                    self::VIEWER_MICROSOFT_OFFICE_WEB,
                    self::VIEWER_GOOGLE_DOCS,
                ]
            );

            // add the new input filed to the argument list
            $args['blocks']['main']['options']['cloudview_viewer'] = [
                'title' => html::label($fieldId, rcmail::Q($this->gettext('select_viewer'))),
                'content' => $select->show($viewerName),
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
        if ($args['section'] !== 'server') {
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

        // cloud viewer
        if (!\in_array('cloudview_viewer', $dontOverride)) {
            $args['prefs']['cloudview_viewer'] = \filter_var(
                $_POST['_cloudview_viewer'],
                \FILTER_SANITIZE_STRING
            );
        }

        return $args;
    }

    /**
     * Check message bodies and attachments for supported documents.
     */
    public function messageLoad(array $p): array
    {
        $this->message = $p['object'];

        // handle attachments
        foreach ((array) $this->message->attachments as $attachment) {
            if ($this->isSupportedDoc($attachment)) {
                $this->attachmentDatas[] = [
                    'mime_id' => $attachment->mime_id,
                    'mimetype' => $attachment->mimetype,
                    'filename' => $attachment->filename,
                ];
            }
        }

        if (!empty($this->attachmentDatas)) {
            $this->add_texts('locales/', true);
        }

        return $p;
    }

    public function attachmentListHook(array $p): array
    {
        $html = '';
        $attachmentDatas = [];

        foreach ($this->attachmentDatas as $documentInfo) {
            if (MimeHelper::isSupportedMimeType($documentInfo['mimetype'])) {
                $attachmentDatas[] = $documentInfo;
            }
        }

        if (!empty($attachmentDatas)) {
            $jsonData = \json_encode($attachmentDatas, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
            $html .= "<script>var cloudview_attachmentInfos = {$jsonData};</script>";

            $this->include_stylesheet($this->local_skin_path() . '/main.css');
            $this->include_script('js/appendAttachmentPreview.min.js');
            $this->include_script('js/openDocument.min.js');
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
        // Others: external cloud viewer
        else {
            if ($this->config->get('is_dev_mode')) {
                $fileUrl = $this->config->get('dev_mode_file_base_url') . $tempFile;
            }

            $viewerUrl = self::VIEWER_URLS[$this->config->get('cloudview_viewer', 'microsoft_web')];
            $viewUrl = \strtr($viewerUrl, [
                '{DOCUMENT_URL}' => \urlencode($fileUrl),
            ]);
        }

        $rcMail->output->command('plugin.cloudview', ['message' => ['url' => $viewUrl]]);
        $rcMail->output->send();
    }

    /**
     * Check if specified attachment contains a supported document.
     */
    public function isSupportedDoc(rcube_message_part $attachment): bool
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
