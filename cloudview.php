<?php
/**
 * @version $Id$
 * Add a button to open attachments online with Zoho or Pixlr web service
 *
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */
class cloudview extends rcube_plugin
{
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

    private $aAttachmentData = [];
    private $oMessage;

    /**
     * Plugin initialization.
     */
    public function init(): void
    {
        $this->load_plugin_config();

        // initialize the rcmail class
        $oRCmail = rcmail::get_instance();

        // Add include path for internal classes
        $include_path = $this->home . '/lib' . \PATH_SEPARATOR;
        $include_path .= \ini_get('include_path');
        \set_include_path($include_path);

        // per-user plugin enable
        if ($this->config->get('cloudview_enabled', true)) {
            // include javascript files
            $this->include_script('js/browserWindowSize.js');

            if ($oRCmail->action == 'show' || $oRCmail->action == 'preview') {
                $this->add_hook('message_load', [$this, 'messageLoad']);
                $this->add_hook('template_object_messagebody', [$this, 'htmlOutput']);
            } elseif (
                !$oRCmail->output->framed &&
                (!$oRCmail->action || $oRCmail->action == 'list')
            ) {
                $this->include_script('js/openDocument.js');
            }

            $this->register_action('plugin.cloudview', [$this, 'viewDocument']);
        }

        // preference settings hooks
        if ($oRCmail->task == 'settings') {
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
        $this->add_texts('localization/');

        // get disabled configuration parameters
        $aDontOverride = $this->config->get('dont_override', []);

        // add enable editor check box
        if (!\in_array('cloudview_enabled', $aDontOverride)) {
            $sFieldId = '_cloudview_enabled';

            // get the current value
            $bIsEnabled = $this->config->get('cloudview_enabled', true);

            // crate the input field
            $oCheckBox = new html_checkbox(['name' => $sFieldId, 'id' => $sFieldId, 'value' => 1]);

            // add the new input filed to the argument list
            $args['blocks']['main']['options']['cloudview_enabled'] = [
                'title' => html::label($sFieldId, rcmail::Q($this->gettext('plugin_enabled'))),
                'content' => $oCheckBox->show($bIsEnabled ? 1 : 0),
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
        $aDontOverride = $this->config->get('dont_override', []);

        // enable plugin
        if (!\in_array('cloudview_enabled', $aDontOverride)) {
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
        $this->oMessage = $p['object'];

        // handle attachments
        foreach ((array) $this->oMessage->attachments as $oAttachment) {
            if ($this->isSupportedDoc($oAttachment)) {
                $this->aAttachmentData[] = [
                    'mime_id' => $oAttachment->mime_id,
                    'mimetype' => $oAttachment->mimetype,
                    'filename' => $oAttachment->filename,
                ];
            }
        }

        // debug stuff
        //var_dump($this->oMessage->attachments);

        if ($this->aAttachmentData) {
            $this->add_texts('localization');
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
        $bAttachScript = false;
        $bAttachPdfScript = false;

        foreach ($this->aAttachmentData as $aDocumentInfo) {
            $aJsonDocument['document'] = $aDocumentInfo;

            $style =
                'margin:0.5em 1em; padding:0.2em 0.5em; border:1px solid #999; ' .
                'border-radius:4px; -moz-border-radius:4px; -webkit-border-radius:4px; width: auto';

            if (mimeHelper::isMimeTypeText($aDocumentInfo['mimetype'])) {
                $icon =
                    'plugins/cloudview/' .
                    $this->local_skin_path() .
                    '/x-office-document.png';
                // add box below message body
                $p['content'] .= html::p(
                    ['style' => $style],
                    html::a(
                        [
                            'href' => '#',
                            'onclick' => "return plugin_cloudview_view_document('" .
                                rcube::JQ(\json_encode($aJsonDocument)) .
                                "')",
                            'title' => $this->gettext('opendocument'),
                        ],
                        html::img([
                            'src' => $icon,
                            'style' => 'vertical-align:middle',
                        ])
                    ) .
                        ' ' .
                        html::span(null, rcube::Q($aDocumentInfo['filename']))
                );

                $bAttachScript = true;
            }

            if (mimeHelper::isMimeTypeSpreadsheet($aDocumentInfo['mimetype'])) {
                $icon =
                    'plugins/cloudview/' .
                    $this->local_skin_path() .
                    '/x-office-spreadsheet.png';
                // add box below message body
                $p['content'] .= html::p(
                    ['style' => $style],
                    html::a(
                        [
                            'href' => '#',
                            'onclick' => "return plugin_cloudview_view_document('" .
                                rcube::JQ(\json_encode($aJsonDocument)) .
                                "')",
                            'title' => $this->gettext('opendocument'),
                        ],
                        html::img([
                            'src' => $icon,
                            'style' => 'vertical-align:middle',
                        ])
                    ) .
                        ' ' .
                        html::span(null, rcube::Q($aDocumentInfo['filename']))
                );

                $bAttachScript = true;
            }

            if (
                mimeHelper::isMimeTypePresentation($aDocumentInfo['mimetype'])
            ) {
                $icon =
                    'plugins/cloudview/' .
                    $this->local_skin_path() .
                    '/x-office-presentation.png';
                // add box below message body
                $p['content'] .= html::p(
                    ['style' => $style],
                    html::a(
                        [
                            'href' => '#',
                            'onclick' => "return plugin_cloudview_view_document('" .
                                rcube::JQ(\json_encode($aJsonDocument)) .
                                "')",
                            'title' => $this->gettext('opendocument'),
                        ],
                        html::img([
                            'src' => $icon,
                            'style' => 'vertical-align:middle',
                        ])
                    ) .
                        ' ' .
                        html::span(null, rcube::Q($aDocumentInfo['filename']))
                );

                $bAttachScript = true;
            }

            if (mimeHelper::isMimeTypePdf($aDocumentInfo['mimetype'])) {
                $icon =
                    'plugins/cloudview/' .
                    $this->local_skin_path() .
                    '/x-application-pdf.png';
                // show PDF below message body
                $p['content'] .= html::div(
                    ['class' => 'pdfviewer-container'],
                    html::p(
                        ['class' => 'pdfviewer-navigation', 'style' => $style],
                        html::span(
                            ['style' => 'float:right'],
                            html::a(
                                [
                                    'href' => '#',
                                    'class' => 'svgicon-arrowleft prev-page',
                                    'title' => $this->gettext('previouspage'),
                                ],
                                ''
                            ) .
                                html::span(
                                    null,
                                    html::span(['class' => 'page_num'], '') .
                                        ' ' .
                                        $this->gettext('pagenrof') .
                                        ' ' .
                                        html::span(
                                            ['class' => 'page_count'],
                                            ''
                                        )
                                ) .
                                html::a(
                                    [
                                        'href' => '#',
                                        'class' => 'svgicon-arrowright next-page',
                                        'title' => $this->gettext('nextpage'),
                                    ],
                                    ''
                                )
                        ) .
                            html::img([
                                'src' => $icon,
                                'style' => 'vertical-align:middle',
                            ]) .
                            ' ' .
                            html::span(
                                null,
                                rcube::Q($aDocumentInfo['filename'])
                            )
                    ) .
                        html::div(
                            ['class' => 'pdfviewer-viewport'],
                            html::tag('canvas', [
                                'class' => 'pdfviewer-canvas',
                                'data-id' => $aDocumentInfo['mime_id'],
                            ])
                        )
                );

                $bAttachPdfScript = true;
            }
        }

        if ($bAttachScript) {
            $this->include_script('js/openDocument.js');
        }

        if ($bAttachPdfScript) {
            $this->include_script('js/pdf.min.js');
            $this->include_script('js/compatibility.min.js');
            $this->include_script('js/showPdf.js');
            $this->include_script('js/raphael.min.js');
            $this->include_script('js/svgIcons.js');
        }

        return $p;
    }

    /**
     * Handler for request action.
     */
    public function viewDocument(): void
    {
        $this->load_config();

        // tell the plugin API where to search for texts
        $this->add_texts('localization', true);

        // get the post values
        $sUid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $aJsonDocument = rcube_utils::get_input_value('_info', rcube_utils::INPUT_POST);

        if (!$sUid || !$aJsonDocument) {
            return;
        }

        $aDocumentInfo = \json_decode($aJsonDocument, true);

        // initialize the rcmail class
        $oRCmail = rcmail::get_instance();

        $sFileSuffix = \pathinfo($aDocumentInfo['document']['filename'], \PATHINFO_EXTENSION);
        $sFileBaseName = \hash('md5', $aJsonDocument . $this->config->get('hash_salt'));
        $sTmpFileRelative = "plugins/cloudview/temp/{$sFileBaseName}.{$sFileSuffix}";
        $sTmpFile = INSTALL_PATH . $sTmpFileRelative;

        // save the attachment into temp directory
        if (!\is_file($sTmpFile)) {
            $sDocument = $oRCmail->imap->get_message_part($sUid, $aDocumentInfo['document']['mime_id']);
            \file_put_contents($sTmpFile, $sDocument);
        }

        if ($this->config->get('is_dev_mode')) {
            $sFileUrl = $this->config->get('dev_mode_file_base_url') . $sTmpFileRelative;
        } else {
            $sRequestedUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
            $aUrlComponents = \parse_url($sRequestedUrl);
            unset($aUrlComponents['fragment']);
            $aUrlComponents['path'] .= $sTmpFileRelative;
            $aUrlComponents['query'] = null;

            $sFileUrl = $this->unparseUrl($aUrlComponents);
        }

        $oRCmail->output->command('plugin.cloudview', [
            'message' => [
                'url' => \strtr($this->config->get('viewer_url'), [
                    '{DOCUMENT_URL}' => \urlencode($sFileUrl),
                ]),
            ],
        ]);
        $oRCmail->output->send();
    }

    /**
     * Check if specified attachment contains a supported document.
     *
     * @param mixed $oAttachment
     */
    public function isSupportedDoc($oAttachment): bool
    {
        // use file name suffix with hard-coded mime-type map
        $aMimeExt = @include RCMAIL_CONFIG_DIR . '/mimetypes.php';
        $sFileSuffix = \pathinfo($oAttachment->filename, \PATHINFO_EXTENSION);
        if (\is_array($aMimeExt)) {
            $sMimeType = $aMimeExt[$sFileSuffix];
        }

        if (mimeHelper::isSupportedMimeType($oAttachment->mimetype)) {
            return mimeHelper::isSupportedMimeType($oAttachment->mimetype);
        }

        return mimeHelper::isSupportedMimeType($sMimeType);
    }

    /**
     * Load plugin configuration.
     */
    private function load_plugin_config(): void
    {
        $rcmail = rcmail::get_instance();

        $this->load_config('config.inc.php.dist');
        $this->load_config('config.inc.php');

        $this->config = $rcmail->config;
    }

    /**
     * Assemble URL parts back to string URL.
     *
     * @param array $parts the parts
     */
    private function unparseUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ":{$parts['port']}" : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ":{$parts['pass']}" : '';
        $pass = ($user || $pass) ? "{$pass}@" : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? "?{$parts['query']}" : '';
        $fragment = isset($parts['fragment']) ? "#{$parts['fragment']}" : '';

        return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
    }
}
