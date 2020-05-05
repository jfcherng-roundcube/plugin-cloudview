<?php

declare(strict_types=1);

include __DIR__ . '/lib/vendor/autoload.php';

use Jfcherng\Roundcube\Plugin\CloudView\AbstractRoundcubePlugin;
use Jfcherng\Roundcube\Plugin\CloudView\Attachment;
use Jfcherng\Roundcube\Plugin\CloudView\Factory\ViewerFactory;
use Jfcherng\Roundcube\Plugin\CloudView\MimeHelper;
use Jfcherng\Roundcube\Plugin\CloudView\RoundcubeHelper;

final class cloudview extends AbstractRoundcubePlugin
{
    const VIEWER_GOOGLE_DOCS = 1;
    const VIEWER_MICROSOFT_OFFICE_WEB = 2;
    const VIEWER_PDF_JS = 3;
    const VIEWER_MARKDOWN_JS = 4;

    const VIEW_BUTTON_IN_ATTACHMENTSLIST = 1;
    const VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU = 2;

    /**
     * {@inheritdoc}
     */
    public $task = 'mail|settings';

    /**
     * {@inheritdoc}
     */
    public $actions = [
        'settings' => 'settingsAction',
        'settings-save' => 'settingsSaveAction',
        'view' => 'viewAction',
    ];

    /**
     * {@inheritdoc}
     */
    public $hooks = [
        'message_load' => 'messageLoadHook',
        'settings_actions' => 'settingsActionsHook',
        'template_object_messageattachments' => 'templateObjectMessageattachmentsHook',
    ];

    /**
     * Information about attachments.
     *
     * key: attachment ID, value: attachment object
     *
     * @var array<string,Attachment>
     */
    private $attachments = [];

    /**
     * Plugin initialization.
     */
    public function init(): void
    {
        parent::init();

        $this->include_stylesheet("{$this->skinPath}/main.css");
        $this->include_script('assets/vendor/Sortable.min.js');
        $this->include_script('assets/main.min.js');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPluginPreferences(): array
    {
        return [
            'enabled' => 1,
            'view_button_layouts' => $this->config['view_button_layouts'],
            'viewer_order' => $this->config['viewer_order'],
        ];
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
        /** @var rcmail_output_html */
        $output = $rcmail->output;

        foreach ((array) $p['object']->attachments as $rcAttachment) {
            // Roundcube's mimetype detection seems to be less accurate
            // (such as it detect "rtf" files as "application/msword" rather than "application/rtf")
            // so we use the mimetype map from Apache to determine it by filename if possible
            $mimeType = MimeHelper::guessMimeTypeByFilename($rcAttachment->filename) ?? $rcAttachment->mimetype;

            $attachment = Attachment::fromArray([]);
            $attachment->setId($rcAttachment->mime_id);
            $attachment->setFilename($rcAttachment->filename);
            $attachment->setMimeType($mimeType);
            $attachment->setSize($rcAttachment->size);
            $attachment->setIsSupported(
                null !== $this->getSuggestedViewerIdForAttachment(
                    $attachment,
                    $this->getViewerOrderArray()
                )
            );

            $this->attachments[$attachment->getId()] = $attachment;
        }

        $output->set_env("{$this->ID}.attachments", $this->attachments);

        return $p;
    }

    /**
     * Add a button to the attachment popup menu.
     */
    public function templateObjectMessageattachmentsHook(array $p): array
    {
        if (!$this->prefs['enabled']) {
            return $p;
        }

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
        $rcmail = rcmail::get_instance();
        /** @var rcmail_output_html */
        $output = $rcmail->output;

        $this->register_handler('plugin.body', [$this, 'settingsForm']);

        $output->set_pagetitle($this->gettext('plugin_settings_title'));
        $output->send('plugin');
    }

    /**
     * Handler for plugin's "settings-save" action.
     */
    public function settingsSaveAction(): void
    {
        $rcmail = rcmail::get_instance();
        /** @var rcmail_output_html */
        $output = $rcmail->output;

        $this->register_handler('plugin.body', [$this, 'settingsForm']);
        $output->set_pagetitle($this->gettext('plugin_settings_title'));

        $prefs = $rcmail->user->get_prefs();
        $prefs['cloudview'] = $this->prefs = \array_merge(
            $this->prefs,
            [
                'enabled' => (int) rcube_utils::get_input_value(
                    '_cloudview_enabled',
                    rcube_utils::INPUT_POST
                ),
                'viewer_order' => rcube_utils::get_input_value(
                    '_cloudview_viewer_order',
                    rcube_utils::INPUT_POST
                ),
                'view_button_layouts' => \array_map(
                    'intval',
                    (array) rcube_utils::get_input_value(
                        '_cloudview_view_button_layouts',
                        rcube_utils::INPUT_POST
                    )
                ),
            ]
        );

        // if no data received, we don't save
        if (!empty($_POST) && $rcmail->user->save_prefs($prefs)) {
            $output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
        } else {
            $output->command('display_message', $this->gettext('errfailedrequest'), 'error');
        }

        //  our preferences may have changed, we overwrite the old output the current one
        $this->exposePluginPreferences();

        $rcmail->overwrite_action('plugin.cloudview.settings');
        $output->send('plugin');
    }

    /**
     * Output the plugin preferences form.
     */
    public function settingsForm(): string
    {
        $rcmail = rcmail::get_instance();
        /** @var rcmail_output_html */
        $output = $rcmail->output;

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

        // option: cloud viewer order
        $viewersSortable = '<h5>' . rcmail::Q($this->gettext('viewers_tried_from_top_to_buttom')) . '</h5>';
        $viewersSortable .= '<ol class="viewers sortable">';
        foreach ($this->getViewerHtmlInformation() as $vid => $vname) {
            $viewersSortable .= '<li data-id="' . $vid . '">' . rcmail::Q($vname) . '</li>';
        }
        $viewersSortable .= '</ol>';

        $objectTable->add('title', html::label('_cloudview_viewer', rcmail::Q($this->gettext('select_viewer'))));
        $objectTable->add('', $viewersSortable);

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
                $buttonLayoutCheckboxes
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

        $output->add_gui_object('cloudview-form', 'cloudview-form');

        return html::div($containerAttrs, $output->form_tag(
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
        /** @var rcmail_output_json */
        $output = $rcmail->output;

        // get the post values
        $callback = rcube_utils::get_input_value('_callback', rcube_utils::INPUT_POST);
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $info = rcube_utils::get_input_value('_info', rcube_utils::INPUT_POST);

        if (!$uid || !$info) {
            return;
        }

        $attachment = Attachment::fromArray(\json_decode($info, true) ?? []);

        $fileExt = \strtolower(\pathinfo($attachment->getFilename(), \PATHINFO_EXTENSION));
        $fileDotExt = $fileExt ? ".{$fileExt}" : '';
        $tempFileBaseName = \hash('md5', $info . $rcmail->user->ID);
        $tempFilePath = $this->url("temp/{$rcmail->user->ID}/{$tempFileBaseName}{$fileDotExt}");
        $tempFileFullPath = INSTALL_PATH . $tempFilePath;

        // save the attachment into temp directory
        if (!\is_file($tempFileFullPath)) {
            $tempDir = \dirname($tempFileFullPath);

            @\mkdir($tempDir, 0777, true);
            // put an index.html to prevent from potential directory traversal
            @\file_put_contents("{$tempDir}/index.html", '', \LOCK_EX);

            $fp = \fopen($tempFileFullPath, 'w');
            $rcmail->imap->get_message_part($uid, $attachment->getId(), null, null, $fp);
            \fclose($fp);
        }

        $viewerId = $this->getSuggestedViewerIdForAttachment($attachment, $this->getViewerOrderArray());

        if (null === $viewerId) {
            $viewUrl = '';
        } else {
            $viewer = ViewerFactory::make($viewerId);
            $viewer->setRcubePlugin($this);

            $fileUrl = $this->config['is_dev_mode'] && $viewer::IS_SUPPORT_CORS_FILE
                ? $this->config['dev_mode_file_base_url'] . $tempFilePath
                : RoundcubeHelper::getSiteUrl() . $tempFilePath;

            $viewUrl = $viewer->getViewableUrl(['document_url' => \urlencode($fileUrl)]) ?? '';
        }

        // trigger the frontend callback to open the cloud viewer window
        $callback && $output->command($callback, ['message' => ['url' => $viewUrl]]);
        $output->send();
    }

    /**
     * Get the viewer HTML information.
     *
     * @return array<int,string> [ viewer ID => localized viewer name ]
     */
    private function getViewerHtmlInformation(): array
    {
        return [
            self::VIEWER_MICROSOFT_OFFICE_WEB => $this->gettext('viewer_microsoft_office_web'),
            self::VIEWER_GOOGLE_DOCS => $this->gettext('viewer_google_docs'),
            self::VIEWER_PDF_JS => $this->gettext('viewer_pdf_js'),
            self::VIEWER_MARKDOWN_JS => $this->gettext('viewer_markdown_js'),
        ];
    }

    /**
     * Get the viewer order array from user preferences.
     *
     * @return int[] the viewer order array
     */
    private function getViewerOrderArray(): array
    {
        return \array_map('intval', \explode(',', $this->prefs['viewer_order']));
    }

    /**
     * Get the suggested viewer ID for attachment.
     *
     * @param Attachment $attachment  the attachment
     * @param null|int[] $viewerOrder the viewer order
     *
     * @return null|int the viewer ID or null if no suitable one
     */
    private function getSuggestedViewerIdForAttachment(Attachment $attachment, ?array $viewerOrder = []): ?int
    {
        foreach ($this->calculatePreferredViewerOrder($viewerOrder) as $viewerId) {
            if (ViewerFactory::getViewerFqcnById($viewerId)::canSupportAttachment($attachment)) {
                return $viewerId;
            }
        }

        return null;
    }

    /**
     * Get the preferred viewer ID order.
     *
     * @param null|int[] $viewerOrder the (partial) viewer order
     *
     * @return int[] the viewer ID order
     */
    private function calculatePreferredViewerOrder(?array $viewerOrder = []): array
    {
        $viewerOrder = $viewerOrder ?? [];
        $viewerIds = \array_keys(ViewerFactory::VIEWER_TABLE);

        return \array_filter(
            \array_unique(\array_merge($viewerOrder, $viewerIds)),
            function (int $viewerId): bool {
                return ViewerFactory::hasViewer($viewerId);
            }
        );
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
                $attachment = $this->attachments[$attachmentId] ?? Attachment::fromArray([]);
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
                $li = '<li data-with-cloudview="' . (int) $attachment->getIsSupported() . '"' . \substr($li, 3);

                // append the button into the <li> tag
                $li = \substr($li, 0, -5) . $button . '</li>';

                return $li;
            },
            $p['content']
        );
    }
}
