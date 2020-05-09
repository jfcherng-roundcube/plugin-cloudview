const $ = global.$;
const rcmail = global.rcmail;

const config = rcmail.env['cloudview.config'] || {};
const prefs = rcmail.env['cloudview.prefs'] || {};

let sortableViewers;

/**
 * Get the full viewer order.
 *
 * @return {Number[]} The full viewer order.
 */
const getFullViewerOrder = () => {
  // the user viewer order can be incomplete
  // if there is a new viewer added into this plugin
  // or an old viewer gets removed from this plugin
  const userViewerOrder = (prefs.viewer_order || '').split(/,/g).map((id) => parseInt(id));
  const allViewerIds = $('#_cloudview_viewer_order li')
    .toArray()
    .map((dom) => parseInt(dom.getAttribute('data-id')))
    .sort();

  // the returned list should contain all available viewers in the preferred order
  return (
    [...userViewerOrder, ...allViewerIds]
      // remove invalid
      .filter((v) => allViewerIds.indexOf(v) !== -1)
      // unique (code size: don't use ES6 Set)
      .filter((v, idx, self) => self.indexOf(v) === idx)
  );
};

/**
 * Inject data into a HTML form.
 *
 * @param {string}                 formId     The form identifier
 * @param {Object.<string,string>} dataObject The data object
 */
const injectFormData = (formId, dataObject) => {
  for (let [k, v] of Object.entries(dataObject)) {
    $(`#${formId}`).append(`<input
      type="hidden"
      name="${k.replace(/"/g, '\\"')}"
      value="${v.replace(/"/g, '\\"')}"
    >`);
  }
};

$(() => {
  const viewerOrderListDom = $('#_cloudview_viewer_order')?.[0];

  if (viewerOrderListDom) {
    // @see https://github.com/SortableJS/Sortable#options
    sortableViewers = Sortable.create(viewerOrderListDom, {
      animation: 120,
    });

    sortableViewers.sort(getFullViewerOrder());
  } else {
    console.error('cloudview: DOM "#_cloudview_viewer_order" is not found');
  }
});

rcmail.addEventListener('init', (evt) => {
  rcmail.register_command(
    'plugin.cloudview.settings-save',
    () => {
      injectFormData('cloudview-form', {
        _cloudview_viewer_order: sortableViewers.toArray().join(','),
        _is_saved: '1',
      });

      rcmail.gui_objects['cloudview-form'].submit();
    },
    true
  );
});
