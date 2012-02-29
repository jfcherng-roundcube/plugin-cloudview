<?php
/**
 * @version $Id$
 * MIME types supported by cloudview
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */

class mimeHelper {
	var $sMimeType;
	
	/**
	 * supported MIME types
	 * @param string $sMimeType the MIME type
	 * @return mixed the MIME type or false 
	 */

	function isSupportedMimeType($sMimeType) {

		if (self::isMimeTypePlainText($sMimeType) ||
			self::isMimeTypeText($sMimeType) ||
			self::isMimeTypeSpreadsheet($sMimeType) ||
			self::isMimeTypePresentation($sMimeType) ||
			self::isMimeTypePdf($sMimeType) ||
			self::isMimeTypeImage($sMimeType) ||
			self::isMimeTypeCode($sMimeType)) {
		
			return $sMimeType;
		} else {
			return false;
		}
	}
	
	/**
	 * plain text
	 * @param string $sMimeType the MIME type
	 * @return bool true or false 
	 */
	
	function isMimeTypePlainText($sMimeType) {
		$aPlainTextMimeTypes = array(
		   'text/plain' => true, // txt ##
		   'text/html' => true, // html ##
		   'text/csv' => true // csv ##
		);
		
		if (array_key_exists($sMimeType, $aPlainTextMimeTypes)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * text
	 * @param string $sMimeType the MIME type
	 * @return bool true or false 
	 */
	 
	function isMimeTypeText($sMimeType) {
		$aTextMimeTypes = array(
		   'text/rtf' => true, // rtf ##
		   'application/msword' => true, // doc ##
		   'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true, // docx ##
		   'application/vnd.sun.xml.writer' => true, // sxw ##
		   'application/vnd.oasis.opendocument.text' => true // odt ##
		);
		
		if (array_key_exists($sMimeType, $aTextMimeTypes)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * spreadsheet
	 * @param string $sMimeType the MIME type
	 * @return bool true or false 
	 */
	
	function isMimeTypeSpreadsheet($sMimeType) {
		$aSpreadsheetMimeTypes = array(
		   'application/vnd.ms-excel' => true, // xls ##
		   'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => true, // xlsx ##
		   'application/vnd.sun.xml.calc' => true, // sxc ##
		   'application/vnd.oasis.opendocument.spreadsheet' => true //ods ##
		);
		
		if (array_key_exists($sMimeType, $aSpreadsheetMimeTypes)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * presentation
	 * @param string $sMimeType the MIME type
	 * @return bool true or false 
	 */
	
	function isMimeTypePresentation($sMimeType) {
		$aPresentationMimeTypes = array(
		   'application/vnd.ms-powerpoint' => true, // ppt ##
		   'application/vnd.openxmlformats-officedocument.presentationml.presentation' => true, // pptx ##
		   'application/vnd.ms-powerpoint' => true, // pps ##
		   'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => true, // ppsx ##
		   'application/vnd.oasis.opendocument.presentation' => true // odp ##
		);
		
		if (array_key_exists($sMimeType, $aPresentationMimeTypes)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * pdf
	 * @param string $sMimeType the MIME type
	 * @return bool true or false 
	 */
	
	function isMimeTypePdf($sMimeType) {
		$aPdfMimeTypes = array(
			'application/pdf' => true, // pdf ##
			'application/x-pdf' => true, // pdf ##
			'application/acrobat' => true, // pdf ##
			'applications/vnd.pdf' => true, // pdf ##
			'text/x-pdf' => true, // pdf ##
			'text/pdf' => true // pdf ##
		);
		
		if (array_key_exists($sMimeType, $aPdfMimeTypes)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * image
	 * @param string $sMimeType the MIME type
	 * @return bool true or false 
	 */
	 
	function isMimeTypeImage($sMimeType) {
		$aImageMimeTypes = array(
		   'image/gif' => true, // gif ##
		   'image/jpeg' => true, // jpeg ##
		   'image/png' => true, // png ##
		   'image/bmp' => true // bmp ##
	   );
		
		if (array_key_exists($sMimeType, $aImageMimeTypes)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * plan text source code files
	 * @param string $sMimeType the MIME type
	 * @return bool true or false 
	 */
	
	function isMimeTypeCode($sMimeType) {
		$aCodeMimeTypes = array(
		   'text/xml' => true, // xml ##
		   'text/html' => true, // html ##
		   'application/x-javascript' => true, // js ##
		   'application/x-latex' => true, // latex ##
		   'text/css' => true // css ##
	   );
		
		if (array_key_exists($sMimeType, $aCodeMimeTypes)) {
			return true;
		} else {
			return false;
		}
	}
}
?>