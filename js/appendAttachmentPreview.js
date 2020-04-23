const $ = global.$;
const rcmail = global.rcmail;

// the "mime_id" mapped version of 'cloudview_attachmentInfos'
global.cloudview_attachmentInfosById = {};

$(() => {
  // 'cloudview_attachmentInfos' to generate 'cloudview_attachmentInfosById'
  global.cloudview_attachmentInfos.forEach((el) => {
    global.cloudview_attachmentInfosById[el.mime_id] = el;
  });

  // add menu link for each attachment
  $('#attachment-list > li').each(function () {
    attachmentmenuAppend(this);
  });
});

// append drop-icon to attachments list item (to invoke attachment menu)
function attachmentmenuAppend(item) {
  let $item = $(item);
  let attachmentId = parseInt($item.attr('id').replace(/^attach/g, ''));

  if (!(attachmentId in global.cloudview_attachmentInfosById)) {
    return;
  }

  $item.addClass('with-preview').append(`
    <a
      title="${rcmail.labels['cloudview.open_document']}"
      href="#"
      onclick="plugin_cloudview_view_document({ document: cloudview_attachmentInfosById[${attachmentId}] })"
      class="cloudview-preview-link"
    ></a>
  `);
}
