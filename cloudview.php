<?php

declare(strict_types=1);

include __DIR__ . '/lib/vendor/autoload.php';

use Jfcherng\Roundcube\Plugin\CloudView\MimeHelper;
use Jfcherng\Roundcube\Plugin\CloudView\RoundcubeHelper;
use Jfcherng\Roundcube\Plugin\CloudView\RoundcubePluginTrait;

final class cloudview extends rcube_plugin
{
    use RoundcubePluginTrait;

    const VIEWER_GOOGLE_DOCS = 'google_docs';
    const VIEWER_MICROSOFT_OFFICE_WEB = 'microsoft_office_web';

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
     * The default user plugin preferences.
     *
     * @var array
     */
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
    private $prefs;

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

        $this->loadPluginConfig();

        $this->prefs = \array_merge(
            self::PREFS_DEFAULT,
            $rcmail->user->get_prefs()['cloudview'] ?? []
        );

        // per-user plugin enable
        if ($this->prefs['cloudview_enabled']) {
            if ($rcmail->action === 'show' || $rcmail->action === 'preview') {
                $this->add_texts('localization/', false);
                $this->add_hook('message_load', [$this, 'messageLoad']);
                $this->add_hook('template_object_messageattachments', [$this, 'attachmentListHook']);
            }

            $this->register_action('plugin.cloudview-view', [$this, 'viewDocument']);
        }

        // preference settings hooks
        if ($rcmail->task === 'settings') {
            $this->add_texts('localization/', false);
            $this->add_hook('settings_actions', [$this, 'settingsActions']);
            $this->register_action('plugin.cloudview', [$this, 'cloudviewInit']);
            $this->register_action('plugin.cloudview-save', [$this, 'cloudviewSave']);
            $this->include_stylesheet($this->local_skin_path() . '/settings.css');
            $this->include_script('assets/settings.min.js');
        }
    }

    /**
     * Register an entry for the settings page.
     */
    public function settingsActions(array $args): array
    {
        $args['actions'][] = [
            'action' => 'plugin.' . $this->ID,
            'class' => $this->ID,
            'label' => 'plugin_settings_title',
            'domain' => $this->ID,
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

        $boxTitle = html::div(['class' => 'boxtitle'], rcmail::Q($this->gettext('plugin_settings_title')));

        $saveButton = (new html_button())->show(
            rcmail::Q($this->gettext('save')),
            [
                'type' => 'input',
                'class' => 'btn button submit mainaction',
                'onclick' => "return rcmail.command('plugin.cloudview-save', '', this, event)",
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
        $objectTable->add('', $objectCloudviewEnabled->show($this->prefs['cloudview_enabled'] ? 1 : 0));

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
        $objectTable->add('', $objectCloudviewViewer->show($this->prefs['cloudview_viewer']));

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
                'action' => './?_task=settings&_action=plugin.cloudview-save',
            ],
            html::div(['class' => 'box'], $boxTitle . $form)
        ));
    }

    /**
     * Called when the user saves plugin preferences.
     */
    public function cloudviewSave(): void
    {
        $rcmail = rcmail::get_instance();

        $this->register_handler('plugin.body', [$this, 'cloudviewForm']);
        $rcmail->output->set_pagetitle($this->gettext('plugin_settings_title'));

        $prefs = $rcmail->user->get_prefs();
        $this->prefs = $prefs['cloudview'] = \array_merge(
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
     * Check message bodies and attachments.
     */
    public function messageLoad(array $p): array
    {
        foreach ((array) $p['object']->attachments as $attachment) {
            // Roundcube's mimetype detection seems to be less accurate
            // (such as it detect "rtf" files as "application/msword" rather than "application/rtf")
            // so we use the mimetype map from Apache to determine it by filename
            $mimetype = MimeHelper::guessMimeTypeByFilename($attachment->filename) ?? $attachment->mimetype;

            $this->attachments[$attachment->mime_id] = [
                'mime_id' => $attachment->mime_id,
                'mimetype' => $mimetype,
                'filename' => $attachment->filename,
                'is_supported' => MimeHelper::isSupportedMimeType($mimetype),
            ];
        }

        return $p;
    }

    /**
     * Add a button to the attachment list.
     */
    public function attachmentListHook(array $p): array
    {
        $rcmail = rcmail::get_instance();

        $this->add_buttons_attachmentmenu([
            [
                '_id' => $this->ID,
                'label' => "{$this->ID}.cloud_view_document",
                'href' => '#',
                'prop' => '',
                'command' => 'plugin.cloudview-open-attachment',
            ],
        ]);

        $rcmail->output->set_env('cloudview.attachments', $this->attachments);
        $this->include_stylesheet($this->local_skin_path() . '/main.css');
        $this->include_script('assets/main.min.js');

        return $p;
    }

    /**
     * Handler for request action.
     */
    public function viewDocument(): void
    {
        $rcmail = rcmail::get_instance();

        // get the post values
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $info = rcube_utils::get_input_value('_info', rcube_utils::INPUT_POST);

        if (!$uid || !$info) {
            return;
        }

        $attachment = \json_decode($info, true);

        $fileExt = \strtolower(\pathinfo($attachment['filename'], \PATHINFO_EXTENSION));
        $tempFileBaseName = \hash('md5', $info . $this->config->get('hash_salt'));
        $tempFilePath = $this->url("temp/{$tempFileBaseName}.{$fileExt}");
        $tempFileFullPath = INSTALL_PATH . $tempFilePath;

        // save the attachment into temp directory
        if (!\is_file($tempFileFullPath)) {
            \file_put_contents(
                $tempFileFullPath,
                $rcmail->imap->get_message_part($uid, $attachment['mime_id'])
            );
        }

        $fileUrl = RoundcubeHelper::getSiteUrl() . $tempFilePath;

        // PDF: local site viewer
        if ($fileExt === 'pdf') {
            $viewerUrl = RoundcubeHelper::getSiteUrl() . $this->url('assets/pdfjs-dist/web/viewer.html');
            $viewUrl = $viewerUrl . '?' . \http_build_query(['file' => $fileUrl]);
        }
        // Others: external cloud viewer
        else {
            if ($this->config->get('is_dev_mode')) {
                $fileUrl = $this->config->get('dev_mode_file_base_url') . $tempFilePath;
            }

            $viewerUrl = self::VIEWER_URLS[$this->prefs['cloudview_viewer']] ?? '';
            $viewUrl = \strtr($viewerUrl, [
                '{DOCUMENT_URL}' => \urlencode($fileUrl),
            ]);
        }

        // trigger the frontend command to open the cloud viewer window
        $rcmail->output->command('plugin.cloudview-view', ['message' => ['url' => $viewUrl]]);
        $rcmail->output->send();
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
