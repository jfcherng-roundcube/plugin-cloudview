/**
 * @version $Id$
 * show PDF document in the message window
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */

var pdf = (function(that) {
	var iMimeId = null;
	var _this = that;
	var oPdfDoc = null;
	var iPageNum = 1;
	var oCanvas = null;
	var oCtx = null;
	
	function show() {
		iMimeId = $('.pdfviewer-canvas', _this).data('id');
		oCanvas = $('.pdfviewer-canvas', _this).get(0);
		oCtx = oCanvas.getContext('2d');
		var sUrl = '?_task=mail&_uid=' + rcmail.env.uid + '&_mbox=' + rcmail.env.mailbox + '&_action=get&_part=' + iMimeId;
		
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
				bindButtons();
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
				var iRatio = $(_this).width() / oViewport.width;
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
	
		$('.page_num', _this).text(iPageNum);
		$('.page_count', _this).text(oPdfDoc.numPages);
		
		// hide loading message ##
		rcmail.clear_messages();
	}

	function bindButtons() {
		$('.next-page', _this).click(function() {
			if(iPageNum >= oPdfDoc.numPages) {
				return;
			}
			
			iPageNum++;
			renderPage(iPageNum);
		});
		
		$('.prev-page', _this).click(function() {
			if(iPageNum <= 1) {
				return;
			}
			
			iPageNum--;
			renderPage(iPageNum);
		});
	}
	
	return {
		show: show
	};
});

$(document).ready(function() {
	$('.pdfviewer-container').bind('showPdf', function(e) {
		oPdf = new pdf(this);
		oPdf.show();
	});

	$('.pdfviewer-container').trigger('showPdf');
});
