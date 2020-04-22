const $ = global.$;

// from plugin
let cloudview_attachmentInfos, cloudview_attachmentInfosById;

$(() => {
  cloudview_attachmentInfos = global.cloudview_attachmentInfos;

  cloudview_attachmentInfosById = {};
  cloudview_attachmentInfos.forEach((el) => {
    cloudview_attachmentInfosById[el.mime_id] = el;
  });
  global.cloudview_attachmentInfosById = cloudview_attachmentInfosById;

  // add menu link for each attachment
  $('#attachment-list > li').each(function () {
    attachmentmenuAppend(this);
  });
});

// append drop-icon to attachments list item (to invoke attachment menu)
function attachmentmenuAppend(item) {
  let $item = $(item);
  let attachmentId = parseInt($item.attr('id').replace(/^attach/g, ''));

  if (!(attachmentId in cloudview_attachmentInfosById)) {
    return;
  }

  $item.addClass('with-preview');
  $item.append(`
    <a
      title="Preview"
      href="#"
      onclick="plugin_cloudview_view_document({ document: cloudview_attachmentInfosById[${attachmentId}] })"
      class="cloudview-preview-link"
    ></a>
  `);
}
