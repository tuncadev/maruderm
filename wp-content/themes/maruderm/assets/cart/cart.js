class CartController {
  constructor(root) {
    this.root = root;
    this.form = root.querySelector('[data-cart-form]');
    this.updateButton = root.querySelector('[data-cart-update]');
    this.submitTimer = null;
  }

  init() {
    if (!this.form || !this.updateButton) return;

    this.form.addEventListener('click', (event) => this.handleClick(event));
    this.form.addEventListener('change', (event) => this.handleChange(event));
  }

  handleClick(event) {
    const button = event.target.closest('[data-cart-minus], [data-cart-plus]');
    if (!button) return;

    const quantity = button.closest('.cart-quantity')?.querySelector('[data-cart-quantity]');
    if (!quantity) return;

    const minimum = Number(quantity.min || 1);
    const maximum = quantity.max ? Number(quantity.max) : Number.POSITIVE_INFINITY;
    const adjustment = button.hasAttribute('data-cart-plus') ? 1 : -1;
    quantity.value = String(Math.max(minimum, Math.min(maximum, Number(quantity.value || minimum) + adjustment)));
    this.scheduleSubmit();
  }

  handleChange(event) {
    if (!event.target.matches('[data-cart-quantity]')) return;

    const input = event.target;
    const minimum = Number(input.min || 1);
    const maximum = input.max ? Number(input.max) : Number.POSITIVE_INFINITY;
    input.value = String(Math.max(minimum, Math.min(maximum, Number(input.value || minimum))));
    this.scheduleSubmit();
  }

  scheduleSubmit() {
    window.clearTimeout(this.submitTimer);
    this.submitTimer = window.setTimeout(() => this.updateButton.click(), 250);
  }
}

const cartPage = document.querySelector('[data-cart-page]');
if (cartPage) new CartController(cartPage).init();
