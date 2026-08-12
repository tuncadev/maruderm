const initSlick = () => {
  const $ = window.jQuery;

  if (!$ || typeof $.fn.slick !== "function") {
    return;
  }

  $(".js-maruderm-home-slider").not(".slick-initialized").slick({
    arrows: false,
    dots: true,
    autoplay: false,
    infinite: true,
    rtl: $("body").hasClass("rtl"),
  });

  $(".mf-elementor-product-deals-carousel").each(function initDeals() {
    const $selector = $(this);
    const settings = $selector.data("settings") || {};

    $selector.find("ul.products").not(".slick-initialized").slick({
      rtl: $("body").hasClass("rtl"),
      slidesToShow: Number.parseInt(settings.slidesToShow, 10) || 5,
      slidesToScroll: Number.parseInt(settings.slidesToScroll, 10) || 5,
      arrows: true,
      dots: true,
      infinite: settings.infinite === "yes",
      prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
      nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
      autoplay: settings.autoplay === "yes",
      autoplaySpeed: Number.parseInt(settings.autoplay_speed, 10) || 5000,
      responsive: [
        { breakpoint: 992, settings: { slidesToShow: Number.parseInt(settings.slidesToShow_tablet, 10) || 3, slidesToScroll: Number.parseInt(settings.slidesToScroll_tablet, 10) || 3 } },
        { breakpoint: 480, settings: { slidesToShow: Number.parseInt(settings.slidesToShow_mobile, 10) || 2, slidesToScroll: Number.parseInt(settings.slidesToScroll_mobile, 10) || 2 } },
      ],
    });
  });

  $(".mf-products-carousel").each(function initProducts() {
    const $selector = $(this);
    const settings = $selector.data("settings") || {};

    $selector.find("ul.products").not(".slick-initialized").slick({
      rtl: $("body").hasClass("rtl"),
      slidesToShow: Number.parseInt(settings.slidesToShow, 10) || 5,
      slidesToScroll: Number.parseInt(settings.slidesToScroll, 10) || 5,
      arrows: true,
      dots: true,
      infinite: settings.infinite === "yes",
      prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
      nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
      autoplay: settings.autoplay === "yes",
      autoplaySpeed: Number.parseInt(settings.autoplay_speed, 10) || 3000,
      speed: Number.parseInt(settings.speed, 10) || 800,
      responsive: [
        { breakpoint: 992, settings: { slidesToShow: Number.parseInt(settings.slidesToShow_tablet, 10) || 3, slidesToScroll: Number.parseInt(settings.slidesToScroll_tablet, 10) || 3 } },
        { breakpoint: 767, settings: { slidesToShow: Number.parseInt(settings.slidesToShow_mobile, 10) || 2, slidesToScroll: Number.parseInt(settings.slidesToScroll_mobile, 10) || 2 } },
      ],
    });
  });

  $(".martfury-countdown").each(function initCountdown() {
    if (typeof $(this).mf_countdown === "function") {
      $(this).mf_countdown();
    }
  });
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initSlick);
} else {
  initSlick();
}
