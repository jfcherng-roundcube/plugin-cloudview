const rcmail = global.rcmail;

const plugin_name = 'cloudview';
const config = rcmail.env[`${plugin_name}.config`] ?? {};
const prefs = rcmail.env[`${plugin_name}.prefs`] ?? {};

/**
 * Get the attachment information by given ID.
 *
 * @param  {string} attachmentId The attachment identifier
 * @return {?Object.<string, any>} The attachment information.
 */
const getAttachmentInfo = (attachmentId) =>
  rcmail.env[`${plugin_name}.attachments`]?.[attachmentId] || null;

/**
 * Open the attachment with cloud viewer.
 *
 * @param {?Object.<string, any>} attachment The attachment information
 */
const cloudview_openAttachment = (attachment) => {
  rcmail.http_post(
    `plugin.${plugin_name}.view`,
    {
      _callback: `plugin.${plugin_name}.view-callback`,
      _attachment: JSON.stringify(attachment),
    },
    rcmail.set_busy(true, 'loading')
  );
};

rcmail.addEventListener('init', (evt) => {
  // register the main command
  rcmail.register_command(
    `plugin.${plugin_name}.open-attachment`,
    () => {
      let attachmentId = rcmail.env[`${plugin_name}.target-attachment-id`];
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

    rcmail.set_env(`${plugin_name}.target-attachment-id`, attachmentId);
    rcmail.enable_command(`plugin.${plugin_name}.open-attachment`, attachment.isSupported);
  });

  // open the cloud viewer window
  rcmail.addEventListener(`plugin.${plugin_name}.view-callback`, (response) => {
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
