// generate a unique id
function uniqId() {
  var newDate = new Date();
  return newDate.getTime();
}

// add rc event listener
rcmail.addEventListener('plugin.cloudview', openDocument);
// the event listener callback funktion to open the document
function openDocument(response) {
  var url = response.message.url;
  var popupId = uniqId();
  var width = 'width=' + scrWidth;
  var height = 'height=' + scrHeight;
  window.open(
    url,
    popupId,
    width,
    height,
    'menubar=no, toolbar=no, directories=no, location=no, scrollbars=no, resizable=yes, status=no'
  );
}

// this function calls "viewDocument" in cloudview.php
function plugin_cloudview_view_document(documentInfo) {
  var lock = rcmail.set_busy(true, 'loading');
  rcmail.http_post(
    'plugin.cloudview',
    '_uid=' +
      rcmail.env.uid +
      '&_mbox=' +
      urlencode(rcmail.env.mailbox) +
      '&_info=' +
      urlencode(documentInfo),
    lock
  );
  return false;
}
