import "./landing-page.css";

const revealLandingSections = () => {
  const landing = document.querySelector(".md-landing");
  const sections = document.querySelectorAll(".md-landing .md-reveal");

  landing?.classList.add("md-animate");

  if (!("IntersectionObserver" in window)) {
    sections.forEach((section) => section.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    { rootMargin: "0px 0px -8%", threshold: 0.08 },
  );

  sections.forEach((section) => observer.observe(section));
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", revealLandingSections);
} else {
  revealLandingSections();
}
