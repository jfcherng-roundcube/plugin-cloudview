<?php

class cloudview extends rcube_plugin
{
    const THIS_PLUGIN_DIR = 'plugins/' . __CLASS__ . '/';

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

    const PREFS_DEFAULT = [
        'cloudview_enabled' => 1,
        'cloudview_viewer' => self::VIEWER_MICROSOFT_OFFICE_WEB,
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
     * The user plugin preferences.
     *
     * @var array
     */
    private $pluginPrefs;

    /**
     * @var array[]
     */
    private $attachmentDatas = [];

    /**
     * Plugin initialization.
     */
    public function init(): void
    {
        $rcMail = rcmail::get_instance();

        $this->loadPluginConfig();
        $this->add_texts('locales/', false);

        $this->pluginPrefs = \array_merge(
            self::PREFS_DEFAULT,
            $rcMail->user->get_prefs()['cloudview'] ?? []
        );

        // add include path for internal classes
        $includePath = $this->home . '/lib' . \PATH_SEPARATOR;
        $includePath .= \ini_get('include_path');
        \set_include_path($includePath);

        // per-user plugin enable
        if ($this->pluginPrefs['cloudview_enabled']) {
            if ($rcMail->action === 'show' || $rcMail->action === 'preview') {
                $this->add_hook('message_load', [$this, 'messageLoad']);
                $this->add_hook('template_object_messageattachments', [$this, 'attachmentListHook']);
            }

            $this->register_action('plugin.cloudview-view', [$this, 'viewDocument']);
        }

        // preference settings hooks
        if ($rcMail->task === 'settings') {
            $this->add_hook('settings_actions', [$this, 'settingsActions']);
            $this->register_action('plugin.cloudview', [$this, 'cloudviewInit']);
            $this->register_action('plugin.cloudview-save', [$this, 'cloudviewSave']);
            $this->include_stylesheet($this->local_skin_path() . '/settings.css');
            $this->include_script('js/settings.min.js');
        }
    }

    /**
     * Register an entry for the settings page.
     */
    public function settingsActions(array $args): array
    {
        $args['actions'][] = [
            'action' => 'plugin.' . __CLASS__,
            'class' => __CLASS__,
            'label' => 'plugin_settings_title',
            'domain' => __CLASS__,
        ];

        return $args;
    }

    /**
     * The settings page.
     */
    public function cloudviewInit(): void
    {
        $this->register_handler('plugin.body', [$this, 'cloudviewForm']);

        $rcmail = rcmail::get_instance();
        $rcmail->output->set_pagetitle($this->gettext('plugin_settings_title'));
        $rcmail->output->send('plugin');
    }

    /**
     * Output the plugin preferences form.
     */
    public function cloudviewForm(): string
    {
        $rcmail = rcmail::get_instance();
        $this->add_texts('locales/', false);

        $saveButton = html::p(null, $rcmail->output->button([
            'command' => 'plugin.cloudview-save',
            'type' => 'input',
            'class' => 'button mainaction',
            'label' => 'save',
        ]));

        $table = new html_table(['cols' => 2, 'class' => 'propform']);

        $objectCloudviewEnabled = new html_checkbox([
            'name' => '_cloudview_enabled',
            'id' => '_cloudview_enabled',
            'value' => 1,
        ]);

        $table->add('title', html::label('_cloudview_enabled', rcmail::Q($this->gettext('plugin_enabled'))));
        $table->add('', $objectCloudviewEnabled->show($this->pluginPrefs['cloudview_enabled'] ? 1 : 0));

        $objectCloudviewViewer = new html_select(['name' => '_cloudview_viewer', 'id' => '_cloudview_viewer']);
        $objectCloudviewViewer->add(
            [
                $this->gettext('viewer_microsoft_office_web'),
                $this->gettext('viewer_google_docs'),
            ],
            [
                self::VIEWER_MICROSOFT_OFFICE_WEB,
                self::VIEWER_GOOGLE_DOCS,
            ]
        );

        $table->add('title', html::label('_cloudview_viewer', rcmail::Q($this->gettext('select_viewer'))));
        $table->add('', $objectCloudviewViewer->show($this->pluginPrefs['cloudview_viewer']));

        $html .= $table->show();

        $boxTitle = html::div(['class' => 'boxtitle'], $this->gettext('plugin_settings_title'));
        $form = html::div(['class' => 'boxcontent'], $html.$saveButton);

        $rcmail->output->add_gui_object('cloudview-form', 'cloudview-form');

        return $rcmail->output->form_tag(
            [
                'id' => 'cloudview-form',
                'name' => 'cloudview-form',
                'method' => 'post',
                'class' => 'propform',
                'action' => './?_task=settings&_action=plugin.cloudview-save',
            ],
            html::div(['class' => 'box'], $boxTitle . $form)
        );
    }

    /**
     * Called when the user saves plugin preferences.
     */
    public function cloudviewSave(): void
    {
        $rcmail = rcmail::get_instance();
        $this->add_texts('locales/', false);

        $this->register_handler('plugin.body', [$this, 'cloudviewForm']);
        $rcmail->output->set_pagetitle($this->gettext('plugin_settings_title'));

        $prefs = $rcmail->user->get_prefs();
        $this->pluginPrefs = $prefs['cloudview'] = \array_merge(
            $prefs['cloudview'] ?? [],
            [
                'cloudview_enabled' => (int) rcube_utils::get_input_value('_cloudview_enabled', rcube_utils::INPUT_POST),
                'cloudview_viewer' => (string) rcube_utils::get_input_value('_cloudview_viewer', rcube_utils::INPUT_POST),
            ]
        );

        if ($rcmail->user->save_prefs($prefs)) {
            $rcmail->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
        } else {
            $rcmail->output->command('display_message', $this->gettext('unsuccessfullysaved'), 'error');
        }

        $rcmail->overwrite_action('plugin.cloudview');
        $rcmail->output->send('plugin');
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

    /**
     * Add a button to the plugin list.
     */
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

            $viewerUrl = self::VIEWER_URLS[$this->pluginPrefs['cloudview_viewer']];
            $viewUrl = \strtr($viewerUrl, [
                '{DOCUMENT_URL}' => \urlencode($fileUrl),
            ]);
        }

        $rcMail->output->command('plugin.cloudview-view', ['message' => ['url' => $viewUrl]]);
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
