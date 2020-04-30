const rcmail = global.rcmail;

rcmail.addEventListener('init', (evt) => {
  rcmail.register_command(
    'plugin.cloudview.settings-save',
    () => rcmail.gui_objects['cloudview-form'].submit(),
    true
  );
});
