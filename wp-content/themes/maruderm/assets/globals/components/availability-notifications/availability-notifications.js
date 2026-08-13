const settings = window.marudermStockNotifications;

if (settings) {
  const activeProductIds = new Set(
    (settings.activeProductIds || []).map((productId) => String(productId)),
  );

  const ensureFeedbackRegion = () => {
    let region = document.querySelector("[data-stock-notification-feedback]");

    if (region) return region;

    region = document.createElement("div");
    region.className = "stock-notification-feedback";
    region.dataset.stockNotificationFeedback = "";
    region.setAttribute("role", "status");
    region.setAttribute("aria-live", "polite");
    document.body.append(region);

    return region;
  };

  const announce = (message, isError = false) => {
    const region = ensureFeedbackRegion();
    region.textContent = message;
    region.classList.toggle("is-error", isError);
    region.classList.add("is-visible");
    window.clearTimeout(announce.timeoutId);
    announce.timeoutId = window.setTimeout(() => region.classList.remove("is-visible"), 3600);
  };

  const syncButton = (button, active) => {
    button.classList.toggle("is-active", active);
    button.setAttribute("aria-pressed", String(active));

    const label = button.querySelector("[data-stock-notify-label]");
    if (label) {
      label.textContent = active ? "Сповіщення увімкнено" : "Повідомити, коли з’явиться";
    }

    if (button.classList.contains("product-card__notify")) {
      const cardName = button.closest("[data-product-name]")?.dataset.productName || "товар";
      button.setAttribute(
        "aria-label",
        active
          ? `Сповіщення про наявність ${cardName} увімкнено`
          : `Повідомити, коли ${cardName} з’явиться в наявності`,
      );
    }
  };

  const syncProduct = (productId, active) => {
    const normalizedId = String(productId);
    if (active) activeProductIds.add(normalizedId);
    else activeProductIds.delete(normalizedId);

    document.querySelectorAll("[data-stock-notify]").forEach((button) => {
      if (button.dataset.stockNotify === normalizedId) syncButton(button, active);
    });
  };

  const refreshEmptyState = () => {
    const list = document.querySelector("[data-account-notifications]");
    const empty = document.querySelector("[data-account-notifications-empty]");
    if (!list || !empty) return;

    const hasRows = Boolean(list.querySelector("[data-notification-product]"));
    list.hidden = !hasRows;
    empty.hidden = hasRows;
  };

  const removeAccountRows = (productId) => {
    document.querySelectorAll("[data-notification-product]").forEach((row) => {
      if (row.dataset.notificationProduct !== String(productId)) return;
      row.classList.add("is-removing");
      window.setTimeout(() => {
        row.remove();
        refreshEmptyState();
      }, 180);
    });
  };

  document.querySelectorAll("[data-stock-notify]").forEach((button) => {
    syncButton(button, activeProductIds.has(button.dataset.stockNotify));
  });

  document.addEventListener("click", async (event) => {
    const button = event.target.closest("[data-stock-notify]");
    if (!button) return;

    event.preventDefault();

    if (!settings.authenticated) {
      window.location.assign(settings.loginUrl);
      return;
    }

    const productId = button.dataset.stockNotify;
    if (!productId || button.disabled) return;

    button.disabled = true;
    button.classList.add("is-loading");

    const body = new URLSearchParams({
      action: settings.action,
      nonce: settings.nonce,
      product_id: productId,
    });

    try {
      const response = await fetch(settings.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
      });
      const payload = await response.json();

      if (!payload.success) {
        if (response.status === 401 && payload.data?.loginUrl) {
          window.location.assign(payload.data.loginUrl);
          return;
        }

        throw new Error(payload.data?.message || "Не вдалося оновити сповіщення.");
      }

      syncProduct(payload.data.productId, Boolean(payload.data.active));
      if (!payload.data.active) removeAccountRows(payload.data.productId);
      announce(payload.data.message);
      document.dispatchEvent(
        new CustomEvent("maruderm:stock-notifications-change", { detail: payload.data }),
      );
    } catch (error) {
      announce(error.message || "Не вдалося оновити сповіщення.", true);
    } finally {
      button.disabled = false;
      button.classList.remove("is-loading");
    }
  });

  const singleButton = document.querySelector("[data-product-stock-notify]");
  if (singleButton && window.jQuery) {
    const parentProductId = singleButton.dataset.parentProductId;
    const defaultHidden = singleButton.hidden;
    const variationForm = window.jQuery(singleButton.closest(".summary")).find(".variations_form");

    variationForm.on("found_variation", (_event, variation) => {
      if (!variation?.variation_id || variation.is_in_stock) {
        singleButton.hidden = true;
        return;
      }

      singleButton.dataset.stockNotify = String(variation.variation_id);
      singleButton.hidden = false;
      syncButton(singleButton, activeProductIds.has(String(variation.variation_id)));
    });

    variationForm.on("hide_variation reset_data", () => {
      singleButton.dataset.stockNotify = parentProductId;
      singleButton.hidden = defaultHidden;
      syncButton(singleButton, activeProductIds.has(parentProductId));
    });
  }
}
