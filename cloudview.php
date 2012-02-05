<?php
/**
 * @version $Id$
 * Add a button to open attachments online with Zoho or Pixlr web service
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */
 
class cloudview extends rcube_plugin {
	public $task = 'mail|settings';
	
    private $aAttachmentData = array();
	private $oMessage;

	/**
	 * Plugin initialization
	 */
	function init() {
		// initialize the rcmail class ##
        $oRCmail = rcmail::get_instance();
		
		// Add include path for internal classes ##
        $include_path = $this->home . '/lib' . PATH_SEPARATOR;
        $include_path .= ini_get('include_path');
        set_include_path($include_path);
		
		// include javascript files ##
		$this->include_script("js/browserWindowSize.js");
		
        if ($oRCmail->action == 'show' || $oRCmail->action == 'preview') {
            $this->add_hook('message_load', array($this, 'messageLoad'));
            $this->add_hook('template_object_messagebody', array($this, 'htmlOutput'));
        }
        else if (!$oRCmail->output->framed && (!$oRCmail->action || $oRCmail->action == 'list')) {
            $this->include_script('js/openDocument.js');
        }

        $this->register_action('plugin.cloudview', array($this, 'viewDocument'));
		
		// preference settings hooks ##
		if ($oRCmail->task == 'settings') {
			$this->add_hook('preferences_list', array($this, 'preferencesList'));
			$this->add_hook('preferences_save', array($this, 'preferencesSave'));
		}
    }

	/**
	 * Handler for user preferences form (preferences_list hook)
	 */
	function preferencesList($args) {
		// add our new preferences to the server settings page ##
		if ($args['section'] != 'server') {
			return $args;
		}
		
		// initialize the rcmail class ##
        $oRCmail = rcmail::get_instance();
		
		// load configuration ##
		$this->load_config();
		
		// load localization and configuration ##
		$this->add_texts('localization/');
		
		// get disabled configuration parameters ##
		$aDontOverride = $oRCmail->config->get('dont_override', array());
		
		// add zoho save url input field ##
		if (!in_array('cloudview_zoho_save_url', $aDontOverride)) { // check that our configuration is not disabled ##
			$sFieldId = '_cloudview_zoho_save_url';
			// get the current value ##
			$sZohoSaveUrl = $oRCmail->config->get('cloudview_zoho_save_url');
			// crate the input field ##
			$oInputField = new html_inputfield( array('name' => $sFieldId, 'id' => $sFieldId, 'size' => 50) );
			
			// add the new input filed to the argument list ##
			$args['blocks']['main']['options']['cloudview_zoho_save_url'] = array(
						'title' => html::label($sFieldId, Q($this->gettext('zohosaveurl'))),
						'content' => $oInputField->show($sZohoSaveUrl),
			);
		}
		
		// add pixlr save url input field ##
		if (!in_array('cloudview_pixlr_save_url', $aDontOverride)) { // check that our configuration is not disabled ##
			$sFieldId = '_cloudview_pixlr_save_url';
			// get the current value ##
			$sPixlrSaveUrl = $oRCmail->config->get('cloudview_pixlr_save_url');
			// crate the input field ##
			$oInputField = new html_inputfield( array('name' => $sFieldId, 'id' => $sFieldId, 'size' => 50) );
			
			// add the new input filed to the argument list ##
			$args['blocks']['main']['options']['cloudview_pixlr_save_url'] = array(
						'title' => html::label($sFieldId, Q($this->gettext('pixlrsaveurl'))),
						'content' => $oInputField->show($sPixlrSaveUrl),
			);
		}
		
		// add access key input field ##
		if (!in_array('cloudview_access_key', $aDontOverride)) { // check that our configuration is not disabled ##
			$sFieldId = '_cloudview_access_key';
			// get the current value ##
			$sAccessKey = $oRCmail->config->get('cloudview_access_key');
			// crate the input field ##
			$oInputField = new html_inputfield( array('name' => $sFieldId, 'id' => $sFieldId, 'size' => 50) );
			
			// add the new input filed to the argument list ##
			$args['blocks']['main']['options']['cloudview_access_key'] = array(
						'title' => html::label($sFieldId, Q($this->gettext('accesskey'))),
						'content' => $oInputField->show($sAccessKey),
			);
		}
		
		// add enable editor check box ##
		if (!in_array('cloudview_enable_editor', $aDontOverride)) { // check that our configuration is not disabled ##
			$sFieldId = '_cloudview_enable_editor';
			// get the current value ##
			$bEnableEditor = $oRCmail->config->get('cloudview_enable_editor');
			// crate the input field ##
			$oCheckBox = new html_checkbox( array('name' => $sFieldId, 'id' => $sFieldId, 'value' => 1) );
			
			// add the new input filed to the argument list ##
			$args['blocks']['main']['options']['cloudview_enable_editor'] = array(
						'title' => html::label($sFieldId, Q($this->gettext('enableeditor'))),
						'content' => $oCheckBox->show($bEnableEditor?1:0),
			);
		}
		return $args;
	}
	
	/**
	 * Handler for user preferences save (preferences_save hook)
	 */
	function preferencesSave($args) {
		// add our new preferences to the server settings page ##
		if ($args['section'] != 'server') {
			return $args;
		}
	
		// initialize the rcmail class ##
        $oRCmail = rcmail::get_instance();
		
		// load configuration ##
		$this->load_config();
        
		// get disabled configuration parameters ##
		$aDontOverride = $oRCmail->config->get('dont_override', array());
		
		// zoho save url ##
		if (!in_array('cloudview_zoho_save_url', $aDontOverride)) { // check that our configuration is not disabled ##
			// get the current value ##
			#$sSaveUrl = $oRCmail->config->get('cloudview_zoho_save_url');
			// set the new value ##	
            $args['prefs']['cloudview_zoho_save_url'] = filter_var($_POST['_cloudview_zoho_save_url'], FILTER_SANITIZE_URL);
		}
		
		// pixlr save url ##
		if (!in_array('cloudview_pixlr_save_url', $aDontOverride)) { // check that our configuration is not disabled ##
			// get the current value ##
			#$sSaveUrl = $oRCmail->config->get('cloudview_pixlr_save_url');
			// set the new value ##	
            $args['prefs']['cloudview_pixlr_save_url'] = filter_var($_POST['_cloudview_pixlr_save_url'], FILTER_SANITIZE_URL);
		}
		
		// access key ##
		if (!in_array('cloudview_access_key', $aDontOverride)) { // check that our configuration is not disabled ##
			// get the current value ##
			#$sAccessSecret = $oRCmail->config->get('cloudview_access_key');
			// set the new value ##	
            $args['prefs']['cloudview_access_key'] = filter_var($_POST['_cloudview_access_key'], FILTER_SANITIZE_STRING);
		}
		
		// enable editor ##
		if (!in_array('cloudview_enable_editor', $aDontOverride)) { // check that our configuration is not disabled ##
			// get the current value ##
			#$bEnableEditor = $oRCmail->config->get('cloudview_enable_editor');
			// set the new value ##	
            $args['prefs']['cloudview_enable_editor'] = isset($_POST['_cloudview_enable_editor']) ? true : false;
		}
        return $args;
    }
	
	/**
     * Check message bodies and attachments for supported documents
	 */
    function messageLoad($p)
    {
        $this->oMessage = $p['object'];

        // handle attachments ##
        foreach ( (array)$this->oMessage->attachments as $oAttachment ) {
            if ($this->isSupportedDoc($oAttachment)) {
                $this->aAttachmentData[] = array('mime_id' => $oAttachment->mime_id,
												'mimetype' => $oAttachment->mimetype,
												'filename' => $oAttachment->filename
												);
            }
        }
        
		// debug stuff ##
		#var_dump($this->oMessage->attachments);
		
        if ($this->aAttachmentData)
            $this->add_texts('localization');
    }

    /**
     * This callback function adds a box below the message content
     * if there is a supported document available
     */
    function htmlOutput($p) {
        $bAttachScript = false;
       
        foreach ($this->aAttachmentData as $aDocumentInfo) {
			$aJsonDocument[document] = $aDocumentInfo;
			
			// get the icon ##
			if (mimeHelper::isMimeTypeText($aDocumentInfo['mimetype'])) {
				$icon = 'plugins/cloudview/' .$this->local_skin_path(). '/x-office-document.png';
			}
			
			if (mimeHelper::isMimeTypeSpreadsheet($aDocumentInfo['mimetype'])) {
				$icon = 'plugins/cloudview/' .$this->local_skin_path(). '/x-office-spreadsheet.png';
			}
			
			if(mimeHelper::isMimeTypePresentation($aDocumentInfo['mimetype'])) {
				$icon = 'plugins/cloudview/' .$this->local_skin_path(). '/x-office-presentation.png';
			}
			
			if(mimeHelper::isMimeTypeImage($aDocumentInfo['mimetype'])) {
				$icon = 'plugins/cloudview/' .$this->local_skin_path(). '/image-x-generic.png';
			}
			
			if(mimeHelper::isMimeTypePdf($aDocumentInfo['mimetype'])) {
				$icon = 'plugins/cloudview/' .$this->local_skin_path(). '/x-application-pdf.png';
			}

            $style = 'margin:0.5em 1em; padding:0.2em 0.5em; border:1px solid #999; '
                .'border-radius:4px; -moz-border-radius:4px; -webkit-border-radius:4px; width: auto';

			// add box below messsage body ##
			$p['content'] .= html::p(array('style' => $style),
								html::a(array('href' => "#",
											'onclick' => "return plugin_cloudview_view_document('".JQ(json_encode($aJsonDocument))."')",
											'title' => $this->gettext('opendocument')
											),
											html::img(array('src' => $icon, 
															'style' => "vertical-align:middle"
															)
											)
								) . ' ' . html::span(null, Q($aDocumentInfo['filename']))
							);
            
			$bAttachScript = true;
        }

        if ($bAttachScript) {
            $this->include_script('js/openDocument.js');
		}
		
        return $p;
    }

	/**
	 * Handler for request action
	 */
	function viewDocument() {
		// tell the plugin API where to search for texts ##
		$this->add_texts('localization', true);
		
		// get the post values ##
		$sUid = get_input_value('_uid', RCUBE_INPUT_POST);
		$aJsonDocument = get_input_value('_info', RCUBE_INPUT_POST);

		// initialize the rcmail class ##
        $oRCmail = rcmail::get_instance();

		// get the attachment as string ##
        if ($sUid && $aJsonDocument) {
            $aDocumentInfo = json_decode($aJsonDocument, true);
            $sDocument = $oRCmail->imap->get_message_part($sUid, $aDocumentInfo['document']['mime_id']); 
        }

		// get Zoho API Key from plugin config ##
		if (!defined('zohoAPIkey')) { 
			$this->load_config();
			$sZohoAPIkey = $oRCmail->config->get('zoho_api_key');
			define('zohoAPIkey', $sZohoAPIkey);
		}
		
		// check if editor mode is enabled ##
		$bEnableEditor = $oRCmail->config->get('cloudview_enable_editor');
		if ($bEnableEditor) {
			$sOpenType = 'edit';
		} else {
			$sOpenType = 'view';
		}
		
        // open document with Zoho or Pixlr ##
		$aJsonResponse = openDocument::loadDocument($sDocument, $aDocumentInfo['document']['filename'], $aDocumentInfo['document']['mimetype'], $sOpenType);
		
		// check the result ##
		$aResult = json_decode($aJsonResponse, true);
		if ($aResult['response']['result'] != 'Success') {
			$oRCmail->output->command('display_message', $this->gettext($aResult['response']['errorCode']), 'error');
		}

        $oRCmail->output->command('plugin.cloudview', array('message' => $aJsonResponse));
		$oRCmail->output->send();
	}

    /**
     * Checks if specified message part is a document supported by Zoho
     * @param rcube_message_part Part object
     * @return boolean True if part is a document supported by Zoho
     */
    function isSupportedDoc($oAttachment) {

		// use file name suffix with hard-coded mime-type map ##
		$aMimeExt = @include(RCMAIL_CONFIG_DIR . '/mimetypes.php');
		$sFileSuffix = pathinfo($oAttachment->filename, PATHINFO_EXTENSION);
		if (is_array($aMimeExt)) {
			$sMimeType = $aMimeExt[$sFileSuffix];
		}

		return ( mimeHelper::isMimeTypeText($oAttachment->mimetype) || mimeHelper::isMimeTypeText($sMimeType) ||
				mimeHelper::isMimeTypeSpreadsheet($oAttachment->mimetype) || mimeHelper::isMimeTypeSpreadsheet($sMimeType) ||
				mimeHelper::isMimeTypePresentation($oAttachment->mimetype) || mimeHelper::isMimeTypePresentation($sMimeType) ||
				mimeHelper::isMimeTypeImage($oAttachment->mimetype) || mimeHelper::isMimeTypeImage($sMimeType) ||
				mimeHelper::isMimeTypePdf($oAttachment->mimetype) || mimeHelper::isMimeTypePdf($sMimeType)
				);
	}
}
?>