const WISHLIST_KEY = "maruderm-catalog-wishlist";

const readWishlist = () => {
  try {
    const value = JSON.parse(window.localStorage.getItem(WISHLIST_KEY) ?? "[]");
    return new Set(Array.isArray(value) ? value.map(String) : []);
  } catch {
    return new Set();
  }
};

const writeWishlist = (wishlist) => {
  window.localStorage.setItem(WISHLIST_KEY, JSON.stringify([...wishlist]));
};

const initProductPage = (root) => {
  const productId = String(root.dataset.productId ?? "");
  const mainImage = root.querySelector("[data-gallery-main]");
  const stage = root.querySelector("[data-gallery-stage]");
  const wishlist = readWishlist();

  root.querySelectorAll("[data-gallery-image]").forEach((button) => {
    button.addEventListener("click", () => {
      root.querySelectorAll("[data-gallery-image]").forEach((item) => item.classList.remove("is-active"));
      button.classList.add("is-active");
      stage.dataset.view = button.dataset.galleryView ?? "clean";
      if (mainImage && button.dataset.gallerySrc) {
        mainImage.style.backgroundImage = `url("${button.dataset.gallerySrc}")`;
      }
    });
  });

  const productWishlist = root.querySelector("[data-product-wishlist]");
  productWishlist?.classList.toggle("is-active", wishlist.has(productId));
  productWishlist?.setAttribute("aria-pressed", wishlist.has(productId) ? "true" : "false");
  productWishlist?.addEventListener("click", () => {
    wishlist.has(productId) ? wishlist.delete(productId) : wishlist.add(productId);
    writeWishlist(wishlist);
    productWishlist.classList.toggle("is-active", wishlist.has(productId));
    productWishlist.setAttribute("aria-pressed", wishlist.has(productId) ? "true" : "false");
  });

  root.querySelectorAll(".product-card").forEach((card) => {
    const cardId = String(card.dataset.productId ?? "");
    const button = card.querySelector("[data-wishlist-toggle]");
    button?.classList.toggle("is-active", wishlist.has(cardId));
    button?.addEventListener("click", () => {
      wishlist.has(cardId) ? wishlist.delete(cardId) : wishlist.add(cardId);
      writeWishlist(wishlist);
      button.classList.toggle("is-active", wishlist.has(cardId));
    });
  });

  const quantity = root.querySelector(".quantity-control input.qty");
  const updateQuantity = (increment) => {
    if (!quantity) return;
    const minimum = Number(quantity.min || 1);
    const maximum = Number(quantity.max || 999);
    quantity.value = String(Math.min(maximum, Math.max(minimum, Number(quantity.value || 1) + increment)));
    quantity.dispatchEvent(new Event("change", { bubbles: true }));
  };
  root.querySelector("[data-quantity-minus]")?.addEventListener("click", () => updateQuantity(-1));
  root.querySelector("[data-quantity-plus]")?.addEventListener("click", () => updateQuantity(1));

  root.querySelectorAll(".product-accordion > button").forEach((button) => {
    button.addEventListener("click", () => {
      const accordion = button.closest(".product-accordion");
      const isOpen = accordion.classList.toggle("is-open");
      button.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  });
};

document.querySelectorAll("[data-product-page]").forEach(initProductPage);
