const config = window.marudermCampaignPopup || {};
const CAMPAIGN_PREVIEW_PARAM = "campaign";
const CAMPAIGN_PREVIEW_VALUE = "preview";
const CAMPAIGN_PREVIEW_HASH = "#campaign-preview";
const CAMPAIGN_FREQUENCY_MS = Number(config.frequencyMs || 24 * 60 * 60 * 1000);
const CAMPAIGN_LAST_SHOWN_KEY = "maruderm-campaign:last-shown-at";

class CampaignPopup {
  constructor(element) {
    this.element = element;
    this.campaignId = element.dataset.campaignId;
    this.delay = Number(element.dataset.campaignDelay || 4300);
    this.dialog = element.querySelector(".campaign-popup__dialog");
    this.closeButtons = element.querySelectorAll("[data-campaign-close]");
    this.form = element.querySelector("[data-campaign-form]");
    this.email = element.querySelector("[data-campaign-email]");
    this.submitButton = element.querySelector("[data-campaign-submit]");
    this.status = element.querySelector("[data-campaign-status]");
    this.video = element.querySelector("[data-campaign-video]");
    this.openTimer = null;
    this.closeTimer = null;
    this.lastFocusedElement = null;
    this.previewMode =
      new URLSearchParams(window.location.search).get(CAMPAIGN_PREVIEW_PARAM) ===
        CAMPAIGN_PREVIEW_VALUE || window.location.hash === CAMPAIGN_PREVIEW_HASH;
    this.reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  }

  init() {
    this.closeButtons.forEach((button) => {
      button.addEventListener("click", () => this.close());
    });
    this.form.addEventListener("submit", (event) => this.handleSubmit(event));
    this.email.addEventListener("input", () => this.clearError());
    document.addEventListener("keydown", (event) => this.handleKeydown(event));
    window.addEventListener("storage", (event) => this.handleFrequencyChange(event));

    const frequencyDelay = this.previewMode ? 0 : this.getFrequencyRemaining();
    this.scheduleOpen(this.previewMode ? 150 : frequencyDelay + this.delay);
  }

  get submittedKey() {
    return `maruderm-campaign:${this.campaignId}:submitted`;
  }

  getFrequencyRemaining() {
    if (this.previewMode) return 0;

    const localTimestamp = Number(window.localStorage.getItem(CAMPAIGN_LAST_SHOWN_KEY));
    const cookieTimestamp = Number(this.readCookie(config.cookieName)) * 1000;
    const serverTimestamp = Number(config.lastShownAt || 0);
    const lastShownAt = Math.max(
      Number.isFinite(localTimestamp) ? localTimestamp : 0,
      Number.isFinite(cookieTimestamp) ? cookieTimestamp : 0,
      Number.isFinite(serverTimestamp) ? serverTimestamp : 0,
    );

    return Math.max(0, lastShownAt + CAMPAIGN_FREQUENCY_MS - Date.now());
  }

  recordImpression() {
    if (this.previewMode) return;

    const timestamp = Date.now();
    window.localStorage.setItem(CAMPAIGN_LAST_SHOWN_KEY, String(timestamp));
    this.writeCookie(config.cookieName, String(Math.floor(timestamp / 1000)));
    this.request(config.impressionAction, { campaign_id: this.campaignId }).catch(() => {});
  }

  scheduleOpen(delay) {
    window.clearTimeout(this.openTimer);
    this.openTimer = window.setTimeout(() => this.tryOpen(), Math.max(0, delay));
  }

  tryOpen() {
    const frequencyRemaining = this.getFrequencyRemaining();

    if (frequencyRemaining > 0) {
      this.scheduleOpen(frequencyRemaining + this.delay);
      return;
    }

    const loginModal = document.querySelector("[data-login-modal]:not([hidden])");
    const googlePrompt = document.querySelector("[data-google-one-tap]:not([hidden])");

    if (loginModal || googlePrompt) {
      this.scheduleOpen(1000);
      return;
    }

    this.open();
  }

  open() {
    this.lastFocusedElement = document.activeElement;
    window.clearTimeout(this.closeTimer);
    this.element.hidden = false;
    this.recordImpression();
    document.body.classList.add("is-locked");
    document.dispatchEvent(
      new CustomEvent("campaign-popup:opened", { detail: { campaignId: this.campaignId } }),
    );

    window.requestAnimationFrame(() => {
      this.element.classList.add("is-open");
      this.closeButtons[1]?.focus({ preventScroll: true });
    });
    this.playVideo();
  }

  close() {
    if (this.element.hidden) return;

    window.clearTimeout(this.openTimer);
    this.element.classList.remove("is-open");
    this.pauseVideo();

    const loginModal = document.querySelector("[data-login-modal]:not([hidden])");
    if (!loginModal) document.body.classList.remove("is-locked");

    this.closeTimer = window.setTimeout(() => {
      this.element.hidden = true;
      if (
        this.lastFocusedElement instanceof HTMLElement &&
        this.lastFocusedElement !== document.body
      ) {
        this.lastFocusedElement.focus({ preventScroll: true });
      }
      document.dispatchEvent(
        new CustomEvent("campaign-popup:closed", { detail: { campaignId: this.campaignId } }),
      );
    }, 300);
  }

  playVideo() {
    if (!this.video || this.reducedMotion.matches) return;
    this.video.play().catch(() => {});
  }

  pauseVideo() {
    this.video?.pause();
  }

  handleFrequencyChange(event) {
    if (this.previewMode || event.key !== CAMPAIGN_LAST_SHOWN_KEY) return;
    window.clearTimeout(this.openTimer);
    if (!this.element.hidden) this.close();
    this.scheduleOpen(this.getFrequencyRemaining() + this.delay);
  }

  handleKeydown(event) {
    if (this.element.hidden) return;

    if (event.key === "Escape") {
      event.preventDefault();
      this.close();
      return;
    }

    if (event.key !== "Tab") return;

    const focusable = [
      ...this.dialog.querySelectorAll('button, input, a, [tabindex]:not([tabindex="-1"])'),
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

  clearError() {
    this.form.classList.remove("has-error");
    if (!this.element.classList.contains("is-submitted")) this.status.textContent = "";
  }

  async handleSubmit(event) {
    event.preventDefault();
    this.clearError();

    if (!this.email.validity.valid) {
      this.form.classList.add("has-error");
      this.status.textContent = this.email.validity.valueMissing
        ? "Введи email, щоб приєднатися."
        : "Перевір формат email.";
      this.email.focus();
      return;
    }

    this.form.classList.add("is-submitting");
    this.submitButton.disabled = true;
    this.status.textContent = "Додаємо тебе до Maruderm Ukraine…";

    try {
      const response = await this.request(config.subscribeAction, {
        campaign_id: this.campaignId,
        email: this.email.value.trim(),
      });

      if (!response.success) {
        throw new Error(response.data?.message || "Не вдалося активувати підписку.");
      }

      this.form.classList.remove("is-submitting");
      this.element.classList.add("is-submitted");
      this.status.textContent = response.data?.message || this.status.dataset.successMessage;
      window.sessionStorage.setItem(this.submittedKey, "true");
      window.setTimeout(() => this.close(), 1900);
    } catch (error) {
      this.form.classList.remove("is-submitting");
      this.form.classList.add("has-error");
      this.submitButton.disabled = false;
      this.status.textContent = error instanceof Error ? error.message : "Спробуй ще раз.";
    }
  }

  async request(action, fields) {
    if (!config.ajaxUrl || !config.nonce || !action) {
      throw new Error("Підписка тимчасово недоступна.");
    }

    const body = new URLSearchParams({ action, nonce: config.nonce, ...fields });
    const response = await window.fetch(config.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body,
    });
    const payload = await response.json().catch(() => null);

    if (!response.ok && !payload) {
      throw new Error("Сервіс підписки не відповідає. Спробуй пізніше.");
    }

    return payload || { success: false };
  }

  readCookie(name) {
    if (!name) return "";
    const prefix = `${encodeURIComponent(name)}=`;
    const cookie = document.cookie.split("; ").find((part) => part.startsWith(prefix));
    return cookie ? decodeURIComponent(cookie.slice(prefix.length)) : "";
  }

  writeCookie(name, value) {
    if (!name) return;
    const secure = window.location.protocol === "https:" ? "; Secure" : "";
    document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; Max-Age=${Math.floor(
      CAMPAIGN_FREQUENCY_MS / 1000,
    )}; Path=/; SameSite=Lax${secure}`;
  }
}

document.querySelectorAll("[data-campaign-popup]").forEach((element) => {
  new CampaignPopup(element).init();
});
