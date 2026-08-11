(() => {
    'use strict';

    const rows = Array.from(document.querySelectorAll('[data-mks-row]'));
    const includedCount = document.querySelector('[data-mks-included-count]');
    const fallbackCount = document.querySelector('[data-mks-fallback-count]');

    const updateCounts = () => {
        const included = rows.filter((row) => row.classList.contains('is-included')).length;

        if (includedCount) {
            includedCount.textContent = String(included);
        }

        if (fallbackCount) {
            fallbackCount.textContent = String(rows.length - included);
        }
    };

    rows.forEach((row) => {
        const checkbox = row.querySelector('[data-mks-include]');
        const toggleLabel = row.querySelector('.mks-toggle__label');
        const labelInput = row.querySelector('[data-mks-label]');
        const fallbackSelect = row.querySelector('[data-mks-fallback]');
        const resultType = row.querySelector('[data-mks-result-type]');
        const resultLabel = row.querySelector('[data-mks-result-label]');
        const resultSlug = row.querySelector('[data-mks-result-slug]');

        if (!checkbox || !labelInput || !fallbackSelect) {
            return;
        }

        const updateRow = () => {
            const included = checkbox.checked;
            const fallbackOption = fallbackSelect.options[fallbackSelect.selectedIndex];
            const label = labelInput.value.trim() || 'Custom status';

            row.classList.toggle('is-included', included);
            labelInput.setAttribute('aria-disabled', included ? 'false' : 'true');
            fallbackSelect.setAttribute('aria-disabled', included ? 'true' : 'false');

            if (toggleLabel) {
                toggleLabel.textContent = included ? 'Included' : 'Excluded';
            }

            if (resultType) {
                resultType.textContent = included ? 'Custom' : 'Fallback';
            }

            if (resultLabel) {
                resultLabel.textContent = included ? label : fallbackOption.textContent.trim();
            }

            if (resultSlug) {
                resultSlug.textContent = included
                    ? resultSlug.dataset.mksCustomSlug
                    : `wc-${fallbackSelect.value}`;
            }

            updateCounts();
        };

        checkbox.addEventListener('change', updateRow);
        labelInput.addEventListener('input', updateRow);
        fallbackSelect.addEventListener('change', updateRow);
        updateRow();
    });
})();
