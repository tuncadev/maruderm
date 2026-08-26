const root = document.querySelector('[data-catalog-root]');

if (root) {
  const grid = root.querySelector('[data-product-grid]');
  const cards = [...grid.querySelectorAll('.product-card')];
  const emptyState = root.querySelector('[data-catalog-empty]');
  const panel = root.querySelector('[data-filter-panel]');
  const overlay = root.querySelector('[data-filter-overlay]');
  const activeFilters = root.querySelector('[data-active-filters]');
  const resultCount = root.querySelector('[data-result-count]');
  const mobileCount = root.querySelector('[data-mobile-count]');
  const activeFilterCount = root.querySelector('[data-active-filter-count]');
  const sort = root.querySelector('[data-sort]');
  const catalogUrl = root.dataset.catalogUrl || '/catalog/';
  const catalogTitle = root.dataset.catalogTitle || 'Каталог догляду';
  const catalogDescription = root.dataset.catalogDescription || '';
  const siteName = root.dataset.siteName || '';
  const heading = root.querySelector('[data-catalog-heading]');
  const description = root.querySelector('[data-catalog-description]');
  const breadcrumbLink = root.querySelector('[data-catalog-breadcrumb-link]');
  const breadcrumbSeparator = root.querySelector('[data-catalog-breadcrumb-separator]');
  const breadcrumbCurrent = root.querySelector('[data-catalog-breadcrumb-current]');
  const url = new URL(window.location.href);
  const parameterNames = {
    category: 'category',
    skinTypes: 'skin-type',
    concerns: 'concern',
    hairNeeds: 'hair-need',
    price: 'price',
  };
  const state = Object.fromEntries(
    Object.entries(parameterNames).map(([group, parameter]) => [
      group,
      new Set((url.searchParams.get(parameter) || '').split(',').filter(Boolean)),
    ]),
  );
  const initialCategory = root.dataset.initialCategory || '';
  const initialCategoryLabel = root.dataset.initialCategoryLabel || '';
  const initialCategoryDescription = root.dataset.initialCategoryDescription || '';
  const initialCategoryUrl = root.dataset.initialCategoryUrl || '';
  const search = (url.searchParams.get('search') || url.searchParams.get('s') || '').toLocaleLowerCase('uk');
  const labels = new Map();
  const categoryInputs = new Map();
  const categoryPaths = new Map();

  if (initialCategory) {
    labels.set(`category:${initialCategory}`, initialCategoryLabel || initialCategory);
    if (initialCategoryUrl) {
      categoryPaths.set(new URL(initialCategoryUrl, window.location.origin).pathname, initialCategory);
    }
  }

  if (state.category.size === 0 && initialCategory) {
    state.category.add(initialCategory);
  }

  panel.querySelectorAll('input[type="checkbox"]').forEach((input) => {
    labels.set(`${input.name}:${input.value}`, input.closest('.filter-check')?.querySelector('span:nth-of-type(2)')?.textContent?.trim() || input.value);
    input.checked = state[input.name]?.has(input.value) || false;

    if (input.name === 'category') {
      categoryInputs.set(input.value, input);
      categoryPaths.set(new URL(input.dataset.categoryUrl, window.location.origin).pathname, input.value);
    }
  });

  if ([...sort.options].some((option) => option.value === url.searchParams.get('sort'))) {
    sort.value = url.searchParams.get('sort');
  }

  const values = (card, key) => new Set((card.dataset[key] || '').split(' ').filter(Boolean));
  const matchesAny = (available, selected) =>
    selected.size === 0 || [...selected].some((value) => available.has(value));
  const matchesPrice = (card, selected) =>
    selected.size === 0 ||
    (card.dataset.inStock === 'yes' && card.dataset.price !== '' &&
      [...selected].some((range) => {
        const [minimum, maximum] = range.split('-').map(Number);
        const price = Number(card.dataset.price);
        return price >= minimum && price <= maximum;
      }));
  const matchesStockContext = (card, selection) =>
    card.dataset.inStock === 'yes' || selection.category.size > 0;

  const matches = (card, selection = state) =>
    matchesStockContext(card, selection) &&
    matchesAny(values(card, 'category'), selection.category) &&
    matchesAny(values(card, 'skinTypes'), selection.skinTypes) &&
    matchesAny(values(card, 'concerns'), selection.concerns) &&
    matchesAny(values(card, 'hairNeeds'), selection.hairNeeds) &&
    matchesPrice(card, selection.price) &&
    (!search || (card.dataset.productName || '').toLocaleLowerCase('uk').includes(search));

  const cloneState = () => Object.fromEntries(
    Object.entries(state).map(([group, selected]) => [group, new Set(selected)]),
  );

  const hasResults = (selection = state) => cards.some((card) => matches(card, selection));
  const hasSelections = (selection = state) =>
    Object.values(selection).some((selected) => selected.size > 0);

  const comparePrice = (left, right, direction) => {
    const leftHasPrice = left.dataset.price !== '';
    const rightHasPrice = right.dataset.price !== '';

    if (leftHasPrice !== rightHasPrice) {
      return leftHasPrice ? -1 : 1;
    }

    return leftHasPrice
      ? direction * (Number(left.dataset.price) - Number(right.dataset.price))
      : 0;
  };

  const sorters = {
    popular: (left, right) =>
      Number(right.dataset.popularity) - Number(left.dataset.popularity) ||
      Number(right.dataset.created) - Number(left.dataset.created),
    newest: (left, right) => Number(right.dataset.created) - Number(left.dataset.created),
    'price-asc': (left, right) => comparePrice(left, right, 1),
    'price-desc': (left, right) => comparePrice(left, right, -1),
    name: (left, right) =>
      (left.dataset.productName || '').localeCompare(right.dataset.productName || '', 'uk'),
  };

  const buildUrl = () => {
    const selectedCategories = [...state.category];
    const base = selectedCategories.length === 1
      ? categoryUrl(selectedCategories[0])
      : catalogUrl;
    const next = new URL(base, window.location.origin);
    const current = new URL(window.location.href);

    current.searchParams.forEach((value, parameter) => {
      if (!Object.values(parameterNames).includes(parameter) && parameter !== 'sort') {
        next.searchParams.append(parameter, value);
      }
    });

    Object.entries(parameterNames).forEach(([group, parameter]) => {
      next.searchParams.delete(parameter);
      const selected = [...state[group]];

      if (selected.length && !(group === 'category' && selected.length === 1)) {
        next.searchParams.set(parameter, selected.join(','));
      }
    });

    next.searchParams.delete('sort');
    if (sort.value !== 'popular') {
      next.searchParams.set('sort', sort.value);
    }

    return next;
  };

  const syncUrl = (mode = 'replace') => {
    const next = buildUrl();
    const historyState = {
      catalogFilters: Object.fromEntries(
        Object.entries(state).map(([group, selected]) => [group, [...selected]]),
      ),
    };

    window.history[mode === 'push' ? 'pushState' : 'replaceState'](historyState, '', next);
  };

  const categoryUrl = (value) =>
    categoryInputs.get(value)?.dataset.categoryUrl
    || (value === initialCategory ? initialCategoryUrl : '')
    || catalogUrl;

  const updateCategoryContext = () => {
    const selectedCategories = [...state.category];
    const value = selectedCategories.length === 1 ? selectedCategories[0] : '';
    const input = categoryInputs.get(value);
    const label = value ? labels.get(`category:${value}`) || value : catalogTitle;
    const categoryDescription = input?.dataset.categoryDescription
      || (value === initialCategory ? initialCategoryDescription : '')
      || catalogDescription;

    heading.textContent = label;
    breadcrumbCurrent.textContent = label;
    description.textContent = categoryDescription;
    breadcrumbLink.hidden = !value;
    breadcrumbSeparator.hidden = !value;
    document.title = `${value ? label : 'Каталог'}${siteName ? ` – ${siteName}` : ''}`;
  };

  const syncCategoryControls = () => {
    categoryInputs.forEach((input, slug) => {
      input.checked = state.category.has(slug);
    });
  };

  const setSingleCategory = (value, historyMode = 'push') => {
    state.category.clear();
    if (value) state.category.add(value);

    if (value && !hasResults()) {
      Object.entries(state).forEach(([group, selected]) => {
        if (group !== 'category') selected.clear();
      });
      panel.querySelectorAll('input[type="checkbox"]:not([name="category"])').forEach((input) => {
        input.checked = false;
      });
    }

    syncCategoryControls();
    updateCategoryContext();
    render(historyMode);
  };

  const renderActiveFilters = () => {
    const items = Object.entries(state).flatMap(([group, selected]) =>
      [...selected].map((value) => ({
          group,
          value,
          label: labels.get(`${group}:${value}`) || value,
        })),
    );

    activeFilters.hidden = items.length === 0;
    activeFilters.innerHTML = items
      .map(
        ({ group, value, label }) =>
          `<button class="active-filter" type="button" data-remove-filter="${group}:${value}">${label}<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 5 14 14M19 5 5 19"></path></svg></button>`,
      )
      .join('');
    activeFilterCount.textContent = items.length ? String(items.length) : '';
  };

  const updateAvailability = () => {
    panel.querySelectorAll('input[type="checkbox"]').forEach((input) => {
      const label = input.closest('.filter-check');

      if (input.checked) {
        input.disabled = false;
        label?.classList.remove('is-disabled');
        return;
      }

      const candidate = cloneState();
      candidate[input.name] = new Set([input.value]);
      const disabled = !hasResults(candidate);
      input.disabled = disabled;
      label?.classList.toggle('is-disabled', disabled);
    });
  };

  const render = (historyMode = 'replace') => {
    const sorter = sorters[sort.value] || sorters.popular;
    const sortedCards = [...cards].sort(sorter);
    let visibleCount = 0;

    sortedCards.forEach((card) => {
      card.hidden = !matches(card);
      if (!card.hidden) visibleCount += 1;
      grid.insertBefore(card, emptyState);
    });

    emptyState.hidden = visibleCount !== 0;
    resultCount.textContent = String(visibleCount);
    mobileCount.textContent = `(${visibleCount})`;
    renderActiveFilters();
    updateAvailability();
    if (historyMode !== 'pop') syncUrl(historyMode);
  };

  panel.querySelectorAll('input[type="checkbox"]').forEach((input) => {
    input.addEventListener('change', () => {
      input.checked ? state[input.name].add(input.value) : state[input.name].delete(input.value);

      if (hasSelections() && !hasResults()) {
        input.checked = !input.checked;
        input.checked ? state[input.name].add(input.value) : state[input.name].delete(input.value);
        return;
      }

      if (input.name === 'category') updateCategoryContext();
      render('push');
    });
  });

  panel.querySelectorAll('.filter-group__toggle').forEach((button) => {
    button.addEventListener('click', () => {
      const group = button.closest('.filter-group');
      const open = group.classList.toggle('is-open');
      button.setAttribute('aria-expanded', String(open));
    });
  });

  sort.addEventListener('change', () => render('push'));
  root.querySelector('[data-clear-all]').addEventListener('click', () => {
    Object.values(state).forEach((selected) => selected.clear());
    panel.querySelectorAll('input[type="checkbox"]').forEach((input) => {
      input.checked = false;
    });
    updateCategoryContext();
    render('push');
  });

  activeFilters.addEventListener('click', (event) => {
    const button = event.target.closest('[data-remove-filter]');
    if (!button) return;
    const [group, value] = button.dataset.removeFilter.split(':');

    state[group].delete(value);
    const input = panel.querySelector(`input[name="${group}"][value="${value}"]`);
    if (input) input.checked = false;
    if (group === 'category') updateCategoryContext();
    render('push');
  });

  const togglePanel = (open) => {
    panel.classList.toggle('is-open', open);
    overlay.classList.toggle('is-open', open);
    document.body.classList.toggle('maruderm-catalog-filters-open', open);
  };

  root.querySelector('[data-filter-open]').addEventListener('click', () => togglePanel(true));
  root
    .querySelectorAll('[data-filter-close]')
    .forEach((button) => button.addEventListener('click', () => togglePanel(false)));
  overlay.addEventListener('click', () => togglePanel(false));

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const target = new URL(link.href, window.location.origin);
    const catalogPath = new URL(catalogUrl, window.location.origin).pathname;

    if (target.origin !== window.location.origin || (target.pathname !== catalogPath && !categoryPaths.has(target.pathname))) return;

    event.preventDefault();
    setSingleCategory(categoryPaths.get(target.pathname) || '');
  });

  window.addEventListener('popstate', () => {
    const current = new URL(window.location.href);
    const currentPath = new URL(window.location.href).pathname;
    Object.entries(parameterNames).forEach(([group, parameter]) => {
      const valuesFromUrl = (current.searchParams.get(parameter) || '').split(',').filter(Boolean);
      state[group] = new Set(valuesFromUrl);
    });

    if (state.category.size === 0 && categoryPaths.has(currentPath)) {
      state.category.add(categoryPaths.get(currentPath));
    }

    panel.querySelectorAll('input[type="checkbox"]').forEach((input) => {
      input.checked = state[input.name]?.has(input.value) || false;
    });
    const requestedSort = current.searchParams.get('sort');
    sort.value = [...sort.options].some((option) => option.value === requestedSort)
      ? requestedSort
      : 'popular';
    updateCategoryContext();
    render('pop');
  });

  const wishlistKey = 'maruderm-catalog-wishlist';
  let wishlist = [];

  try {
    wishlist = JSON.parse(window.localStorage.getItem(wishlistKey) || '[]');
  } catch {
    wishlist = [];
  }

  root.querySelectorAll('[data-wishlist-toggle]').forEach((button) => {
    const productId = button.closest('.product-card')?.dataset.productId;
    if (!productId) return;
    button.classList.toggle('is-active', wishlist.includes(productId));
    button.addEventListener('click', () => {
      wishlist = wishlist.includes(productId)
        ? wishlist.filter((id) => id !== productId)
        : [...wishlist, productId];
      button.classList.toggle('is-active', wishlist.includes(productId));
      window.localStorage.setItem(wishlistKey, JSON.stringify(wishlist));
    });
  });

  updateCategoryContext();
  render();
}
