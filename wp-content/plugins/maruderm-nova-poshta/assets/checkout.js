(function ($) {
  'use strict';

  function isUaCountry() {
    var country = ($('#billing_country').val() || '').toUpperCase();
    return country === (MarudermNovaPoshta.countryCode || 'UA');
  }

  function isNovaPoshtaShippingSelected() {
    var selected = $('input[name^="shipping_method"]').filter(':checked').val();
    if (!selected) {
      selected = $('select[name^="shipping_method"]').val();
    }
    if (!selected) {
      return false;
    }

    return String(selected).indexOf(MarudermNovaPoshta.shippingMethodId || 'maruderm_nova_poshta') !== -1;
  }

  function clearSelect($select, placeholder) {
    $select.empty();
    $select.append($('<option>', { value: '', text: placeholder }));
  }

  function toggleNovaPoshtaFields() {
    var visible = isUaCountry() && isNovaPoshtaShippingSelected();
    var fieldIds = ['nova_poshta_area', 'nova_poshta_settlement_id', 'nova_poshta_division_id'];

    fieldIds.forEach(function (fieldId) {
      var $wrapper = $('#' + fieldId + '_field');
      if (!$wrapper.length) {
        return;
      }

      if (visible) {
        $wrapper.show();
      } else {
        $wrapper.hide();
      }
    });

    return visible;
  }

  function fillAreas(items) {
    var $area = $('#nova_poshta_area');
    clearSelect($area, MarudermNovaPoshta.strings.selectArea);

    items.forEach(function (item) {
      $area.append($('<option>', {
        value: String(item),
        text: String(item)
      }));
    });
  }

  function fillSettlements(items) {
    var $settlement = $('#nova_poshta_settlement_id');
    clearSelect($settlement, MarudermNovaPoshta.strings.selectCity);

    items.forEach(function (item) {
      var title = item.name;
      if (item.region) {
        title += ' (' + item.region + ')';
      }

      $settlement.append($('<option>', {
        value: String(item.id),
        text: title
      }));
    });
  }

  function fillDivisions(items) {
    var $division = $('#nova_poshta_division_id');
    clearSelect($division, MarudermNovaPoshta.strings.selectOffice);

    items.forEach(function (item) {
      var title = '#' + (item.number || '-') + ' ' + (item.name || '');
      if (item.address) {
        title += ' - ' + item.address;
      }

      $division.append($('<option>', {
        value: String(item.id),
        text: title
      }));
    });
  }

  function loadAreas() {
    if (!toggleNovaPoshtaFields()) {
      return;
    }

    $.getJSON(MarudermNovaPoshta.areasEndpoint)
      .done(function (response) {
        fillAreas((response && response.items) || []);
      });
  }

  function loadSettlements(area) {
    var $settlement = $('#nova_poshta_settlement_id');
    var $division = $('#nova_poshta_division_id');

    clearSelect($settlement, MarudermNovaPoshta.strings.selectCity);
    clearSelect($division, MarudermNovaPoshta.strings.selectOffice);

    if (!toggleNovaPoshtaFields() || !area) {
      return;
    }

    $.getJSON(MarudermNovaPoshta.settlementsEndpoint, { area: area })
      .done(function (response) {
        fillSettlements((response && response.items) || []);
      });
  }

  function loadDivisions(settlementId) {
    var $division = $('#nova_poshta_division_id');
    clearSelect($division, MarudermNovaPoshta.strings.selectOffice);

    if (!toggleNovaPoshtaFields() || !settlementId) {
      return;
    }

    $.getJSON(MarudermNovaPoshta.divisionsEndpoint, { settlement_id: settlementId })
      .done(function (response) {
        fillDivisions((response && response.items) || []);
      });
  }

  $(document).ready(function () {
    var $area = $('#nova_poshta_area');
    var $settlement = $('#nova_poshta_settlement_id');
    var $division = $('#nova_poshta_division_id');

    if (!$area.length || !$settlement.length || !$division.length) {
      return;
    }

    toggleNovaPoshtaFields();
    loadAreas();

    $(document.body).on('updated_checkout', function () {
      toggleNovaPoshtaFields();
    });

    $(document).on('change', 'input[name^="shipping_method"], select[name^="shipping_method"], #billing_country', function () {
      clearSelect($area, MarudermNovaPoshta.strings.selectArea);
      clearSelect($settlement, MarudermNovaPoshta.strings.selectCity);
      clearSelect($division, MarudermNovaPoshta.strings.selectOffice);
      loadAreas();
    });

    $area.on('change', function () {
      loadSettlements($(this).val());
    });

    $settlement.on('change', function () {
      loadDivisions($(this).val());
    });
  });
})(jQuery);
