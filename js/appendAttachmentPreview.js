const $ = global.$;
const rcmail = global.rcmail;

const cloudview_findAttachmentByMimeId = (mime_id) => {
  let attachments = rcmail.env['cloudview_attachmentInfos'] || [];

  return attachments.find((attachment) => attachment.mime_id === mime_id);
};

// append drop-icon to attachments list item (to invoke attachment menu)
const attachmentmenuAppend = (item) => {
  let $item = $(item);
  let attachmentId = $item.attr('id').replace(/^attach/g, '');

  if (!global.cloudview_findAttachmentByMimeId(attachmentId)) {
    return;
  }

  $item.addClass('with-preview').append(`
    <a
      title="${rcmail.labels['cloudview.open_document']}"
      href="#"
      onclick="plugin_cloudview_view_document({ document: cloudview_findAttachmentByMimeId('${attachmentId}') })"
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
