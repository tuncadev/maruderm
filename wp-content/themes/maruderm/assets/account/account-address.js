export class AccountAddressController {
  constructor(root) {
    this.root = root;
    this.form = root.querySelector("[data-profile-form]");
    this.addresses = root.querySelector(".account-addresses");
    this.editor = root.querySelector("[data-address-editor]");
    this.addButton = root.querySelector("[data-address-add]");
    this.saveButton = root.querySelector("[data-address-save]");
    this.fields = [...root.querySelectorAll("[data-address-field]")];
    this.defaults = this.fields.map((field) => field.value);
    this.status = root.querySelector("[data-address-status]");
  }

  init() {
    if (!this.form || !this.editor || !this.addButton) return;
    this.root.addEventListener("click", (event) => this.handleClick(event));
  }

  handleClick(event) {
    if (event.target.closest("[data-address-add]")) this.open();
    if (event.target.closest("[data-address-cancel]")) this.close(true);
    if (event.target.closest("[data-address-save]")) this.save();
  }

  open() {
    if (this.addButton.disabled) return;
    this.editor.hidden = false;
    this.fields.forEach((field) => {
      field.disabled = false;
      field.required = true;
    });
    this.status.textContent = "";
    this.fields[1]?.focus({ preventScroll: true });
    this.editor.scrollIntoView({ behavior: this.motionBehavior(), block: "nearest" });
  }

  close(reset = false) {
    if (!this.editor) return;
    this.editor.hidden = true;
    this.fields.forEach((field, index) => {
      field.required = false;
      field.disabled = true;
      field.removeAttribute("aria-invalid");
      if (reset) field.value = this.defaults[index];
    });
    this.status.textContent = "";
  }

  async save() {
    const invalidField = this.fields.find((field) => !field.reportValidity());

    if (invalidField) {
      invalidField.setAttribute("aria-invalid", "true");
      invalidField.focus();
      this.status.textContent = "Заповни всі поля нової адреси.";
      return;
    }

    const [type, city, location] = this.fields.map((field) => field.value.trim());
    const body = new URLSearchParams({
      action: "maruderm_save_account_address",
      nonce: this.form.dataset.addressNonce || "",
      type,
      city,
      location,
    });

    this.saveButton.disabled = true;
    this.status.textContent = "Зберігаємо адресу…";

    try {
      const response = await fetch(this.form.dataset.addressAjaxUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body,
      });
      const payload = await response.json();

      if (!response.ok || !payload.success || !payload.data?.address) {
        throw new Error(payload.data?.message || "Не вдалося зберегти адресу.");
      }

      this.prependAddress(payload.data.address);
      this.close(true);
      const profileStatus = this.root.querySelector("[data-profile-status]");
      if (profileStatus) profileStatus.textContent = payload.data.message || "Нову адресу додано.";
    } catch (error) {
      this.status.textContent = error instanceof Error ? error.message : "Не вдалося зберегти адресу.";
    } finally {
      this.saveButton.disabled = false;
    }
  }

  prependAddress(address) {
    this.addresses.querySelectorAll(".account-address").forEach((node) => {
      node.classList.remove("is-selected");
      node.querySelector("input").checked = false;
      node.querySelector("small").textContent = "Збережена адреса";
    });
    this.addresses.insertAdjacentHTML(
      "afterbegin",
      `<label class="account-address is-selected"><input type="radio" name="address" value="${this.escape(address.id)}" checked><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10v10h16V10M3 4h18l-1 6H4L3 4Z"></path><path d="M8 20v-6h8v6"></path></svg></span><div><small>Основна адреса</small><strong>${this.escape(address.type)} · ${this.escape(address.location)}</strong><p>${this.escape(address.city)}</p></div><i>✓</i></label>`,
    );
  }

  escape(value) {
    const holder = document.createElement("span");
    holder.textContent = String(value);
    return holder.innerHTML;
  }

  motionBehavior() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth";
  }
}
