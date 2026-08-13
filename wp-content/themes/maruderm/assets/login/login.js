class LoginExperience {
  constructor(documentRoot) {
    this.root = documentRoot;
    this.modal = documentRoot.querySelector("[data-login-modal]");
    this.lastTrigger = null;
  }

  init() {
    if (!this.modal) return;

    this.registerParentThemeTriggers();
    this.root.addEventListener("click", (event) => this.handleClick(event));
    this.root.addEventListener("keydown", (event) => this.handleKeydown(event));
    this.root.querySelectorAll("[data-login-form]").forEach((form) => {
      form.addEventListener("submit", (event) => this.validateForm(event));
      form.addEventListener("input", (event) => this.clearFieldError(event.target));
    });

    if (!this.modal.hidden) document.body.classList.add("is-locked");
  }

  registerParentThemeTriggers() {
    this.root
      .querySelectorAll("#menu-extra-login, .navigation-mobile_account")
      .forEach((trigger) => trigger.setAttribute("data-login-open", ""));
  }

  handleClick(event) {
    const openButton = event.target.closest("[data-login-open]");
    const closeButton = event.target.closest("[data-login-close]");
    const passwordButton = event.target.closest("[data-password-toggle]");

    if (openButton && !event.metaKey && !event.ctrlKey) {
      event.preventDefault();
      this.openModal(openButton);
    }

    if (closeButton) this.closeModal();
    if (passwordButton) this.togglePassword(passwordButton);
  }

  openModal(trigger) {
    this.lastTrigger = trigger;
    this.modal.hidden = false;
    this.modal.classList.add("is-open");
    document.body.classList.add("is-locked");
    trigger.setAttribute("aria-expanded", "true");
    window.requestAnimationFrame(() => this.modal.querySelector("input")?.focus());
  }

  closeModal() {
    if (this.modal.hidden) return;

    this.modal.classList.remove("is-open");
    document.body.classList.remove("is-locked");
    this.lastTrigger?.setAttribute("aria-expanded", "false");
    window.setTimeout(() => {
      this.modal.hidden = true;
      this.lastTrigger?.focus();
    }, 220);
  }

  handleKeydown(event) {
    if (event.key === "Escape" && !this.modal.hidden) this.closeModal();
    if (event.key !== "Tab" || this.modal.hidden) return;

    const focusable = [
      ...this.modal.querySelectorAll('button, a, input, [tabindex]:not([tabindex="-1"])'),
    ].filter((element) => !element.disabled && element.offsetParent !== null);
    const first = focusable[0];
    const last = focusable.at(-1);

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last?.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first?.focus();
    }
  }

  togglePassword(button) {
    const input = button.closest(".login-field__control")?.querySelector("input");
    if (!input) return;

    const visible = input.type === "text";
    input.type = visible ? "password" : "text";
    button.classList.toggle("is-visible", !visible);
    button.setAttribute("aria-label", visible ? "Показати пароль" : "Приховати пароль");
  }

  validateField(input) {
    const field = input.closest(".login-field");
    const error = field?.querySelector("[data-login-error]");
    let message = "";

    if (input.validity.valueMissing) message = "Заповни це поле";
    if (input.type === "email" && input.validity.typeMismatch) message = "Перевір формат email";
    if (input.name === "password" && input.validity.tooShort) message = "Мінімум 6 символів";

    field?.classList.toggle("has-error", Boolean(message));
    if (error) error.textContent = message;

    return !message;
  }

  clearFieldError(input) {
    if (input instanceof HTMLInputElement && input.matches("input[required]")) {
      this.validateField(input);
    }
  }

  validateForm(event) {
    const form = event.currentTarget;
    const inputs = [...form.querySelectorAll("input[required]")];
    const isValid = inputs.map((input) => this.validateField(input)).every(Boolean);

    if (!isValid) {
      event.preventDefault();
      inputs.find((input) => !input.validity.valid)?.focus();
      return;
    }

    const button = form.querySelector(".login-submit");
    button?.classList.add("is-loading");
    button?.setAttribute("aria-disabled", "true");
    const label = button?.querySelector("[data-login-submit-label]");
    if (label) label.textContent = "Перевіряємо дані…";
  }
}

const loginExperience = new LoginExperience(document);
loginExperience.init();
