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
  const userViewerOrder = (prefs.viewer_order || '').split(/,/g);
  const allViewerIds = $('#_cloudview_viewer_order li')
    .toArray()
    .map((dom) => $(dom).attr('data-id'))
    .sort();

  // the returned list should contain all available viewers in the preferred order
  return (
    [...userViewerOrder, ...allViewerIds]
      // remove invalid
      .filter((value, index, self) => {
        // code size: don't use ES6 includes()
        return allViewerIds.indexOf(value) !== -1;
      })
      // unique (code size: don't use ES6 Set)
      .filter((value, index, self) => {
        return self.indexOf(value) === index;
      })
  );
};

$(() => {
  const viewerOrderListDom = $('#_cloudview_viewer_order')?.[0];

  if (viewerOrderListDom) {
    // @see https://github.com/SortableJS/Sortable#options
    sortableViewers = Sortable.create(viewerOrderListDom, {
      animation: 150,
      ghostClass: '', // "sortable-chosen" by default
    });

    sortableViewers.sort(getFullViewerOrder());
  }
});

rcmail.addEventListener('init', (evt) => {
  rcmail.register_command(
    'plugin.cloudview.settings-save',
    () => {
      const viewerOrder = sortableViewers.toArray();

      $('#cloudview-form').append(`<input
        type="hidden"
        name="_cloudview_viewer_order"
        value="${viewerOrder.join(',')}"
      >`);

      rcmail.gui_objects['cloudview-form'].submit();
    },
    true
  );
});
