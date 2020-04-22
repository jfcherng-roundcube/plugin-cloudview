const rcmail = global.rcmail;
const scrHeight = window.innerWidth;
const scrWidth = window.innerHeight;

// add 'plugin.cloudview' event listener
rcmail.addEventListener('plugin.cloudview', (response) => {
  let url = response.message.url;
  let popupId = new Date().getTime();
  let width = `width=${scrWidth}`;
  let height = `height=${scrHeight}`;

  window.open(
    url,
    popupId,
    width,
    height,
    'menubar=no, toolbar=no, directories=no, location=no, scrollbars=no, resizable=yes, status=no'
  );
});

// this function calls "viewDocument" in cloudview.php
global.plugin_cloudview_view_document = (documentInfo) => {
  let lock = rcmail.set_busy(true, 'loading');

  rcmail.http_post(
    'plugin.cloudview',
    `_uid=${rcmail.env.uid}&_mbox=${urlencode(rcmail.env.mailbox)}&_info=${urlencode(
      JSON.stringify(documentInfo)
    )}`,
    lock
  );

  return false;
};
