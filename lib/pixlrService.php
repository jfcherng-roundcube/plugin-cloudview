<?php
/**
 * @version $Id$
 * upload a document to Pixlr's web service 
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */
 
class pixlrService {
	var $sDocument;
	var $sFileName;
	var $sMimeType;
	var $sOpenType;

	/**
	 * send the document to Pixlr's web service
	 * @param string $sDocument the document
	 * @param string $sFileName the filename
	 * @param string $sMimeType the document MIME type
	 * @param string $sOpenType view or edit the document
	 * @return string a JSON string which contains the URL to view or edit the document in the Pixlr Editor
	 */
 
	function sendDocument($sDocument, $sFileName, $sMimeType, $sOpenType) {

		$sFileSuffix = pathinfo($sFileName, PATHINFO_EXTENSION);
		
		$sTmpFile = INSTALL_PATH . 'temp' . "/" . uniqid('cloudviewTmp_') . "." . $sFileSuffix;
		file_put_contents($sTmpFile, $sDocument);
		
		// check open parameter ##
		if (!$sOpenType == 'view' || !$sOpenType == 'edit') {
			appendLogEntry::addLogEntry( "no valid open type given - set to view", "pixlrService" );
			$sOpenType = 'view';
		}
		
		// check the if the document is an image ##
		if ( !mimeHelper::isMimeTypeImage($sMimeType) ) {
			appendLogEntry::addLogEntry( "Document type not supported", "pixlrService" );
			return '{"response":{"errorCode":"unsupporteddoctype"}}';
		}
				
		// parameters for pixlr editor ##
		$aPostdata = array('image' => "@" . $sTmpFile);	
			
		// Pixlr API upload url ##	
		$sPixlrUrl = "http://pixlr.com/store/";
		
		// POST request with curl ## 
		$ch = curl_init($sPixlrUrl);
		$timeout = 15;
		#curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $aPostdata);
		$result = curl_exec($ch);
		curl_close ($ch);
		
		// delete temporary file ##
		@unlink($sTmpFile);
		
		// if everything works well we will get an image id like this "4cebaa22eddc6c0ab2000aff." ##
		if (!$result) {
			appendLogEntry::addLogEntry( "Image upload failed.", "pixlrService" );
			return '{"response":{"errorCode":"imageuploadfailed"}}';
		}

		// Pixlr available languages ##
		$aAvailableLanguages = array('de', 'en', 'dk', 'he', 'es', 'fr', 'it', 'pl', 'ro', 'nl', 'ru', 'uk', 'sv', 'sl', 'el', 'cs', 'th', 'tr', 'jp');
		$sLanguage = clientLanguage::getClientLanguage($aAvailableLanguages); // set Pixlr language ##
		
		// parameters for Zoho Writer, Sheet, Show ##
		$oRCmail = rcmail::get_instance(); // initialize the rcmail class ##
		$this->load_config(); // load configuration ##
			
		$sUniqueId = $oRCmail->config->get('cloudview_access_key');
		$sSaveUrl = $oRCmail->config->get('cloudview_pixlr_save_url');
			
		// check if unique id ist set ##
		if (!$sUniqueId) {
			$sUniqueId = md5(uniqid(rand(), true)); // generate a random unique id ##
		}
		
		// set Pixlr save url ##
		if ($sSaveUrl) {
			// add the unique id as parameter to the save url and Pixlr will it send back if the image is saved ##
			$sPixlrSaveUrl = $sSaveUrl . '?id=' . $sUniqueId;
			$sPixlrSaveUrl = urlencode($sPixlrSaveUrl);
		} else {
			$sOpenType = 'view';
		}
		
		// construct the url to open the Pixlr editor ##
		if ($sOpenType == 'view') {
			$sPixlrOpenUrl = "http://www.pixlr.com/editor/?image=http://pixlr.com/_temp/" . trim($result) . "&loc=" . $sLanguage . "&title=" . $sFileName . "&locktarget=false";
		} elseif ($sOpenType == 'edit') {
			$sPixlrOpenUrl = "http://www.pixlr.com/editor/?image=http://pixlr.com/_temp/" . trim($result) . "&loc=" . $sLanguage . "&referer=RC&title=" . $sFileName . "&method=POST&target=" . $sPixlrSaveUrl . "&locktype=source&locktitle=true&locktarget=true&credentials=true";
		}
		
		// construct a Zoho Viewer API compatible json string ##
		$aJsonArray = array('url' => $sPixlrOpenUrl,
							'result' => 'Success');
							
		$aJsonResponse[response] = $aJsonArray;
		// return a json string ##
		appendLogEntry::addLogEntry( json_encode($aJsonResponse), "pixlrService" );
		return json_encode($aJsonResponse);
	}
}
?>