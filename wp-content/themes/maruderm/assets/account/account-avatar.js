const MAX_UPLOAD_SIZE = 5 * 1024 * 1024;
const AVATAR_SIZE = 512;
const ACCEPTED_TYPES = new Set(["image/jpeg", "image/png", "image/webp"]);

export class AccountAvatarController {
  constructor(root) {
    this.root = root;
    this.uploader = root.querySelector("[data-avatar-uploader]");
    this.avatars = [...root.querySelectorAll("[data-account-avatar]")];
    this.input = root.querySelector("[data-avatar-input]");
    this.removeButton = root.querySelector("[data-avatar-remove]");
    this.status = root.querySelector("[data-avatar-status]");
  }

  init() {
    if (!this.uploader || !this.input || this.avatars.length === 0) return;
    this.input.addEventListener("change", () => this.handleUpload());
    this.removeButton.addEventListener("click", () => this.remove());
  }

  async handleUpload() {
    const [file] = this.input.files;
    this.setStatus("");
    if (!file) return;
    if (!ACCEPTED_TYPES.has(file.type)) {
      this.reject("Обери зображення у форматі JPG, PNG або WebP.");
      return;
    }
    if (file.size > MAX_UPLOAD_SIZE) {
      this.reject("Зображення має бути не більше 5 МБ.");
      return;
    }

    this.input.disabled = true;
    this.setStatus("Обробляємо фото…");

    try {
      const avatar = await this.createAvatar(file);
      const payload = await this.request("upload", avatar);
      await this.render(payload.url);
      this.setStatus(payload.message || "Фото профілю оновлено.");
    } catch (error) {
      this.reject(error instanceof Error ? error.message : "Не вдалося обробити зображення.");
    } finally {
      this.input.disabled = false;
      this.input.value = "";
    }
  }

  createAvatar(file) {
    return new Promise((resolve, reject) => {
      const source = new Image();
      const objectUrl = URL.createObjectURL(file);
      source.onload = () => {
        const canvas = document.createElement("canvas");
        const context = canvas.getContext("2d");
        const cropSize = Math.min(source.naturalWidth, source.naturalHeight);
        canvas.width = AVATAR_SIZE;
        canvas.height = AVATAR_SIZE;
        context.drawImage(
          source,
          (source.naturalWidth - cropSize) / 2,
          (source.naturalHeight - cropSize) / 2,
          cropSize,
          cropSize,
          0,
          0,
          AVATAR_SIZE,
          AVATAR_SIZE,
        );
        URL.revokeObjectURL(objectUrl);
        canvas.toBlob(
          (blob) => (blob ? resolve(blob) : reject(new Error("Не вдалося обробити зображення."))),
          "image/webp",
          0.86,
        );
      };
      source.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        reject(new Error("Не вдалося обробити зображення. Спробуй інший файл."));
      };
      source.src = objectUrl;
    });
  }

  async request(operation, avatar = null) {
    const body = new FormData();
    body.append("action", "maruderm_update_account_avatar");
    body.append("nonce", this.uploader.dataset.avatarNonce || "");
    body.append("operation", operation);
    if (avatar) body.append("avatar", avatar, "maruderm-avatar.webp");

    const response = await fetch(this.uploader.dataset.avatarAjaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body,
    });
    const payload = await response.json();

    if (!response.ok || !payload.success) {
      throw new Error(payload.data?.message || "Не вдалося оновити фото.");
    }

    return payload.data;
  }

  render(source) {
    return new Promise((resolve, reject) => {
      const probe = new Image();
      probe.onload = () => {
        this.applySource(source);
        resolve();
      };
      probe.onerror = () => {
        this.resetToInitials();
        reject(new Error("Збережене фото недоступне. Показуємо ініціали."));
      };
      probe.src = source;
    });
  }

  applySource(source) {
    this.avatars.forEach((avatar) => {
      const image = avatar.querySelector("[data-account-avatar-image]");
      const initials = avatar.querySelector("[data-account-avatar-initials]");
      image.src = source;
      image.hidden = false;
      initials.hidden = true;
      avatar.classList.add("has-image");
    });
    this.removeButton.hidden = false;
  }

  async remove() {
    this.removeButton.disabled = true;
    this.setStatus("Видаляємо фото…");

    try {
      const payload = await this.request("remove");
      this.resetToInitials();
      this.input.value = "";
      this.setStatus(payload.message || "Фото видалено. Показуємо ініціали.");
    } catch (error) {
      this.setStatus(error instanceof Error ? error.message : "Не вдалося видалити фото.", true);
    } finally {
      this.removeButton.disabled = false;
    }
  }

  resetToInitials() {
    this.avatars.forEach((avatar) => {
      const image = avatar.querySelector("[data-account-avatar-image]");
      const initials = avatar.querySelector("[data-account-avatar-initials]");
      image.removeAttribute("src");
      image.hidden = true;
      initials.hidden = false;
      avatar.classList.remove("has-image");
    });
    this.removeButton.hidden = true;
  }

  reject(message) {
    this.input.value = "";
    this.setStatus(message, true);
  }

  setStatus(message, isError = false) {
    this.status.textContent = message;
    this.status.classList.toggle("is-error", isError);
  }
}
