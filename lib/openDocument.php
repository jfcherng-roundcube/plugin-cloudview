<?php
/**
 * @version $Id$
 * open a document with Zoho or Pixlr
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */
 
class openDocument {
	var $sDocument;
	var $sFileName;
	var $sMimeType;
	var $sOpenMode;
	
	/**
	 * determine the file type and open the document
	 * @param string $sDocument the document
	 * @param string $sFileName the filename
	 * @param string $sMimeType the document MIME type
	 * @param string $sOpenMode view or edit the document
	 * @return zohoService|pixlrService
	 */
 
	function loadDocument($sDocument, $sFileName, $sMimeType, $sOpenMode) {
		
		if ( mimeHelper::isMimeTypeText($sMimeType) || 
			mimeHelper::isMimeTypeSpreadsheet($sMimeType) || 
			mimeHelper::isMimeTypePresentation($sMimeType) {
				// open document with Zoho ##
				$sResult = zohoService::sendDocument($sDocument, $sFileName, $sMimeType, $sOpenMode);
				$sErrorDump = print_r($sResult, true);
				appendLogEntry::addLogEntry( $sErrorDump, "openDocument" );
				return $sResult;
		} elseif ( mimeHelper::isMimeTypeImage($sMimeType) ) {
				// open document with Pixlr ##
				$sResult = pixlrService::sendDocument($sDocument, $sFileName, $sMimeType, $sOpenMode);
				$sErrorDump = print_r($sResult, true);
				appendLogEntry::addLogEntry( $sErrorDump, "openDocument" );
				return $sResult;
		} else {
			appendLogEntry::addLogEntry( "Document type not supported", "openDocument" );
			return '{"response":{"errorCode":"unsupporteddoctype"}}';
		}
	}
}
?>