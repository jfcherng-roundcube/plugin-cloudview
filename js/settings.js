const rcmail = global.rcmail;

if (rcmail) {
  rcmail.addEventListener('init', (evt) => {
    rcmail.register_command(
      'plugin.cloudview-save',
      () => rcmail.gui_objects['cloudview-form'].submit(),
      true
    );
  });
}
