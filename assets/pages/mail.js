const rcmail = global.rcmail;

const config = rcmail.env['cloudview.config'] || {};
const prefs = rcmail.env['cloudview.prefs'] || {};

/**
 * Get the attachment information by given ID.
 *
 * @param  {string} attachmentId The attachment identifier
 * @return {?Object.<string, any>} The attachment information.
 */
const getAttachmentInfo = (attachmentId) =>
  rcmail.env?.['cloudview.attachments']?.[attachmentId] || null;

/**
 * Open the attachment with cloud viewer.
 *
 * @param {?Object.<string, any>} attachmentInfo The attachment information
 */
const cloudview_openAttachment = (attachmentInfo) => {
  rcmail.http_post(
    'plugin.cloudview.view',
    {
      _callback: 'plugin.cloudview.view-callback',
      _uid: rcmail.env.uid,
      _info: JSON.stringify(attachmentInfo),
    },
    rcmail.set_busy(true, 'loading')
  );
};

rcmail.addEventListener('init', (evt) => {
  // register the main command
  rcmail.register_command(
    'plugin.cloudview.open-attachment',
    () => {
      let attachmentId = rcmail.env['cloudview.target-attachment-id'];
      let attachment = getAttachmentInfo(attachmentId);

      cloudview_openAttachment(attachment);
    },
    false // disabled by default
  );

  // enable/disable the button in 'attachmentmenu'
  rcmail.addEventListener('menu-open', (evt) => {
    if (evt.name !== 'attachmentmenu') return;

    let attachmentId = evt.props.id;
    let attachment = getAttachmentInfo(evt.props.id);

    rcmail.set_env('cloudview.target-attachment-id', attachmentId);
    rcmail.enable_command('plugin.cloudview.open-attachment', attachment['is_supported']);
  });

  // open the cloud viewer window
  rcmail.addEventListener('plugin.cloudview.view-callback', (response) => {
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
});

// expose
global.cloudview_openAttachment = cloudview_openAttachment;
