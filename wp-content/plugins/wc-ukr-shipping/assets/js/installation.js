/**
 * Registers this installation on the platform once, on the first admin page load
 * after activation. Fire-and-forget: nothing is reported to the store admin.
 */
(function ($) {
  let globals = window.wc_ukr_shipping_globals;

  if ( ! globals || ! globals.smarty_parcel || globals.smarty_parcel.installationId) {
    return;
  }

  $.post(globals.ajaxUrl, {
    action: 'wcus_smartyparcel_register_installation',
    _token: globals.nonce
  });
})(jQuery);
