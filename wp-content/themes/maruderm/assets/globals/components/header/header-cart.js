class HeaderMiniCartController {
  constructor() {
    this.isOpen = false;
    this.hoverCloseTimer = null;
    this.canHover = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  }

  init() {
    document.addEventListener("click", (event) => this.handleClick(event));
    document.addEventListener("keydown", (event) => this.handleKeydown(event));

    if (this.canHover) {
      document.addEventListener("mouseover", (event) => this.handleMouseOver(event));
      document.addEventListener("mouseout", (event) => this.handleMouseOut(event));
    }

    if (window.jQuery) {
      window.jQuery(document.body).on(
        "added_to_cart removed_from_cart wc_fragments_loaded wc_fragments_refreshed",
        (eventName) => this.handleCartRefresh(eventName.type),
      );
    }
  }

  root() {
    return document.querySelector("[data-header-cart]");
  }

  setOpen(isOpen, focusTrigger = false) {
    const root = this.root();
    const trigger = root?.querySelector("[data-header-cart-toggle]");
    const dropdown = root?.querySelector("[data-header-cart-dropdown]");

    this.isOpen = Boolean(isOpen && trigger && dropdown);

    if (!trigger || !dropdown) return;

    dropdown.hidden = !this.isOpen;
    trigger.setAttribute("aria-expanded", String(this.isOpen));

    if (focusTrigger) trigger.focus();
  }

  handleClick(event) {
    const trigger = event.target.closest("[data-header-cart-toggle]");

    if (trigger) {
      event.preventDefault();
      this.setOpen(!this.isOpen);
      return;
    }

    const removeButton = event.target.closest("[data-header-cart-remove]");

    if (removeButton) {
      event.preventDefault();
      this.removeItem(removeButton);
      return;
    }

    const root = this.root();

    if (root && !root.contains(event.target)) this.setOpen(false);
  }

  handleKeydown(event) {
    if (event.key !== "Escape" || !this.isOpen) return;

    this.setOpen(false, true);
  }

  handleMouseOver(event) {
    const root = event.target.closest("[data-header-cart]");

    if (!root || root.contains(event.relatedTarget)) return;

    window.clearTimeout(this.hoverCloseTimer);
    this.setOpen(true);
  }

  handleMouseOut(event) {
    const root = event.target.closest("[data-header-cart]");

    if (!root || root.contains(event.relatedTarget)) return;

    window.clearTimeout(this.hoverCloseTimer);
    this.hoverCloseTimer = window.setTimeout(() => this.setOpen(false), 220);
  }

  async removeItem(button) {
    const root = this.root();
    const endpoint = root?.dataset.headerCartRemoveEndpoint;
    const cartItemKey = button.dataset.headerCartRemove;

    if (!endpoint || !cartItemKey || button.disabled) return;

    button.disabled = true;
    button.setAttribute("aria-busy", "true");
    this.isOpen = true;

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: new URLSearchParams({ cart_item_key: cartItemKey }),
      });
      const data = await response.json();

      if (!response.ok || !data?.fragments) throw new Error("Cart removal failed");

      this.replaceFragments(data.fragments);
      this.setOpen(true);

      if (window.jQuery) {
        window.jQuery(document.body).trigger("removed_from_cart", [
          data.fragments,
          data.cart_hash,
          window.jQuery(button),
        ]);
      }
    } catch {
      const fallbackUrl = button.dataset.headerCartRemoveUrl || root.dataset.headerCartUrl;

      if (fallbackUrl) window.location.assign(fallbackUrl);
    }
  }

  replaceFragments(fragments) {
    Object.entries(fragments).forEach(([selector, html]) => {
      document.querySelectorAll(selector).forEach((element) => {
        const template = document.createElement("template");
        template.innerHTML = html.trim();
        const replacement = template.content.firstElementChild;

        if (replacement) element.replaceWith(replacement);
      });
    });
  }

  handleCartRefresh(eventName) {
    if (eventName === "added_to_cart") this.isOpen = true;

    this.setOpen(this.isOpen);
  }
}

new HeaderMiniCartController().init();
