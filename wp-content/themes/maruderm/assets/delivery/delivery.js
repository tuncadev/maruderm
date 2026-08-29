class DeliveryController {
  constructor(root) {
    this.root = root;
    this.form = root.querySelector('[data-delivery-form]');
    this.submitButton = root.querySelector('[data-delivery-submit]');
    this.status = root.querySelector('[data-delivery-status]');
  }

  init() {
    if (!this.form || !this.submitButton) return;

    this.bindCheckoutLifecycle();
    this.form.addEventListener('change', (event) => this.handleChange(event));
    this.form.addEventListener('input', (event) => this.clearError(event.target));
    this.form.addEventListener('submit', (event) => event.preventDefault());
    this.submitButton.addEventListener('click', () => this.submit());
    this.syncShippingMethod();
  }

  bindCheckoutLifecycle() {
    const body = window.jQuery?.(document.body);
    if (!body) return;

    body.off('update_checkout.marudermDelivery').on('update_checkout.marudermDelivery', () => {
      window.setTimeout(() => body.trigger('updated_checkout'), 0);
    });
  }

  handleChange(event) {
    if (event.target.matches('input[name^="shipping_method"]')) {
      this.syncShippingMethod();
    }

    this.clearError(event.target);
  }

  syncShippingMethod() {
    const methods = [...this.root.querySelectorAll('[data-shipping-method]')];
    const selected = methods.find((method) => method.querySelector('input')?.checked);

    methods.forEach((method) => method.classList.toggle('is-selected', method === selected));

    const panel = this.root.querySelector('[data-delivery-panel]');
    const label = selected?.querySelector('input')?.dataset.methodLabel ?? 'Спосіб доставки';
    const isNovaPoshta = selected?.dataset.novaPoshta === 'true';

    panel?.classList.toggle('is-inactive', !isNovaPoshta);

    const summaryLabel = this.root.querySelector('[data-delivery-method-label]');
    if (summaryLabel) summaryLabel.textContent = label;

    const hint = this.root.querySelector('[data-delivery-hint]');
    if (hint) {
      hint.textContent = isNovaPoshta
        ? 'Обери населений пункт і відділення або поштомат Нової пошти.'
        : 'Для цього способу додаткові дані доставки не потрібні.';
    }
  }

  clearError(field) {
    if (!(field instanceof HTMLElement) || !field.matches('input, select, textarea')) return;

    const wrapper = field.closest('.delivery-field');
    wrapper?.classList.remove('has-error');
    const error = wrapper?.querySelector('[data-field-error]');
    if (error) error.textContent = '';

    if (field.name.startsWith('wcus_')) {
      this.root.querySelector('[data-delivery-panel]')?.classList.remove('has-error');
    }
  }

  validateContact() {
    const required = [...this.form.querySelectorAll('.delivery-fields--contact [required]')];
    let firstInvalid = null;

    required.forEach((field) => {
      const valid = field.checkValidity() && field.value.trim() !== '';
      const wrapper = field.closest('.delivery-field');
      wrapper?.classList.toggle('has-error', !valid);
      const error = wrapper?.querySelector('[data-field-error]');
      if (error) error.textContent = valid ? '' : 'Заповни це поле';
      if (!valid && !firstInvalid) firstInvalid = field;
    });

    firstInvalid?.focus();
    return !firstInvalid;
  }

  showServerErrors(fields = {}) {
    Object.entries(fields).forEach(([name, message]) => {
      if (name === 'nova_poshta' || name === 'shipping_method') {
        this.root.querySelector('[data-delivery-panel]')?.classList.add('has-error');
        return;
      }

      const field = this.form.elements.namedItem(name);
      if (!(field instanceof HTMLElement)) return;
      const wrapper = field.closest('.delivery-field');
      wrapper?.classList.add('has-error');
      const error = wrapper?.querySelector('[data-field-error]');
      if (error) error.textContent = String(message);
    });
  }

  setStatus(message, type = '') {
    this.status.className = `delivery-form__status${type ? ` is-${type}` : ''}`;
    this.status.textContent = message;
  }

  async submit() {
    if (!this.validateContact()) {
      this.setStatus('Перевір обов’язкові поля перед переходом до оплати.', 'error');
      return;
    }

    this.submitButton.disabled = true;
    this.form.classList.add('is-loading');
    this.setStatus('Зберігаємо дані доставки…');

    try {
      const response = await fetch(this.form.dataset.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new FormData(this.form),
      });
      const payload = await response.json();

      if (!response.ok || !payload.success) {
        this.showServerErrors(payload.data?.fields);
        throw new Error(payload.data?.message || 'Не вдалося зберегти дані доставки.');
      }

      this.setStatus(payload.data.message, 'success');
      window.location.assign(payload.data.redirect);
    } catch (error) {
      this.setStatus(error.message || 'Сталася помилка. Спробуй ще раз.', 'error');
      this.submitButton.disabled = false;
      this.form.classList.remove('is-loading');
    }
  }
}

const deliveryPage = document.querySelector('[data-delivery-page]');
if (deliveryPage) new DeliveryController(deliveryPage).init();
