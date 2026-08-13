const root = document.querySelector(".account-content");

if (root) {
  const panels = [...root.querySelectorAll("[data-account-panel]")];
  const tabs = [...root.querySelectorAll("[data-account-tab]")];

  const showPanel = (name) => {
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

  const profileForm = root.querySelector("[data-profile-form]");
  const profileActions = root.querySelector("[data-profile-actions]");
  const toggleProfile = (editing) => {
    profileForm?.querySelectorAll("input:not([type='hidden'])").forEach((input) => {
      input.disabled = !editing;
    });
    if (profileActions) profileActions.hidden = !editing;
  };

  root.querySelector("[data-profile-edit]")?.addEventListener("click", () => toggleProfile(true));
  root.querySelector("[data-profile-cancel]")?.addEventListener("click", () => {
    profileForm?.reset();
    toggleProfile(false);
  });

  const wishlist = JSON.parse(window.localStorage.getItem("maruderm-catalog-wishlist") || "[]");
  root.querySelectorAll("[data-wishlist-heading-count], [data-overview-wishlist-count]").forEach((node) => {
    node.textContent = String(Array.isArray(wishlist) ? wishlist.length : 0);
  });
}
