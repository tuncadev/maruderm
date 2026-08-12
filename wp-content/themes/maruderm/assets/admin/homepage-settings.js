(() => {
  'use strict';

  document.querySelectorAll('[data-maruderm-media]').forEach((control) => {
    const input = control.querySelector('[data-media-input]');
    const preview = control.querySelector('[data-media-preview]');
    const choose = control.querySelector('[data-media-choose]');
    const clear = control.querySelector('[data-media-clear]');

    if (!input || !preview || !choose || !clear || !window.wp?.media) {
      return;
    }

    choose.addEventListener('click', () => {
      const frame = window.wp.media({
        title: 'Choose an image',
        button: { text: 'Use this image' },
        library: { type: 'image' },
        multiple: false,
      });

      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        const thumbnail = attachment.sizes?.thumbnail?.url || attachment.url;

        input.value = String(attachment.id);
        preview.src = thumbnail;
        preview.hidden = false;
        clear.hidden = false;
      });

      frame.open();
    });

    clear.addEventListener('click', () => {
      input.value = '0';
      preview.src = '';
      preview.hidden = true;
      clear.hidden = true;
    });
  });
})();
