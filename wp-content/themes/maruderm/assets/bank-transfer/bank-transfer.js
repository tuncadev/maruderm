class BankTransferController {
  constructor(root) {
    this.root = root;
  }

  init() {
    this.root.addEventListener('click', (event) => this.copyValue(event));
  }

  async copyValue(event) {
    const button = event.target.closest('[data-copy-value]');
    if (!button) return;

    try {
      await navigator.clipboard.writeText(button.dataset.copyValue);
      this.showCopiedState(button);
    } catch {
      this.copyWithFallback(button);
    }
  }

  copyWithFallback(button) {
    const field = document.createElement('textarea');
    field.value = button.dataset.copyValue;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.opacity = '0';
    document.body.append(field);
    field.select();
    const copied = document.execCommand('copy');
    field.remove();
    if (copied) this.showCopiedState(button);
    else button.querySelector('span').textContent = 'Виділи вручну';
  }

  showCopiedState(button) {
    button.classList.add('is-copied');
    button.querySelector('span').textContent = 'Скопійовано';
    window.setTimeout(() => {
      button.classList.remove('is-copied');
      button.querySelector('span').textContent = 'Копіювати';
    }, 1600);
  }
}

const bankTransferPage = document.querySelector('[data-bank-transfer-page]');
if (bankTransferPage) new BankTransferController(bankTransferPage).init();
