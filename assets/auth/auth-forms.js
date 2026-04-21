(function () {
    function activateTab(root, buttons, panels, tabName) {
        buttons.forEach(function (button) {
            var active = button.getAttribute('data-tab') === tabName;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.setAttribute('tabindex', active ? '0' : '-1');
        });

        panels.forEach(function (panel) {
            var active = panel.getAttribute('data-tab-panel') === tabName;
            panel.classList.toggle('is-active', active);
        });
    }

    function openSocialPopup(url) {
        var width = 560;
        var height = 680;
        var left = window.screenX + Math.max(0, (window.outerWidth - width) / 2);
        var top = window.screenY + Math.max(0, (window.outerHeight - height) / 2);
        var features = 'width=' + width + ',height=' + height + ',left=' + Math.round(left) + ',top=' + Math.round(top) + ',resizable=yes,scrollbars=yes';
        var popup = window.open(url, 'social-auth-popup', features);

        if (popup) {
            popup.focus();
        } else {
            window.location.href = url;
        }
    }

    function bindAuthRoot(root, buttonSelector, panelSelector, socialSelector) {
        if (!root) {
            return;
        }

        var initialTab = root.getAttribute('data-initial-tab') || 'login';
        var buttons = Array.prototype.slice.call(root.querySelectorAll(buttonSelector));
        var panels = Array.prototype.slice.call(root.querySelectorAll(panelSelector));
        var socialButtons = Array.prototype.slice.call(root.querySelectorAll(socialSelector));

        if (buttons.length && panels.length) {
            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    activateTab(root, buttons, panels, button.getAttribute('data-tab') || 'login');
                });
            });
            activateTab(root, buttons, panels, initialTab);
        }

        socialButtons.forEach(function (anchor) {
            anchor.addEventListener('click', function (event) {
                event.preventDefault();
                openSocialPopup(anchor.href);
            });
        });
    }

    bindAuthRoot(document.querySelector('.md-auth-page'), '.md-tab-btn', '[data-tab-panel]', '.md-social-btn');
    bindAuthRoot(document.querySelector('.md-wc-auth'), '.md-wc-auth__tab-btn', '[data-tab-panel]', '.md-wc-auth__social');

    if (!window.__socialAuthMessageBound) {
        window.__socialAuthMessageBound = true;
        window.addEventListener('message', function (event) {
            if (!event || !event.data || event.data.source !== 'social-auth') {
                return;
            }

            if (typeof event.data.redirectTo === 'string' && event.data.redirectTo !== '') {
                window.location.href = event.data.redirectTo;
                return;
            }

            window.location.reload();
        });
    }
})();
