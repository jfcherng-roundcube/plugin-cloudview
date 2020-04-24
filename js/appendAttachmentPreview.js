const $ = global.$;
const rcmail = global.rcmail;

const cloudview_findAttachmentByMimeId = (mime_id) => {
  let attachments = rcmail.env['cloudview_attachments'] || [];

  return attachments.find((attachment) => attachment.mime_id === String(mime_id));
};

// append drop-icon to attachments list item (to invoke attachment menu)
const attachmentmenuAppend = (item) => {
  let $item = $(item);
  let attachmentId = $item.attr('id').replace(/^attach/g, '');
  let attachment = cloudview_findAttachmentByMimeId(attachmentId);

  if (!attachment || !attachment['is_supported']) {
    return;
  }

  $item.addClass('with-preview').append(`
    <a
      title="${rcmail.labels['cloudview.open_document']}"
      href="#"
      onclick="plugin_cloudview_view_document(cloudview_findAttachmentByMimeId('${attachmentId}'))"
      class="cloudview-preview-link"
    ></a>
  `);
};

$(() => {
  // add menu link for each attachment
  $('#attachment-list > li').each(function () {
    attachmentmenuAppend(this);
  });
});

// globals
global.cloudview_findAttachmentByMimeId = cloudview_findAttachmentByMimeId;
