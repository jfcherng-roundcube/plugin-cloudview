/**
 * @version $Id$
 * show PDF document in the message window
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */

var oPdfDoc = null;
var iPageNum = 1;
var oCanvas = null;
var oCtx = null;

function showPdf(iMimeId) {
	var sUrl = '?_task=mail&_uid=' + rcmail.env.uid + '&_mbox=' + rcmail.env.mailbox + '&_action=get&_part=' + iMimeId;
	oCanvas = document.getElementById('pdfviewer-canvas');
	oCtx = oCanvas.getContext('2d');
	
	/**
	 * Disable workers to avoid yet another cross-origin issue (workers need the URL of
	 * the script to be loaded, and currently do not allow cross-origin scripts)
	 */
	PDFJS.disableWorker = true;
	
	// show loading message ##
	rcmail.display_message('', 'loading');

	// asynchronously download PDF as an ArrayBuffer ##
	PDFJS.getDocument(sUrl).then(
		function(oData) {
			oPdfDoc = oData;
			renderPage(iPageNum);
		}
	);
}

function renderPage(iPage) {
	// using promise to fetch the page ##
	oPdfDoc.getPage(iPage).then(
		function(oPage) {
			// get viewport size ##
			var oViewport = oPage.getViewport(1);
			// calculate and apply initial scale to fit page to message window ##
			var iRatio = $('#pdfviewer-viewport').width() / oViewport.width;
			// apply initial scale ##
			var oViewport = oPage.getViewport(iRatio);
			oCanvas.height = oViewport.height;
			oCanvas.width = oViewport.width;
	
			var oRenderContext = {
				canvasContext: oCtx,
				viewport: oViewport
			};
			oPage.render(oRenderContext);
	});

	$('#page_num').text(iPageNum);
	$('#page_count').text(oPdfDoc.numPages);
	
	// hide loading message ##
	rcmail.clear_messages();
}

function goPrevious() {
	if(iPageNum <= 1) {
		return;
	}
	
	iPageNum--;
	renderPage(iPageNum);
}

function goNext() {
	if(iPageNum >= oPdfDoc.numPages) {
		return;
	}
	
	iPageNum++;
	renderPage(iPageNum);
}
