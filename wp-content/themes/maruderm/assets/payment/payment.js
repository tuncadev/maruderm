class PaymentController {
  constructor(root) {
    this.root = root;
    this.form = root.querySelector('[data-payment-form]');
    this.button = root.querySelector('[data-payment-submit]');
    this.status = root.querySelector('[data-payment-status]');
  }

  init() {
    if (!this.form || !this.button) return;

    this.form.addEventListener('change', (event) => this.handleChange(event));
    this.form.addEventListener('submit', (event) => this.validateSubmit(event), true);
    window.jQuery?.(document.body).on('payment_method_selected', () => this.syncMethod());
    window.jQuery?.(document.body).on('checkout_error', () => this.handleCheckoutError());
    this.syncMethod();
  }

  handleChange(event) {
    if (event.target.name === 'payment_method') this.syncMethod();

    if (event.target.name === 'terms') {
      this.root.querySelector('.payment-consent')?.classList.remove('has-error');
      this.setStatus('');
    }
  }

  syncMethod() {
    const methods = [...this.root.querySelectorAll('[data-payment-method]')];
    const selected = methods.find((method) => method.querySelector('input')?.checked);

    methods.forEach((method) => method.classList.toggle('is-selected', method === selected));

    const input = selected?.querySelector('input');
    if (!input) return;

    const label = input.dataset.orderButtonText || 'Підтвердити замовлення';
    this.button.dataset.value = label;
    this.button.value = label;
    this.button.innerHTML = `<span data-payment-submit-label>${label}</span>${this.actionIcon(input.value)}`;
  }

  actionIcon(method) {
    const path = method === 'cod'
      ? '<path d="M5 12h14M13 6l6 6-6 6"></path>'
      : '<rect x="5" y="10" width="14" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path>';

    return `<svg viewBox="0 0 24 24" aria-hidden="true">${path}</svg>`;
  }

  validateSubmit(event) {
    const terms = this.form.elements.namedItem('terms');

    if (!(terms instanceof HTMLInputElement) || !terms.checked) {
      event.preventDefault();
      event.stopImmediatePropagation();
      this.root.querySelector('.payment-consent')?.classList.add('has-error');
      this.setStatus('Підтвердь згоду з умовами оформлення замовлення.', 'error');
      terms?.focus();
      return;
    }

    this.button.disabled = true;
    this.form.classList.add('is-loading');
    this.setStatus('Створюємо замовлення…');
  }

  handleCheckoutError() {
    this.button.disabled = false;
    this.form.classList.remove('is-loading');
    this.setStatus('Перевір дані замовлення та спробуй ще раз.', 'error');
    this.syncMethod();
  }

  setStatus(message, type = '') {
    this.status.className = `payment-form__status${type ? ` is-${type}` : ''}`;
    this.status.textContent = message;
  }
}

const paymentPage = document.querySelector('[data-payment-page]');
if (paymentPage) new PaymentController(paymentPage).init();
