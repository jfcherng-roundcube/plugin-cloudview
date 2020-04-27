<?php

declare(strict_types=1);

include __DIR__ . '/lib/vendor/autoload.php';

use Jfcherng\Roundcube\Plugin\CloudView\MimeHelper;
use Jfcherng\Roundcube\Plugin\CloudView\RoundcubeHelper;
use Jfcherng\Roundcube\Plugin\CloudView\RoundcubePluginTrait;

final class cloudview extends rcube_plugin
{
    use RoundcubePluginTrait;

    const VIEWER_GOOGLE_DOCS = 1;
    const VIEWER_MICROSOFT_OFFICE_WEB = 2;

    const VIEW_BUTTON_IN_ATTACHMENTSLIST = 1;
    const VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU = 2;

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
     * Plugin actions and handlers.
     *
     * @var array<string,string>
     */
    public $actions = [
        'settings' => 'settingsAction',
        'settings-save' => 'settingsSaveAction',
        'view' => 'viewAction',
    ];

    /**
     * The plugin configuration.
     *
     * @var array
     */
    private $config = [];

    /**
     * The plugin user preferences.
     *
     * @var array
     */
    private $prefs = [];

    /**
     * Information about attachments.
     *
     * key: attachment ID, value: attachment info
     *
     * @var array<string,array>
     */
    private $attachments = [];

    /**
     * Plugin initialization.
     */
    public function init(): void
    {
        $rcmail = rcmail::get_instance();

        $this->loadPluginConfigurations();
        $this->loadPluginPreferences();
        $this->registerPluginActions();

        $this->add_texts('localization/', false);

        // preference settings hooks
        if ($rcmail->task === 'settings') {
            $this->add_hook('settings_actions', [$this, 'settingsActionsHook']);
            $this->include_stylesheet($this->local_skin_path() . '/settings.css');
            $this->include_script('assets/settings.min.js');
        }

        if (!$this->prefs['enabled']) {
            return;
        }

        if ($rcmail->action === 'show' || $rcmail->action === 'preview') {
            $this->add_hook('message_load', [$this, 'messageLoadHook']);
            $this->add_hook('template_object_messageattachments', [$this, 'messageattachmentsHook']);
            $this->include_stylesheet($this->local_skin_path() . '/main.css');
            $this->include_script('assets/main.min.js');
        }
    }

    /**
     * Register an entry for the settings page.
     */
    public function settingsActionsHook(array $args): array
    {
        $args['actions'][] = [
            'action' => 'plugin.cloudview.settings',
            'class' => $this->ID,
            'label' => 'plugin_settings_title',
            'domain' => $this->ID,
        ];

        return $args;
    }

    /**
     * Check message bodies and attachments.
     */
    public function messageLoadHook(array $p): array
    {
        $rcmail = rcmail::get_instance();

        foreach ((array) $p['object']->attachments as $attachment) {
            // Roundcube's mimetype detection seems to be less accurate
            // (such as it detect "rtf" files as "application/msword" rather than "application/rtf")
            // so we use the mimetype map from Apache to determine it by filename
            $mimetype = MimeHelper::guessMimeTypeByFilename($attachment->filename) ?? $attachment->mimetype;

            $this->attachments[$attachment->mime_id] = [
                'filename' => $attachment->filename,
                'is_supported' => MimeHelper::isSupportedMimeType($mimetype),
                'mime_id' => $attachment->mime_id,
                'mimetype' => $mimetype,
                'size' => $attachment->size,
            ];
        }

        $rcmail->output->set_env("{$this->ID}.attachments", $this->attachments);

        return $p;
    }

    /**
     * Add a button to the attachment popup menu.
     */
    public function messageattachmentsHook(array $p): array
    {
        if (\in_array(self::VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU, $this->prefs['view_button_layouts'])) {
            $this->addButton_BUTTON_IN_ATTACHMENTOPTIONSMENU($p);
        }

        if (\in_array(self::VIEW_BUTTON_IN_ATTACHMENTSLIST, $this->prefs['view_button_layouts'])) {
            $this->addButton_BUTTON_IN_ATTACHMENTSLIST($p);
        }

        return $p;
    }

    /**
     * Handler for plugin's "settings" action.
     */
    public function settingsAction(): void
    {
        $this->register_handler('plugin.body', [$this, 'settingsForm']);

        $rcmail = rcmail::get_instance();
        $rcmail->output->set_pagetitle($this->gettext('plugin_settings_title'));
        $rcmail->output->send('plugin');
    }

    /**
     * Handler for plugin's "settings-save" action.
     */
    public function settingsSaveAction(): void
    {
        $rcmail = rcmail::get_instance();

        $this->register_handler('plugin.body', [$this, 'settingsForm']);
        $rcmail->output->set_pagetitle($this->gettext('plugin_settings_title'));

        $prefs = $rcmail->user->get_prefs();
        $prefs['cloudview'] = $this->prefs = \array_merge(
            $this->prefs,
            [
                'enabled' => (int) rcube_utils::get_input_value(
                    '_cloudview_enabled',
                    rcube_utils::INPUT_POST
                ),
                'viewer' => (string) rcube_utils::get_input_value(
                    '_cloudview_viewer',
                    rcube_utils::INPUT_POST
                ),
                'view_button_layouts' => (array) rcube_utils::get_input_value(
                    '_cloudview_view_button_layouts',
                    rcube_utils::INPUT_POST
                ),
            ]
        );

        if ($rcmail->user->save_prefs($prefs)) {
            $rcmail->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
        } else {
            $rcmail->output->command('display_message', $this->gettext('unsuccessfullysaved'), 'error');
        }

        $rcmail->overwrite_action('plugin.cloudview.settings');
        $rcmail->output->send('plugin');
    }

    /**
     * Output the plugin preferences form.
     */
    public function settingsForm(): string
    {
        $rcmail = rcmail::get_instance();

        $boxTitle = html::div(['class' => 'boxtitle'], rcmail::Q($this->gettext('plugin_settings_title')));

        $saveButton = (new html_button())->show(
            rcmail::Q($this->gettext('save')),
            [
                'type' => 'input',
                'class' => 'btn button submit mainaction',
                'onclick' => "return rcmail.command('plugin.cloudview.settings-save', '', this, event)",
            ]
        );

        $objectTable = new html_table(['cols' => 2, 'class' => 'propform']);

        // option: enable this plugin or not
        $objectCloudviewEnabled = new html_checkbox([
            'name' => '_cloudview_enabled',
            'id' => '_cloudview_enabled',
            'value' => 1,
        ]);
        $objectTable->add('title', html::label('_cloudview_enabled', rcmail::Q($this->gettext('plugin_enabled'))));
        $objectTable->add('', $objectCloudviewEnabled->show($this->prefs['enabled'] ? 1 : 0));

        // option: choose cloud viewer
        $objectCloudviewViewer = new html_select(['name' => '_cloudview_viewer', 'id' => '_cloudview_viewer']);
        $objectCloudviewViewer->add(
            [
                rcmail::Q($this->gettext('viewer_microsoft_office_web')),
                rcmail::Q($this->gettext('viewer_google_docs')),
            ],
            [
                self::VIEWER_MICROSOFT_OFFICE_WEB,
                self::VIEWER_GOOGLE_DOCS,
            ]
        );
        $objectTable->add('title', html::label('_cloudview_viewer', rcmail::Q($this->gettext('select_viewer'))));
        $objectTable->add('', $objectCloudviewViewer->show($this->prefs['viewer']));

        // option: view button layouts
        $buttonLayoutCheckboxes = [];
        $buttonLayoutCheckboxes[] =
            (new html_checkbox([
                'name' => '_cloudview_view_button_layouts[]',
                'id' => '_cloudview_view_button_layout_in_attachmentslist',
                'value' => self::VIEW_BUTTON_IN_ATTACHMENTSLIST,
            ]))->show(
                \in_array(self::VIEW_BUTTON_IN_ATTACHMENTSLIST, $this->prefs['view_button_layouts'])
                    ? self::VIEW_BUTTON_IN_ATTACHMENTSLIST : -1
            ) . html::label(
                '_cloudview_view_button_layout_in_attachmentslist',
                rcmail::Q($this->gettext('view_button_layout_in_attachmentslist'))
            );
        $buttonLayoutCheckboxes[] =
            (new html_checkbox([
                'name' => '_cloudview_view_button_layouts[]',
                'id' => '_cloudview_view_button_layout_in_attachmentoptionsmenu',
                'value' => self::VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU,
            ]))->show(
                \in_array(self::VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU, $this->prefs['view_button_layouts'])
                    ? self::VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU : -1
            ) . html::label(
                '_cloudview_view_button_layout_in_attachmentoptionsmenu',
                rcmail::Q($this->gettext('view_button_layout_in_attachmentoptionsmenu'))
            );
        $objectTable->add('title', html::label(null, rcmail::Q($this->gettext('select_view_button_layouts'))));
        $objectTable->add(
            '',
            // wrap every checkbox in a <div>
            \implode('', \array_map(
                function (string $checkbox): string { return html::div(null, $checkbox); },
                $buttonLayoutCheckboxes,
            ))
        );

        $table = $objectTable->show();
        $form = html::div(['class' => 'boxcontent'], $table . $saveButton);

        // responsive layout for the "elastic" skin
        if (RoundcubeHelper::getBaseSkinName() === 'elastic') {
            $containerAttrs = ['class' => 'formcontent'];
        } else {
            $containerAttrs = [];
        }

        $rcmail->output->add_gui_object('cloudview-form', 'cloudview-form');

        return html::div($containerAttrs, $rcmail->output->form_tag(
            [
                'id' => 'cloudview-form',
                'name' => 'cloudview-form',
                'method' => 'post',
                'class' => 'propform',
                'action' => './?_task=settings&_action=plugin.cloudview.settings-save',
            ],
            html::div(['class' => 'box'], $boxTitle . $form)
        ));
    }

    /**
     * Handler for plugin's "view" action.
     */
    public function viewAction(): void
    {
        $rcmail = rcmail::get_instance();

        // get the post values
        $callback = rcube_utils::get_input_value('_callback', rcube_utils::INPUT_POST);
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $info = rcube_utils::get_input_value('_info', rcube_utils::INPUT_POST);

        if (!$uid || !$info) {
            return;
        }

        $attachment = \json_decode($info, true);

        $fileExt = \strtolower(\pathinfo($attachment['filename'], \PATHINFO_EXTENSION));
        $tempFileBaseName = \hash('md5', $info . $rcmail->user->ID);
        $tempFilePath = $this->url("temp/{$rcmail->user->ID}/{$tempFileBaseName}.{$fileExt}");
        $tempFileFullPath = INSTALL_PATH . $tempFilePath;

        // save the attachment into temp directory
        if (!\is_file($tempFileFullPath)) {
            @\mkdir(\dirname($tempFileFullPath), 0777, true);

            $fp = \fopen($tempFileFullPath, 'w');
            $rcmail->imap->get_message_part($uid, $attachment['mime_id'], null, null, $fp);
            \fclose($fp);
        }

        $fileUrl = RoundcubeHelper::getSiteUrl() . $tempFilePath;

        // PDF: local site viewer
        if ($fileExt === 'pdf') {
            $viewerUrl = RoundcubeHelper::getSiteUrl() . $this->url('assets/pdfjs-dist/web/viewer.html');
            $viewUrl = $viewerUrl . '?' . \http_build_query(['file' => $fileUrl]);
        }
        // Others: external cloud viewer
        else {
            if ($this->config['is_dev_mode']) {
                $fileUrl = $this->config['dev_mode_file_base_url'] . $tempFilePath;
            }

            $viewerUrl = self::VIEWER_URLS[$this->prefs['viewer']] ?? '';
            $viewUrl = \strtr($viewerUrl, [
                '{DOCUMENT_URL}' => \urlencode($fileUrl),
            ]);
        }

        // trigger the frontend callback to open the cloud viewer window
        $callback && $rcmail->output->command($callback, ['message' => ['url' => $viewUrl]]);
        $rcmail->output->send();
    }

    /**
     * Add a button in "attachmentoptionsmenu".
     */
    private function addButton_BUTTON_IN_ATTACHMENTOPTIONSMENU(array &$p): void
    {
        $this->add_buttons_attachmentmenu([
            [
                '_id' => $this->ID,
                'label' => "{$this->ID}.cloud_view_document",
                'href' => '#',
                'prop' => '',
                'command' => 'plugin.cloudview.open-attachment',
            ],
        ]);
    }

    /**
     * Add a button in "attachmentslist".
     */
    private function addButton_BUTTON_IN_ATTACHMENTSLIST(array &$p): void
    {
        $p['content'] = \preg_replace_callback(
            '/<li (?:.*?)<\/li>/uS',
            function (array $matches): string {
                $li = $matches[0];

                if (!\preg_match('/ id="attach([0-9]+)"/uS', $li, $attachmentId)) {
                    return $li;
                }

                $attachmentId = $attachmentId[1];
                $attachment = $this->attachments[$attachmentId] ?? ['is_supported' => false];
                $attachmentJson = \json_encode($attachment, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

                $button = html::a(
                    [
                        'href' => '#',
                        'class' => 'cloudview-preview-link',
                        'title' => rcmail::Q($this->gettext('cloud_view_document')),
                        'onclick' => "cloudview_openAttachment({$attachmentJson})",
                    ],
                    ''
                );

                // add "data-with-cloudview" attribute to the <li> tag
                $li = '<li data-with-cloudview="' . ($attachment['is_supported'] ? 1 : 0) . '"' . \substr($li, 3);

                // append the button into the <li> tag
                $li = \substr($li, 0, -5) . $button . '</li>';

                return $li;
            },
            $p['content']
        );
    }
}
