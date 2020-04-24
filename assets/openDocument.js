const rcmail = global.rcmail;

// add 'plugin.cloudview' event listener
rcmail.addEventListener('plugin.cloudview-view', (response) => {
  let windowSpecs = {
    width: window.innerWidth,
    height: window.innerHeight,
    directories: 'no',
    location: 'no',
    menubar: 'no',
    resizable: 'yes',
    scrollbars: 'no',
    status: 'no',
    toolbar: 'no',
  };

  window.open(
    response.message.url,
    new Date().getTime(),
    Object.keys(windowSpecs)
      .map((key) => `${key}=${windowSpecs[key]}`)
      .join(',')
  );
});

// this function calls "viewDocument" in cloudview.php
const cloudview_viewDocument = (attachmentInfo) => {
  rcmail.http_post(
    'plugin.cloudview-view',
    buildQueryString({
      _uid: rcmail.env.uid,
      _mbox: rcmail.env.mailbox,
      _info: JSON.stringify(attachmentInfo),
    }),
    rcmail.set_busy(true, 'loading')
  );

  return false;
};

const buildQueryString = (params) => {
  return Object.keys(params)
    .map((key) => encodeURIComponent(key) + '=' + encodeURIComponent(params[key]))
    .join('&');
};

// globals
global.cloudview_viewDocument = cloudview_viewDocument;
