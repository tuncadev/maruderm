(function () {
    'use strict';

    const config = window.MarudermProductPricing || {};

    function number(value) {
        const normalized = String(value || '').trim().replace(/\s+/g, '').replace(',', '.');
        if (normalized === '') {
            return null;
        }

        const parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : NaN;
    }

    function validate(cost, minimum, regular, sale) {
        const values = [cost, minimum, regular, sale];
        if (values.some((value) => Number.isNaN(value) || (value !== null && value < 0))) {
            return false;
        }
        if ((cost === null) !== (minimum === null)) {
            return false;
        }
        if (cost !== null && minimum < cost) {
            return false;
        }
        if (minimum !== null && regular !== null && minimum > regular) {
            return false;
        }
        if (sale === null) {
            return true;
        }

        return cost !== null
            && minimum !== null
            && regular !== null
            && sale >= cost
            && sale >= minimum
            && sale < regular;
    }

    function validateRow(row, selectors) {
        const fields = selectors.map((selector) => row.querySelector(selector));
        if (fields.some((field) => !field)) {
            return true;
        }

        const valid = validate(...fields.map((field) => number(field.value)));
        fields.forEach((field) => field.setCustomValidity(valid ? '' : (config.invalidMessage || 'Invalid product pricing.')));
        row.classList.toggle('has-pricing-error', !valid);

        return valid;
    }

    function validateProductEditor() {
        const panel = document.querySelector('#general_product_data');
        if (!panel) {
            return true;
        }

        return validateRow(panel, ['#_cogs_value', '#_maruderm_minimum_price', '#_regular_price', '#_sale_price']);
    }

    function validateVariations() {
        let valid = true;
        document.querySelectorAll('.woocommerce_variation').forEach((row) => {
            const rowValid = validateRow(row, [
                '[name^="variable_cost_value"]',
                '[name^="variable_maruderm_minimum_price"]',
                '[name^="variable_regular_price"]',
                '[name^="variable_sale_price"]',
            ]);
            valid = rowValid && valid;
        });

        return valid;
    }

    function validateSettingsRows() {
        let valid = true;
        document.querySelectorAll('[data-mpp-row]').forEach((row) => {
            const cost = row.querySelector('[data-mpp-cost]');
            const minimum = row.querySelector('[data-mpp-minimum]');
            const regular = number(row.querySelector('[data-mpp-regular]')?.dataset.mppRegular);
            const sale = number(row.querySelector('[data-mpp-sale]')?.dataset.mppSale);
            if (!cost || !minimum) {
                return;
            }

            const rowValid = validate(number(cost.value), number(minimum.value), regular, sale);
            [cost, minimum].forEach((field) => field.setCustomValidity(rowValid ? '' : (config.invalidMessage || 'Invalid product pricing.')));
            row.classList.toggle('has-pricing-error', !rowValid);
            valid = rowValid && valid;
        });

        return valid;
    }

    function relabelCostField() {
        const input = document.querySelector('#_cogs_value');
        const label = input ? document.querySelector('label[for="_cogs_value"]') : null;
        if (!label) {
            return;
        }

        const currency = label.textContent.match(/\([^)]*\)/)?.[0] || '';
        label.textContent = `${config.costLabel || 'Cost price (private)'} ${currency}`.trim();
    }

    function bind() {
        relabelCostField();
        document.addEventListener('input', (event) => {
            if (!(event.target instanceof HTMLInputElement)) {
                return;
            }
            validateProductEditor();
            validateVariations();
            validateSettingsRows();
        });

        document.querySelectorAll('#post, [data-mpp-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const valid = validateProductEditor() && validateVariations() && validateSettingsRows();
                if (!valid) {
                    event.preventDefault();
                    form.querySelector(':invalid')?.reportValidity();
                }
            });
        });

        document.addEventListener('woocommerce_variations_loaded', validateVariations);
        if (window.jQuery) {
            window.jQuery(document.body).on('woocommerce_variations_loaded woocommerce_variations_added', validateVariations);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
