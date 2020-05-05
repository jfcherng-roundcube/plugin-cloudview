const $ = global.$;
const rcmail = global.rcmail;

const config = rcmail.env['cloudview.config'] || {};
const prefs = rcmail.env['cloudview.prefs'] || {};

let sortableViewers;

$(() => {
  const $sortables = $('#cloudview-form .viewers.sortable');

  if ($sortables.length) {
    const viewerOrder = (prefs.viewer_order || '').split(/,/g);

    // @see https://github.com/SortableJS/Sortable#options
    sortableViewers = Sortable.create($sortables[0], {
      animation: 150,
      ghostClass: 'ghost-background',
    });

    sortableViewers.sort(viewerOrder);
  }
});

rcmail.addEventListener('init', (evt) => {
  rcmail.register_command(
    'plugin.cloudview.settings-save',
    () => {
      const viewerOrder = sortableViewers.toArray();

      $('#cloudview-form').append(`<input
        type="hidden"
        id="cloudview_viewer_order"
        name="_cloudview_viewer_order"
        value="${viewerOrder.join(',')}"
      >`);

      rcmail.gui_objects['cloudview-form'].submit();
    },
    true
  );
});
