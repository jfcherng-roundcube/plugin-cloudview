<?php

declare(strict_types=1);

include __DIR__ . '/lib/vendor/autoload.php';

use Jfcherng\Roundcube\Plugin\CloudView\DataStructure\Attachment;
use Jfcherng\Roundcube\Plugin\CloudView\Exception\ViewerNotFoundException;
use Jfcherng\Roundcube\Plugin\CloudView\Factory\ViewerFactory;
use Jfcherng\Roundcube\Plugin\CloudView\Helper\AbstractRoundcubePlugin;
use Jfcherng\Roundcube\Plugin\CloudView\Helper\MimeHelper;
use Jfcherng\Roundcube\Plugin\CloudView\Helper\PluginConst;
use Jfcherng\Roundcube\Plugin\CloudView\Helper\RoundcubeHelper;

final class cloudview extends AbstractRoundcubePlugin
{
    /**
     * {@inheritdoc}
     */
    public $task = 'mail|settings';

    /**
     * {@inheritdoc}
     */
    public $actions = [
        'settings' => 'settingsAction',
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

        $rcmail = rcmail::get_instance();

        if ($rcmail->task === 'mail') {
            $this->include_stylesheet("{$this->skinPath}/pages/mail.css");
            $this->include_script('assets/pages/mail.min.js');
        }

        if ($rcmail->task === 'settings') {
            $this->include_stylesheet("{$this->skinPath}/pages/settings.css");

            if ($rcmail->action === "plugin.{$this->ID}.settings") {
                $this->include_script('assets/vendor/Sortable.min.js');
                $this->include_script('assets/pages/settings.min.js');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPluginPreferences(): array
    {
        return [
            'enabled' => true,
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

        $uid = \filter_input(\INPUT_GET, '_uid') ?? '';

        foreach ((array) $p['object']->attachments as $rcAttachment) {
            // Roundcube's mimetype detection seems to be less accurate
            // (such as it detect "rtf" files as "application/msword" rather than "application/rtf")
            // so we use the mimetype map from Apache to determine it by filename if possible
            $mimeType = MimeHelper::guessMimeTypeByFilename($rcAttachment->filename) ?? $rcAttachment->mimetype;

            $attachment = Attachment::fromArray([]);
            $attachment->setId($rcAttachment->mime_id);
            $attachment->setUid($uid);
            $attachment->setFilename($rcAttachment->filename);
            $attachment->setMimeType($mimeType);
            $attachment->setSize($rcAttachment->size);
            $attachment->setIsSupported(
                null !== $this->getAttachmentSuggestedViewerId(
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

        if (\in_array(PluginConst::VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU, $this->prefs['view_button_layouts'])) {
            $this->addButton_BUTTON_IN_ATTACHMENTOPTIONSMENU($p);
        }

        if (\in_array(PluginConst::VIEW_BUTTON_IN_ATTACHMENTSLIST, $this->prefs['view_button_layouts'])) {
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

        $this->register_handler('plugin.body', [$this, 'getSettingsForm']);

        $isSaved = \filter_input(\INPUT_POST, '_is_saved', \FILTER_VALIDATE_BOOLEAN);

        if ($isSaved) {
            $prefs = $rcmail->user->get_prefs();
            $prefs['cloudview'] = $this->prefs = \array_merge(
                $this->prefs,
                [
                    'enabled' => \filter_input(\INPUT_POST, '_cloudview_enabled', \FILTER_VALIDATE_BOOLEAN),
                    'viewer_order' => \filter_input(\INPUT_POST, '_cloudview_viewer_order') ?? '',
                    'view_button_layouts' => \filter_input(
                        \INPUT_POST,
                        '_cloudview_view_button_layouts',
                        \FILTER_VALIDATE_INT,
                        \FILTER_FORCE_ARRAY
                    ),
                ]
            );

            // if no data received, we don't save
            if (!empty($_POST) && $rcmail->user->save_prefs($prefs)) {
                $output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
            } else {
                $output->command('display_message', $this->gettext('errfailedrequest'), 'error');
            }

            // our preferences may have changed, we overwrite the old output the current one
            $this->exposePluginPreferences();
        }

        $output->set_pagetitle($this->gettext('plugin_settings_title'));
        $output->send('plugin');
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
        $callback = \filter_input(\INPUT_POST, '_callback');
        $attachmentInfo = \filter_input(\INPUT_POST, '_attachment');

        if (!$attachmentInfo) {
            return;
        }

        $attachment = Attachment::fromArray(\json_decode($attachmentInfo, true) ?? []);
        $this->saveAttachmentToLocal($attachment);

        // trigger the frontend callback to open the cloud viewer window
        $callback && $output->command($callback, [
            'message' => [
                'url' => $this->getAttachmentViewableUrl($attachment) ?? '',
            ],
        ]);
        $output->send();
    }

    /**
     * Get the plugin settings form.
     */
    public function getSettingsForm(): string
    {
        $rcmail = rcmail::get_instance();
        /** @var rcmail_output_html */
        $output = $rcmail->output;

        $boxTitle = html::div(['class' => 'boxtitle'], rcmail::Q($this->gettext('plugin_settings_title')));

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
        $viewersSortable .= '<ol id="_cloudview_viewer_order" data-sortable>';
        foreach ($this->getLocalizedViewerNames() as $viewerId => $viewerName) {
            $viewersSortable .= '<li data-id="' . $viewerId . '">' . rcmail::Q($viewerName) . '</li>';
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
                'value' => PluginConst::VIEW_BUTTON_IN_ATTACHMENTSLIST,
            ]))->show(
                \in_array(PluginConst::VIEW_BUTTON_IN_ATTACHMENTSLIST, $this->prefs['view_button_layouts'])
                    ? PluginConst::VIEW_BUTTON_IN_ATTACHMENTSLIST : -1
            ) . html::label(
                '_cloudview_view_button_layout_in_attachmentslist',
                rcmail::Q($this->gettext('view_button_layout_in_attachmentslist'))
            );
        $buttonLayoutCheckboxes[] =
            (new html_checkbox([
                'name' => '_cloudview_view_button_layouts[]',
                'id' => '_cloudview_view_button_layout_in_attachmentoptionsmenu',
                'value' => PluginConst::VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU,
            ]))->show(
                \in_array(PluginConst::VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU, $this->prefs['view_button_layouts'])
                    ? PluginConst::VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU : -1
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
        $saveButton = (new html_button())->show(
            rcmail::Q($this->gettext('save')),
            [
                'type' => 'input',
                'class' => 'btn button submit mainaction',
                'onclick' => "return rcmail.command('plugin.{$this->ID}.settings-save', '', this, event)",
            ]
        );
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
                'action' => "?_task=settings&_action=plugin.{$this->ID}.settings",
            ],
            html::div(['class' => 'box'], $boxTitle . $form)
        ));
    }

    /**
     * Save an attachment to local.
     *
     * @param Attachment $attachment the attachment
     */
    private function saveAttachmentToLocal(Attachment $attachment): void
    {
        $rcmail = rcmail::get_instance();

        $fileFullPath = INSTALL_PATH . $this->getAttachmentTempPath($attachment);

        // save the attachment into temp directory
        if (!\is_file($fileFullPath)) {
            $tempDir = \dirname($fileFullPath);

            @\mkdir($tempDir, 0777, true);
            // put an index.html to prevent from potential directory traversal
            @\file_put_contents("{$tempDir}/index.html", '', \LOCK_EX);

            $fp = \fopen($fileFullPath, 'w');
            $rcmail->imap->get_message_part($attachment->getUid(), $attachment->getId(), null, null, $fp);
            \fclose($fp);
        }
    }

    /**
     * Get the temporary path for attachment.
     *
     * @param Attachment $attachment The attachment
     *
     * @return string the temporary path for attachment
     */
    private function getAttachmentTempPath(Attachment $attachment): string
    {
        $rcmail = rcmail::get_instance();

        $fileExt = \strtolower(\pathinfo($attachment->getFilename(), \PATHINFO_EXTENSION));
        $fileDotExt = $fileExt ? ".{$fileExt}" : '';
        $fileBaseName = \hash('md5', (string) $attachment);

        return $this->url("temp/{$rcmail->user->ID}/{$fileBaseName}{$fileDotExt}");
    }

    /**
     * Get the viewable URL for the attachment.
     *
     * @param Attachment $attachment the attachment
     *
     * @return null|string the viewable URL for the attachment
     */
    private function getAttachmentViewableUrl(Attachment $attachment): ?string
    {
        try {
            $viewerId = $this->getAttachmentSuggestedViewerId($attachment, $this->getViewerOrderArray());
            $viewer = ViewerFactory::make($viewerId);
            $viewer->setRcubePlugin($this);

            $siteUrl = $this->config['is_dev_mode'] && $viewer::IS_SUPPORT_CORS_FILE
                ? $this->config['dev_mode_file_base_url']
                : RoundcubeHelper::getSiteUrl();

            $fileUrl = $siteUrl . $this->getAttachmentTempPath($attachment);

            return $viewer->getViewableUrl(['document_url' => \urlencode($fileUrl)]) ?? '';
        } catch (ViewerNotFoundException $e) {
            return null;
        }
    }

    /**
     * Get localized viewer names.
     *
     * @return array<int,string> [ viewer ID => localized viewer name ]
     */
    private function getLocalizedViewerNames(): array
    {
        static $localizations;

        return $localizations = $localizations ?? \array_map(
            function (string $fqcn): string {
                // "...\CloudView\Viewer\GoogleDocsViewer" to "GoogleDocs"
                $transKey = \substr(\basename($fqcn), 0, -6);
                // "GoogleDocs" to "viewer_google_docs"
                $transKey = 'viewer' . \strtolower(\preg_replace('/[A-Z]/S', '_$0', $transKey));

                return $this->gettext($transKey);
            },
            PluginConst::VIEWER_TABLE
        );
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
     * @param int[]      $viewerOrder the viewer order
     *
     * @return null|int the viewer ID or null if no suitable one
     */
    private function getAttachmentSuggestedViewerId(Attachment $attachment, array $viewerOrder = []): ?int
    {
        foreach ($this->getPreferredViewerOrder($viewerOrder) as $viewerId) {
            if (ViewerFactory::getViewerFqcnById($viewerId)::canSupportAttachment($attachment)) {
                return $viewerId;
            }
        }

        return null;
    }

    /**
     * Get the preferred viewer ID order.
     *
     * @param int[] $viewerOrder the (partial) viewer order
     *
     * @return int[] the viewer ID order
     */
    private function getPreferredViewerOrder(array $viewerOrder = []): array
    {
        return \array_unique(\array_filter(
            // ensure the viewer list is complete
            \array_merge($viewerOrder, \array_keys(PluginConst::VIEWER_TABLE)),
            function (int $viewerId): bool { return ViewerFactory::hasViewer($viewerId); }
        ));
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
                        'title' => $this->gettext('cloud_view_document'),
                        'onclick' => "cloudview_openAttachment({$attachmentJson})",
                    ],
                    ''
                );

                return '<li data-with-cloudview="' . (int) $attachment->getIsSupported() . '"'
                    . \substr($li, 3, -5) // remove leading "<li" and trailing "</li>"
                    . $button . '</li>';
            },
            $p['content']
        );
    }
}
