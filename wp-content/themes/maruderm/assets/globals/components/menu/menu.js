document.querySelectorAll('[data-maruderm-menu]').forEach((menu) => {
  const toggle = menu.querySelector('[data-maruderm-menu-toggle]');
  const nav = menu.querySelector('[data-maruderm-menu-nav]');
  const close = menu.querySelector('[data-maruderm-menu-close]');
  const overlay = menu.querySelector('[data-maruderm-menu-overlay]');
  const items = [...menu.querySelectorAll('[data-maruderm-menu-item]')];

  const closeDropdowns = (except = null) => {
    items.forEach((item) => {
      if (item === except) return;
      item.classList.remove('is-open');
      item.querySelector('[data-maruderm-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
    });
  };

  const setMenuState = (isOpen) => {
    nav?.classList.toggle('is-open', isOpen);
    overlay?.classList.toggle('is-open', isOpen);
    toggle?.setAttribute('aria-expanded', String(isOpen));
    document.body.classList.toggle('is-maruderm-menu-locked', isOpen);
    if (!isOpen) closeDropdowns();
  };

  toggle?.addEventListener('click', () => setMenuState(!nav?.classList.contains('is-open')));
  close?.addEventListener('click', () => setMenuState(false));
  overlay?.addEventListener('click', () => setMenuState(false));

  items.forEach((item) => {
    const dropdownToggle = item.querySelector('[data-maruderm-dropdown-toggle]');
    dropdownToggle?.addEventListener('click', () => {
      const isOpening = !item.classList.contains('is-open');
      closeDropdowns(item);
      item.classList.toggle('is-open', isOpening);
      dropdownToggle.setAttribute('aria-expanded', String(isOpening));
    });
  });

  document.addEventListener('click', (event) => {
    if (!menu.contains(event.target)) closeDropdowns();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    closeDropdowns();
    setMenuState(false);
    toggle?.focus();
  });
});
