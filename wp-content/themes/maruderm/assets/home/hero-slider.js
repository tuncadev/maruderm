const HERO_AUTOPLAY_DELAY = 6500;
const HERO_SWIPE_THRESHOLD = 48;

class HomeHeroSlider {
  constructor(root) {
    this.root = root;
    this.slides = [...root.querySelectorAll("[data-home-hero-slide]")];
    this.dots = [...root.querySelectorAll("[data-home-hero-dot]")];
    this.previousButton = root.querySelector("[data-home-hero-previous]");
    this.nextButton = root.querySelector("[data-home-hero-next]");
    this.autoplayButton = root.querySelector("[data-home-hero-autoplay]");
    this.status = root.querySelector("[data-home-hero-status]");
    this.motionPreference = window.matchMedia("(prefers-reduced-motion: reduce)");
    this.activeIndex = Math.max(
      0,
      this.slides.findIndex((slide) => slide.classList.contains("is-active")),
    );
    this.pauseReasons = new Set();
    this.autoplayTimer = null;
    this.touchStartX = null;

    this.handlePrevious = this.handlePrevious.bind(this);
    this.handleNext = this.handleNext.bind(this);
    this.handleAutoplayToggle = this.handleAutoplayToggle.bind(this);
    this.handleKeydown = this.handleKeydown.bind(this);
    this.handleTouchStart = this.handleTouchStart.bind(this);
    this.handleTouchEnd = this.handleTouchEnd.bind(this);
    this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
    this.handleMotionChange = this.handleMotionChange.bind(this);

    this.init();
  }

  init() {
    if (
      this.slides.length < 2 ||
      this.dots.length !== this.slides.length ||
      !this.previousButton ||
      !this.nextButton ||
      !this.autoplayButton ||
      !this.status
    ) {
      return;
    }

    this.previousButton.addEventListener("click", this.handlePrevious);
    this.nextButton.addEventListener("click", this.handleNext);
    this.autoplayButton.addEventListener("click", this.handleAutoplayToggle);
    this.dots.forEach((dot) => {
      dot.addEventListener("click", () =>
        this.showSlide(Number(dot.dataset.homeHeroDot)),
      );
    });
    this.root.addEventListener("keydown", this.handleKeydown);
    this.root.addEventListener("touchstart", this.handleTouchStart, {
      passive: true,
    });
    this.root.addEventListener("touchend", this.handleTouchEnd, {
      passive: true,
    });
    this.root.addEventListener("mouseenter", () => this.pause("hover"));
    this.root.addEventListener("mouseleave", () => this.resume("hover"));
    this.root.addEventListener("focusin", () => this.pause("focus"));
    this.root.addEventListener("focusout", (event) => {
      if (!this.root.contains(event.relatedTarget)) this.resume("focus");
    });
    document.addEventListener("visibilitychange", this.handleVisibilityChange);
    this.motionPreference.addEventListener("change", this.handleMotionChange);

    if (this.motionPreference.matches) this.pauseReasons.add("motion");
    if (document.hidden) this.pauseReasons.add("visibility");
    this.showSlide(this.activeIndex, false);
    this.startAutoplay();
  }

  showSlide(index, announce = true) {
    const nextIndex = (index + this.slides.length) % this.slides.length;
    this.activeIndex = nextIndex;

    this.slides.forEach((slide, slideIndex) => {
      const active = slideIndex === nextIndex;
      slide.classList.toggle("is-active", active);
      slide.setAttribute("aria-hidden", String(!active));
      slide.inert = !active;
    });
    this.dots.forEach((dot, dotIndex) => {
      const active = dotIndex === nextIndex;
      dot.classList.toggle("is-active", active);
      dot.setAttribute("aria-current", String(active));
    });

    const activeSlide = this.slides[nextIndex];
    this.root.dataset.imagePosition = activeSlide.dataset.imagePosition || "right";
    this.root.dataset.slideTheme = activeSlide.dataset.slideTheme || "skin";
    if (announce) {
      this.status.textContent = `Слайд ${nextIndex + 1} з ${this.slides.length}`;
    }
  }

  handlePrevious() {
    this.showSlide(this.activeIndex - 1);
    this.restartAutoplay();
  }

  handleNext() {
    this.showSlide(this.activeIndex + 1);
    this.restartAutoplay();
  }

  handleAutoplayToggle() {
    const paused = this.pauseReasons.has("user");
    if (paused) {
      this.resume("user");
    } else {
      this.pause("user");
    }
    this.autoplayButton.classList.toggle("is-paused", !paused);
    this.autoplayButton.setAttribute("aria-pressed", String(!paused));
    this.autoplayButton.setAttribute(
      "aria-label",
      paused
        ? "Призупинити автоматичну зміну слайдів"
        : "Продовжити автоматичну зміну слайдів",
    );
  }

  handleKeydown(event) {
    if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
    if (event.key === "ArrowLeft") {
      event.preventDefault();
      this.handlePrevious();
    }
    if (event.key === "ArrowRight") {
      event.preventDefault();
      this.handleNext();
    }
  }

  handleTouchStart(event) {
    this.touchStartX = event.changedTouches[0]?.clientX ?? null;
  }

  handleTouchEnd(event) {
    if (this.touchStartX === null) return;
    const distance =
      (event.changedTouches[0]?.clientX ?? this.touchStartX) - this.touchStartX;
    this.touchStartX = null;
    if (Math.abs(distance) < HERO_SWIPE_THRESHOLD) return;
    distance > 0 ? this.handlePrevious() : this.handleNext();
  }

  handleVisibilityChange() {
    document.hidden ? this.pause("visibility") : this.resume("visibility");
  }

  handleMotionChange(event) {
    event.matches ? this.pause("motion") : this.resume("motion");
  }

  pause(reason) {
    this.pauseReasons.add(reason);
    this.stopAutoplay();
  }

  resume(reason) {
    this.pauseReasons.delete(reason);
    this.startAutoplay();
  }

  restartAutoplay() {
    this.stopAutoplay();
    this.startAutoplay();
  }

  startAutoplay() {
    if (this.autoplayTimer || this.pauseReasons.size > 0) return;
    this.autoplayTimer = window.setInterval(
      () => this.showSlide(this.activeIndex + 1, false),
      HERO_AUTOPLAY_DELAY,
    );
  }

  stopAutoplay() {
    if (!this.autoplayTimer) return;
    window.clearInterval(this.autoplayTimer);
    this.autoplayTimer = null;
  }
}

document
  .querySelectorAll("[data-home-hero]")
  .forEach((hero) => new HomeHeroSlider(hero));
