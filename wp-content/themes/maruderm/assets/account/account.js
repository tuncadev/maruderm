import { AccountAddressController } from "./account-address.js";
import { AccountAvatarController } from "./account-avatar.js";

const root = document.querySelector(".account-content");

if (root) {
  new AccountAvatarController(root.closest(".account-page") || root).init();
  const panels = [...root.querySelectorAll("[data-account-panel]")];
  const tabs = [...root.querySelectorAll("[data-account-tab]")];
  const orderFilters = [...root.querySelectorAll("[data-order-filter]")];
  const orders = [...root.querySelectorAll(".account-order")];
  const ordersEmpty = root.querySelector("[data-orders-empty]");

  const showPanel = (name, updateHash = true) => {
    if (!panels.some((panel) => panel.dataset.accountPanel === name)) return;

    panels.forEach((panel) => {
      const active = panel.dataset.accountPanel === name;
      panel.hidden = !active;
      panel.classList.toggle("is-active", active);
    });
    tabs.forEach((tab) => {
      const active = tab.dataset.accountTab === name;
      tab.classList.toggle("is-active", active);
      tab.setAttribute("aria-selected", active ? "true" : "false");
    });
    if (updateHash) window.history.replaceState(null, "", `#${name}`);
    if (window.innerWidth < 851) {
      root.querySelector(".account-main")?.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  };

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      if (tab.dataset.accountTabs !== "yes" || panels.length === 0) {
        window.location.assign(tab.dataset.accountUrl);
        return;
      }
      showPanel(tab.dataset.accountTab);
    });
  });

  root.querySelectorAll("[data-account-link]").forEach((button) => {
    button.addEventListener("click", () => showPanel(button.dataset.accountLink));
  });

  orderFilters.forEach((button) => {
    button.addEventListener("click", () => {
      const filter = button.dataset.orderFilter;
      let visibleOrders = 0;

      orderFilters.forEach((filterButton) => {
        filterButton.classList.toggle("is-active", filterButton === button);
      });
      orders.forEach((order) => {
        const visible = filter === "all" || order.dataset.orderStatus === filter;
        order.hidden = !visible;
        if (visible) visibleOrders += 1;
      });
      if (ordersEmpty) ordersEmpty.hidden = visibleOrders !== 0;
    });
  });

  root.querySelectorAll("[data-order-toggle]").forEach((button) => {
    button.addEventListener("click", () => {
      const order = button.closest(".account-order");
      const details = order?.querySelector(".account-order__details");
      const expanded = button.getAttribute("aria-expanded") === "true";

      button.setAttribute("aria-expanded", expanded ? "false" : "true");
      order?.classList.toggle("is-open", !expanded);
      if (details) details.hidden = expanded;
    });
  });

  root.querySelectorAll("[data-order-again-url]").forEach((button) => {
    button.addEventListener("click", () => window.location.assign(button.dataset.orderAgainUrl));
  });

  const profileForm = root.querySelector("[data-profile-form]");
  const profileActions = root.querySelector("[data-profile-actions]");
  const addressController = new AccountAddressController(root);
  addressController.init();
  const toggleProfile = (editing) => {
    profileForm
      ?.querySelectorAll(
        "input:not([type='hidden']):not([data-address-field]), select:not([data-address-field]), .account-address-add",
      )
      .forEach((control) => {
        control.disabled = !editing;
      });
    if (profileActions) profileActions.hidden = !editing;
    if (!editing) addressController.close();
  };

  root.querySelector("[data-profile-edit]")?.addEventListener("click", () => toggleProfile(true));
  root.querySelector("[data-profile-cancel]")?.addEventListener("click", () => {
    profileForm?.reset();
    toggleProfile(false);
  });

  const initialPanel = window.location.hash.replace("#", "");
  if (initialPanel) showPanel(initialPanel, false);
  window.addEventListener("hashchange", () => {
    const panel = window.location.hash.replace("#", "");
    if (panel) showPanel(panel, false);
  });

  const wishlist = JSON.parse(window.localStorage.getItem("maruderm-catalog-wishlist") || "[]");
  root.querySelectorAll("[data-wishlist-heading-count], [data-overview-wishlist-count]").forEach((node) => {
    node.textContent = String(Array.isArray(wishlist) ? wishlist.length : 0);
  });
}
