class LoginExperience {
  constructor(documentRoot) {
    this.root = documentRoot;
    this.modal = documentRoot.querySelector("[data-login-modal]");
    this.oneTap = documentRoot.querySelector("[data-google-one-tap]");
    this.lastTrigger = null;
    this.oneTapTimer = null;
  }

  init() {
    this.registerParentThemeTriggers();
    this.root.addEventListener("click", (event) => this.handleClick(event));
    this.root.addEventListener("keydown", (event) => this.handleKeydown(event));
    this.root.querySelectorAll(".login-form-block").forEach((block) => {
      this.setAuthMode(block, block.dataset.authMode || "login", false);
    });
    this.root.querySelectorAll("[data-login-form]").forEach((form) => {
      form.addEventListener("submit", (event) => this.validateForm(event));
      form.addEventListener("input", (event) => this.clearFieldError(event.target));
    });

    if (this.modal && !this.modal.hidden) document.body.classList.add("is-locked");
    this.scheduleOneTap();
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
    const authModeButton = event.target.closest("[data-auth-mode]");
    const oneTapClose = event.target.closest("[data-one-tap-close]");

    if (openButton && !event.metaKey && !event.ctrlKey) {
      event.preventDefault();
      this.openModal(openButton);
    }

    if (closeButton) this.closeModal();
    if (passwordButton) this.togglePassword(passwordButton);
    if (oneTapClose) this.dismissOneTap();
    if (authModeButton) {
      const block = authModeButton.closest(".login-form-block");
      if (block) this.setAuthMode(block, authModeButton.dataset.authMode);
    }
  }

  setAuthMode(block, requestedMode, focus = true) {
    const mode = requestedMode === "register" ? "register" : "login";
    const isRegistration = mode === "register";
    const activeForm = block.querySelector(`[data-auth-form="${mode}"]`);

    block.dataset.authMode = mode;
    block.classList.toggle("is-registration", isRegistration);
    block.querySelectorAll("[data-auth-mode]").forEach((button) => {
      const isActive = button.dataset.authMode === mode;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", String(isActive));
    });
    block.querySelectorAll("[data-auth-form]").forEach((form) => {
      form.hidden = form.dataset.authForm !== mode;
    });
    block.querySelectorAll("[data-register-only]").forEach((element) => {
      element.hidden = !isRegistration;
    });
    block.querySelectorAll("[data-login-only]").forEach((element) => {
      element.hidden = isRegistration;
    });
    block.querySelectorAll("[data-auth-copy]").forEach((element) => {
      element.textContent = isRegistration
        ? element.dataset.registerCopy
        : element.dataset.loginCopy;
    });
    block.querySelectorAll("[data-social-provider]").forEach((link) => {
      link.href = isRegistration ? link.dataset.authRegisterUrl : link.dataset.authLoginUrl;
    });

    const dialogTitle = block
      .closest(".login-modal__dialog")
      ?.querySelector("[data-auth-dialog-title]");
    if (dialogTitle) {
      dialogTitle.textContent = isRegistration
        ? dialogTitle.dataset.registerCopy
        : dialogTitle.dataset.loginCopy;
    }

    if (focus) {
      block.querySelectorAll(".login-field").forEach((field) => field.classList.remove("has-error"));
      block.querySelectorAll("[data-login-error], [data-login-status]").forEach((message) => {
        message.textContent = "";
      });
      activeForm?.querySelector("input:not([type=hidden])")?.focus({ preventScroll: true });
    }
  }

  openModal(trigger) {
    if (!this.modal) return;

    this.lastTrigger = trigger;
    this.modal.hidden = false;
    this.modal.classList.add("is-open");
    document.body.classList.add("is-locked");
    this.hideOneTap();
    trigger.setAttribute("aria-expanded", "true");
    window.requestAnimationFrame(() => this.modal.querySelector("input")?.focus());
  }

  closeModal() {
    if (!this.modal || this.modal.hidden) return;

    this.modal.classList.remove("is-open");
    document.body.classList.remove("is-locked");
    this.lastTrigger?.setAttribute("aria-expanded", "false");
    window.setTimeout(() => {
      this.modal.hidden = true;
      this.lastTrigger?.focus();
    }, 220);
  }

  scheduleOneTap() {
    if (!this.oneTap || window.sessionStorage.getItem("maruderm-one-tap-dismissed")) return;

    this.oneTapTimer = window.setTimeout(() => {
      if (this.modal && !this.modal.hidden) return;
      this.oneTap.hidden = false;
      window.requestAnimationFrame(() => this.oneTap?.classList.add("is-visible"));
    }, 900);
  }

  hideOneTap() {
    window.clearTimeout(this.oneTapTimer);
    if (!this.oneTap) return;

    this.oneTap.classList.remove("is-visible");
    window.setTimeout(() => {
      this.oneTap.hidden = true;
    }, 180);
  }

  dismissOneTap() {
    window.sessionStorage.setItem("maruderm-one-tap-dismissed", "true");
    this.hideOneTap();
  }

  handleKeydown(event) {
    if (event.key === "Escape" && this.modal && !this.modal.hidden) this.closeModal();
    if (event.key !== "Tab" || !this.modal || this.modal.hidden) return;

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
    if (input.name === "first_name" && input.validity.tooShort) message = "Мінімум 2 символи";
    if (input.name === "password" && input.validity.tooShort) message = "Мінімум 6 символів";
    if (input.name === "password_confirmation" && input.validity.tooShort) {
      message = "Мінімум 6 символів";
    }
    if (
      input.name === "password_confirmation" &&
      input.value &&
      input.value !== input.form.elements.password.value
    ) {
      message = "Паролі не збігаються";
    }

    field?.classList.toggle("has-error", Boolean(message));
    if (error) error.textContent = message;

    return !message;
  }

  clearFieldError(input) {
    if (input instanceof HTMLInputElement && input.matches("input[required]")) {
      this.validateField(input);
      if (input.name === "password" && input.form.elements.password_confirmation?.value) {
        this.validateField(input.form.elements.password_confirmation);
      }
    }
  }

  validateForm(event) {
    const form = event.currentTarget;
    const inputs = [...form.querySelectorAll("input[required]")];
    const isValid = inputs.map((input) => this.validateField(input)).every(Boolean);

    if (!isValid) {
      event.preventDefault();
      form.querySelector(".login-field.has-error input")?.focus();
      return;
    }

    const button = form.querySelector(".login-submit");
    button?.classList.add("is-loading");
    button?.setAttribute("aria-disabled", "true");
    const label = button?.querySelector("[data-login-submit-label]");
    if (label) {
      label.textContent = form.dataset.authForm === "register"
        ? "Створюємо акаунт…"
        : "Перевіряємо дані…";
    }
  }
}

const loginExperience = new LoginExperience(document);
loginExperience.init();
