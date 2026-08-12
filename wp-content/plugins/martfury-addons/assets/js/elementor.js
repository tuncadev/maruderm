(function ($) {
	'use strict';

	/**
	 * Product Deals Carousel
	 */
	var productDealsCarouselHandler = function ($scope, $) {
		$scope.find('.mf-elementor-product-deals-carousel').each(function () {
			var $selector = $(this),
				elementSettings = $selector.data('settings'),
				slidesToShow = parseInt(elementSettings.slidesToShow),
				slidesToScroll = parseInt(elementSettings.slidesToScroll),
				slidesToShow_tablet = parseInt(elementSettings.slidesToShow_tablet),
				slidesToScroll_tablet = parseInt(elementSettings.slidesToScroll_tablet),
				slidesToShow_mobile = parseInt(elementSettings.slidesToShow_mobile),
				slidesToScroll_mobile = parseInt(elementSettings.slidesToScroll_mobile);

			$selector.find('ul.products').not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: slidesToShow,
				slidesToScroll: slidesToScroll,
				arrows: true,
				dots: true,
				infinite: 'yes' === elementSettings.infinite,
				prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
				nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed),
				responsive: [
					{
						breakpoint: 1366,
						settings: {
							slidesToShow: slidesToShow > 5 ? 5 : slidesToShow,
							slidesToScroll: slidesToScroll > 5 ? 5 : slidesToScroll
						}
					},
					{
						breakpoint: 1200,
						settings: {
							slidesToShow: slidesToShow > 4 ? 4 : slidesToShow,
							slidesToScroll: slidesToScroll > 4 ? 4 : slidesToScroll
						},
					},
					{
						breakpoint: 992,
						settings: {
							slidesToShow: slidesToShow_tablet,
							slidesToScroll: slidesToScroll_tablet,
						}
					},
					{
						breakpoint: 767,
						settings: {
							slidesToShow: 2,
							slidesToScroll: slidesToScroll > 2 ? 2 : slidesToScroll
						},
					},
					{
						breakpoint: 480,
						settings: {
							slidesToShow:  slidesToShow_mobile,
							slidesToScroll: slidesToScroll_mobile,
						},
					}
				]
			});

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});

		});
	};

	/**
	 * Product Deals Carousel 2
	 */
	var productDealsCarousel2Handler = function ($scope, $) {
		$scope.find('.mf-product-deals-carousel-2').each(function () {
			var $selector = $(this),
				elementSettings = $selector.data('settings'),
				$gallery = $selector.find('.woocommerce-product-gallery');

			$selector.find('ul.products').not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: 1,
				slidesToScroll: 1,
				arrows: true,
				dots: false,
				infinite: 'yes' === elementSettings.infinite,
				prevArrow: $selector.find('.slick-prev-arrow'),
				nextArrow: $selector.find('.slick-next-arrow'),
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed)
			});

			var options = {
				selector: '.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image',
				allowOneSlide: false,
				animation: "slide",
				animationLoop: false,
				animationSpeed: 500,
				controlNav: "thumbnails",
				directionNav: false,
				rtl: false,
				slideshow: false,
				smoothHeight: true,
				start: function () {
					$gallery.css('opacity', 1);
				},
			};

			$gallery.flexslider(options);

			$selector.find('.deal-expire-countdown').each(function () {
				$(document).trigger('deal_expire_countdown', $(this));
			});

			$gallery.each(function () {
				var $el = $(this);
				$el.imagesLoaded(function () {

					var $thumbnail = $el.find('.flex-control-thumbs');

					setTimeout(function () {
						if ($thumbnail.length < 1) {
							return;
						}
						var columns = $el.data('columns');
						var count = $thumbnail.find('li').length;
						if (count > columns) {
							$thumbnail.not('.slick-initialized').slick({
								slidesToShow: columns,
								slidesToScroll: 1,
								focusOnSelect: true,
								vertical: true,
								infinite: false,
								prevArrow: '<span class="icon-chevron-up slick-prev-arrow"></span>',
								nextArrow: '<span class="icon-chevron-down slick-next-arrow"></span>',
								responsive: [
									{
										breakpoint: 768,
										settings: {
											slidesToShow: 4
										}
									},
									{
										breakpoint: 480,
										settings: {
											slidesToShow: 3
										}
									}
								]
							});
						} else {
							$thumbnail.addClass('no-slick');
						}
					}, 100);

				});
			});

			$selector.find('.woocommerce-product-gallery__image').on('click', function () {
				var href = $(this).closest('div.product').find('.product-title > a').attr('href');

				location.href = href;
				return false;
			});

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});

		});

	};

	/**
	 * LazyLoad (no-op: native loading="lazy" used)
	 */
	var lazyLoadHandler = function ($els) {};

	var getProductsCarouselHandler = function ($els) {
		var $selector = $els,
			elementSettings = $selector.data('settings'),
			slidesToShow = parseInt(elementSettings.slidesToShow),
			slidesToScroll = parseInt(elementSettings.slidesToScroll),
			slidesToShow_tablet = parseInt(elementSettings.slidesToShow_tablet),
			slidesToScroll_tablet = parseInt(elementSettings.slidesToScroll_tablet),
			slidesToShow_mobile = parseInt(elementSettings.slidesToShow_mobile),
			slidesToScroll_mobile = parseInt(elementSettings.slidesToScroll_mobile);
		$selector.find('ul.products').not('.slick-initialized').slick({
			rtl: $('body').hasClass('rtl'),
			slidesToShow: slidesToShow,
			slidesToScroll: slidesToScroll,
			arrows: true,
			dots: true,
			infinite: 'yes' === elementSettings.infinite,
			prevArrow: elementSettings.arrows_background == 'yes' ? '<span class="icon-chevron-left slick-prev-arrow"><span class="mf-arrow-bg"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="123" viewBox="0 0 28 123"><path fill="currentColor" fill-rule="evenodd" d="M135,1763s1.945-18.7,11.511-28.64c14.019-14.57,16.641-21.14,16.481-33.19-0.3-22.74-27.992-30.59-27.992-57.81V1763Z" transform="translate(-135 -1640)"/> </svg></span></span>' : '<span class="icon-chevron-left slick-prev-arrow"></span>',
			nextArrow:  elementSettings.arrows_background == 'yes' ? '<span class="icon-chevron-right slick-next-arrow"><span class="mf-arrow-bg"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="123" viewBox="0 0 28 123"><path fill="currentColor" fill-rule="evenodd" d="M1785,1763s-1.94-18.7-11.51-28.64c-14.02-14.57-16.64-21.14-16.48-33.19,0.3-22.74,27.99-30.59,27.99-57.81V1763Z" transform="translate(-1757 -1640)"/></svg></span></span>' : '<span class="icon-chevron-right slick-next-arrow"></span>',
			autoplay: 'yes' === elementSettings.autoplay,
			autoplaySpeed: parseInt(elementSettings.autoplay_speed),
			speed: parseInt(elementSettings.speed),
			responsive: [
				{
					breakpoint: 1366,
					settings: {
						slidesToShow: slidesToShow > 5 ? 5 : slidesToShow,
						slidesToScroll: slidesToScroll > 5 ? 5 : slidesToScroll
					}
				},
				{
					breakpoint: 1200,
					settings: {
						slidesToShow: slidesToShow > 4 ? 4 : slidesToShow,
						slidesToScroll: slidesToScroll > 4 ? 4 : slidesToScroll
					}
				},
				{
					breakpoint: 992,
					settings: {
						slidesToShow: slidesToShow_tablet > 3 ? 3 : slidesToShow_tablet,
						slidesToScroll: slidesToScroll_tablet > 3 ? 3 : slidesToScroll_tablet
					}
				},
				{
					breakpoint: 767,
					settings: {
						slidesToShow: slidesToShow_mobile > 2 ? 2 : slidesToShow_mobile,
						slidesToScroll: slidesToScroll_mobile > 2 ? 2 : slidesToScroll_mobile
					}
				}
			]
		});

		$selector.on('afterChange', function () {
			lazyLoadHandler($selector);
		});
	};

	/**
	 * CountDown
	 */
	var countDownHandler = function ($scope, $) {
		$scope.find('.martfury-countdown').mf_countdown();
	};


	/**
	 * Catagory Tabs Carousel
	 */
	var categoryTabsCarouselHandler = function ($scope, $) {
		$scope.find('.mf-category-tabs').each(function () {
			var $selector = $(this);

			$selector.find('ul.tabs-nav').not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: 8,
				prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
				nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
				infinite: false,
				responsive: [
					{
						breakpoint: 1200,
						settings: {
							slidesToShow: 6,
							slidesToScroll: 6
						}
					},
					{
						breakpoint: 992,
						settings: {
							slidesToShow: 4,
							slidesToScroll: 4
						}
					},
					{
						breakpoint: 480,
						settings: {
							slidesToShow: 2,
							slidesToScroll: 2
						}
					}
				]
			});

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});
		});
	};

	/**
	 * category Tabs
	 */
	var categoryTabsHandler = function ($scope, $) {
		$scope.find('.martfury-tabs').mrtabs();
	};

	/**
	 * Product Carousel
	 */
	var productsListCarouselHandler = function ($scope, $) {
		var $selector = $scope.find('.mf-products-list-carousel');
		getProductsCarouselHandler($selector);
	};

	/**
	 * Product Carousel
	 */
	var productsCarouselHandler = function ($scope, $) {
		var $selector = $scope.find('.mf-products-carousel');

		if (!$selector.hasClass('no-infinite')) {
			productCarouselInfinite($selector);
		} else {
			getProductsCarouselHandler($selector);
		}

		function productCarouselInfinite($selector) {

			$(window).on('scroll', function () {

				if ($selector.hasClass('no-infinite')) {
					return;
				}

				var offSet = 0;

				if ($selector.is(':in-viewport(' + offSet + ')')) {
					getProductCarouselAjax($selector);
					$selector.addClass('no-infinite');
				}

			}).trigger('scroll');
		}

		function getProductCarouselAjax($selector) {

			if (typeof mf_elementor_data === 'undefined') {
				return;
			}

			var dataSettings = $selector.find('.mf-products-carousel-loading').data('settings'),
				ajax_url = mf_elementor_data.ajax_url.toString().replace('%%endpoint%%', 'mf_elementor_get_elements');


			$.ajax({
				url: ajax_url,
				dataType: 'json',
				method: 'post',
				data: {
					params: JSON.stringify(dataSettings),
					element: 'productsCarousel'
				},
				success: function (response) {
					$selector.html(response.data);
					getProductsCarouselHandler($selector);
					lazyLoadHandler($selector);
					$(document.body).trigger('martfury_get_products_ajax_success');
				}
			});
		}

	};

	var recentlyViewedProductsCarouselHandler = function ($scope, $) {
		$scope.find('.mf-product-recently-viewed-carousel').each(function () {
			var $selector = $(this),
				elementSettings = $selector.data('settings'),
				slidesToShow = parseInt(elementSettings.slidesToShow),
				slidesToScroll = parseInt(elementSettings.slidesToScroll);

			$selector.find( 'ul.products' ).not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: slidesToShow,
				slidesToScroll: slidesToScroll,
				arrows: true,
				dots: true,
				infinite: 'yes' === elementSettings.infinite,
				prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
				nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed),
				speed: parseInt(elementSettings.speed),
				responsive: [
					{
						breakpoint: 1600,
						settings: {
							slidesToShow: slidesToShow > 5 ? 5 : slidesToShow,
							slidesToScroll: slidesToScroll > 5 ? 5 : slidesToScroll
						}
					},
					{
						breakpoint: 1366,
						settings: {
							slidesToShow: slidesToShow > 4 ? 4 : slidesToShow,
							slidesToScroll: slidesToScroll > 4 ? 4 : slidesToScroll
						}
					},
					{
						breakpoint: 1200,
						settings: {
							slidesToShow: slidesToShow > 3 ? 3 : slidesToShow,
							slidesToScroll: slidesToScroll > 3 ? 3 : slidesToScroll
						}
					},
					{
						breakpoint: 992,
						settings: {
							slidesToShow: slidesToShow > 2 ? 2 : slidesToShow,
							slidesToScroll: slidesToScroll > 2 ? 2 : slidesToScroll
						}
					},
					{
						breakpoint: 767,
						settings: {
							slidesToShow: slidesToShow > 2 ? 2 : slidesToShow,
							slidesToScroll: slidesToScroll > 2 ? 2 : slidesToScroll
						}
					},
					{
						breakpoint: 480,
						settings: {
							slidesToShow: 1,
							slidesToScroll: 1
						}
					}
				]
			});

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});

		});
	};

	/**
	 * Products Grid
	 */

	var productsGridHandler = function ($scope, $) {
		var $selector = $scope.find('.mf-products-grid');

		if (!$selector.hasClass('no-infinite')) {
			productGridInfinite($selector);
		}

		function productGridInfinite($selector) {

			$(window).on('scroll', function () {

				if ($selector.hasClass('no-infinite')) {
					return;
				}

				var offSet = 0;

				if ($selector.is(':in-viewport(' + offSet + ')')) {
					getProductGridAjax($selector);
					$selector.addClass('no-infinite');
				}

			}).trigger('scroll');
		}

		function getProductGridAjax($selector) {

			if (typeof mf_elementor_data === 'undefined') {
				return;
			}

			var dataSettings = $selector.find('.mf-products-grid-loading').data('settings'),
				ajax_url = mf_elementor_data.ajax_url.toString().replace('%%endpoint%%', 'mf_elementor_get_elements');


			$.ajax({
				url: ajax_url,
				dataType: 'json',
				method: 'post',
				data: {
					params: JSON.stringify(dataSettings),
					element: 'productsGrid'
				},
				success: function (response) {
					$selector.html(response.data);
					lazyLoadHandler($selector);
					$(document.body).trigger('martfury_get_products_ajax_success');
				}
			});
		}
	};

	/**
	 *    Load Brands
	 */
	var productBrandsHandler = function ($scope, $) {

		// Product Brands Carousel
		var $selector = $scope.find('.mf-elementor-brands-grid');
		getProductBrandCarousel($selector);

		// Brands Load More
		productBrandsInfinite($selector);

		function productBrandsInfinite($selector) {

			$(window).on('scroll', function () {

				var offSet = 0;

				if ($selector.is(':in-viewport(' + offSet + ')')) {
					$selector.find('.navigation-number a').trigger('click');
				}

			}).trigger('scroll');

			$selector.on('click', '.navigation-number a', function (e) {
				e.preventDefault();

				var $el = $(this);

				if ($el.data('requestRunning')) {
					return;
				}

				$el.data('requestRunning', true);

				var $pagination = $el.closest('.navigation-number'),
					$products = $pagination.prev('.product-brands');

				$el.addClass('loading');

				$.get(
					$el.attr('href'),
					function (response) {
						var $wrapper = $(response).find('.mf-elementor-brands-grid'),
							content = $wrapper.find('.product-brands').children('.brand-item-wrapper'),
							$pagination_html = $wrapper.find('.navigation-number').html();

						$pagination.html($pagination_html);

						$products.append(content);

						lazyLoadHandler(content);

						getProductBrandCarousel(content.closest('.mf-elementor-brands-grid'));

						$pagination.find('a')
							.data('requestRunning', false);

						$el.removeClass('loading');

					}
				);
			});
		}

		function getProductBrandCarousel($selector) {
			var elementSettings = $selector.data('settings'),
				slidesToShow = parseInt(elementSettings.slidesToShow),
				slidesToScroll = parseInt(elementSettings.slidesToScroll);

			$selector.find('ul.products').not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: slidesToShow,
				slidesToScroll: slidesToScroll,
				arrows: true,
				dots: false,
				infinite: 'yes' === elementSettings.infinite,
				prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
				nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed),
				speed: parseInt(elementSettings.speed),
				responsive: [
					{
						breakpoint: 992,
						settings: {
							slidesToShow: slidesToShow > 2 ? 2 : slidesToShow,
							slidesToScroll: slidesToScroll > 2 ? 2 : slidesToScroll
						}
					},
					{
						breakpoint: 480,
						settings: {
							slidesToShow: slidesToShow > 1 ? 1 : slidesToShow,
							slidesToScroll: slidesToScroll > 1 ? 1 : slidesToScroll
						}
					}
				]
			});

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});
		}
	};

	var brandsImagesCarouselHandler = function ($scope, $) {
		var $selector = $scope.find('.elementor-brand-images-carousel');

		var elementSettings = $selector.data('settings'),
			slidesToShow = parseInt(elementSettings.slidesToShow),
			slidesToScroll = parseInt(elementSettings.slidesToScroll);

		$selector.find('.images-list').not('.slick-initialized').slick({
			rtl: $('body').hasClass('rtl'),
			slidesToShow: slidesToShow,
			slidesToScroll: slidesToScroll,
			arrows: true,
			dots: false,
			infinite: 'yes' === elementSettings.infinite,
			prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
			nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
			autoplay: 'yes' === elementSettings.autoplay,
			autoplaySpeed: parseInt(elementSettings.autoplay_speed),
			speed: parseInt(elementSettings.speed),
			responsive: [
				{
					breakpoint: 992,
					settings: {
						slidesToShow: slidesToShow > 4 ? 4 : slidesToShow,
						slidesToScroll: slidesToScroll > 4 ? 4 : slidesToScroll
					}
				},
				{
					breakpoint: 800,
					settings: {
						slidesToShow: slidesToShow > 3 ? 3 : slidesToShow,
						slidesToScroll: slidesToScroll > 3 ? 3 : slidesToScroll
					}
				},
				{
					breakpoint: 600,
					settings: {
						slidesToShow: slidesToShow > 2 ? 2 : slidesToShow,
						slidesToScroll: slidesToScroll > 2 ? 2 : slidesToScroll
					}
				},
				{
					breakpoint: 400,
					settings: {
						slidesToShow: slidesToShow > 1 ? 1 : slidesToShow,
						slidesToScroll: slidesToScroll > 1 ? 1 : slidesToScroll
					}
				}
			]
		});

		$selector.on('afterChange', function () {
			lazyLoadHandler($selector);
		});
	}

	/**
	 * Product Tabs Carousel
	 */
	var productTabsCarouselHandler = function ($scope, $) {

		var $selector = $scope.find('.mf-products-tabs-carousel');

		if (!$selector.hasClass('no-infinite')) {
			productTabsCarouselInfinite($selector);
		} else {
			getProductsCarouselHandler($selector);
			productTabsActive($selector);

			$selector.find('.tabs-nav').on('click', 'a', function (e) {
				e.preventDefault();
				getProductsTabsAJAXHandler($(this), $selector, true);
			});
		}

		function productTabsCarouselInfinite($selector) {

			$(window).on('scroll', function () {

				if ($selector.hasClass('no-infinite')) {
					return;
				}

				var offSet = 0;

				if ($selector.is(':in-viewport(' + offSet + ')')) {
					getProductTabsCarouselAjax($selector);
					$selector.addClass('no-infinite');
				}

			}).trigger('scroll');
		}

		function getProductTabsCarouselAjax($selector) {

			if (typeof mf_elementor_data === 'undefined') {
				return;
			}

			var dataSettings = $selector.find('.mf-products-tabs-loading').data('settings'),
				ajax_url = mf_elementor_data.ajax_url.toString().replace('%%endpoint%%', 'mf_elementor_get_elements');


			$.ajax({
				url: ajax_url,
				dataType: 'json',
				method: 'post',
				data: {
					params: JSON.stringify(dataSettings),
					element: 'productsTabsCarousel'
				},
				success: function (response) {
					$selector.html(response.data);
					getProductsCarouselHandler($selector);
					lazyLoadHandler($selector);
					productTabsActive($selector);
					$(document.body).trigger('martfury_get_products_ajax_success');
				}
			});

			$(document.body).on('martfury_get_products_ajax_success', function () {
				$selector.find('.slick-slider').slick('setPosition');
			});
		}
	};

	var productTabsActive = function ($selector) {
		$selector.find('.tabs-nav').on('click', 'a', function (e) {
			e.preventDefault();
			var $this = $(this),
				currentTab = $this.data('href');

			if ($this.hasClass('active')) {
				return;
			}

			$selector.find('.tabs-nav a').removeClass('active');
			$this.addClass('active');
			$selector.find('.tabs-panel').removeClass('active');
			$selector.find('.tabs-' + currentTab).addClass('active');
			$selector.find('.slick-slider').slick('setPosition');
			lazyLoadHandler($selector.find('.tabs-' + currentTab));
		});
	};


	/**
	 * Product Tabs Grid
	 */
	var productTabsGridHandler = function ($scope, $) {
		var $selector = $scope.find('.mf-products-tabs-grid');

		if (!$selector.hasClass('no-infinite')) {
			productTabsGridInfinite($selector);
		} else {
			productTabsActive($selector);
			$selector.find('.tabs-nav').on('click', 'a', function (e) {
				e.preventDefault();
				getProductsTabsAJAXHandler($(this), $selector, false);
			});
		}

		function productTabsGridInfinite($selector) {

			$(window).on('scroll', function () {

				if ($selector.hasClass('no-infinite')) {
					return;
				}

				var offSet = 0;

				if ($selector.is(':in-viewport(' + offSet + ')')) {
					getProductTabsGridAjax($selector);
					$selector.addClass('no-infinite');
				}

			}).trigger('scroll');
		}

		function getProductTabsGridAjax($selector) {

			if (typeof mf_elementor_data === 'undefined') {
				return;
			}

			var dataSettings = $selector.find('.mf-products-tabs-loading').data('settings'),
				ajax_url = mf_elementor_data.ajax_url.toString().replace('%%endpoint%%', 'mf_elementor_get_elements');


			$.ajax({
				url: ajax_url,
				dataType: 'json',
				method: 'post',
				data: {
					params: JSON.stringify(dataSettings),
					element: 'productsTabsGrid'
				},
				success: function (response) {
					$selector.html(response.data);
					lazyLoadHandler($selector);
					productTabsActive($selector);
					$(document.body).trigger('martfury_get_products_ajax_success');
				}
			});
		}


	};


	/**
	 * Get Product AJAX
	 */
	var getProductsTabsAJAXHandler = function ($el, $tabs, carousel) {

		if (typeof mf_elementor_data === 'undefined') {
			return;
		}

		var tab = $el.data('href'),
			$content = $tabs.find('.tabs-' + tab);

		if ($content.hasClass('tab-loaded')) {
			return;
		}

		var data = {},
			elementSettings = $content.data('settings'),
			ajax_url = mf_elementor_data.ajax_url.toString().replace('%%endpoint%%', 'mf_elementor_load_products');


		$.each(elementSettings, function (key, value) {
			data[key] = value;
		});

		$.post(
			ajax_url,
			data,
			function (response) {
				if (!response) {
					return;
				}
				$content.html(response.data);
				if (carousel === true) {
					getProductsCarouselHandler($content.closest('.mf-products-tabs'));
				}
				lazyLoadHandler($content);
				$content.addClass('tab-loaded');
				$(document.body).trigger('martfury_get_products_ajax_success');
			}
		);
	};

	/**
	 * Testimonials Sliders
	 */
	var testimonialSlidesHandler = function ($scope, $) {
		$scope.find('.mf-elementor-testimonial-slides').each(function () {
			var $selector = $(this),
				$arrow_wrapper = $(this).find('.arrow-wrapper'),
				elementSettings = $selector.data('settings');

			var options = {
				rtl: $('body').hasClass('rtl'),
				slidesToShow: 2,
				arrows: true,
				dots: true,
				infinite: 'yes' === elementSettings.infinite,
				prevArrow: '<div class="mf-left-arrow"><i class="icon-chevron-left"></i></div>',
				nextArrow: '<div class="mf-right-arrow"><i class="icon-chevron-right"></i></div>',
				appendArrows: $arrow_wrapper,
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed),
				speed: parseInt(elementSettings.speed),
				responsive: [
					{
						breakpoint: 480,
						settings: {
							slidesToShow: 1,
						}
					}
				]
			};

			$selector.find('.testimonial-list').not('.slick-initialized').slick(options);

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});

		});
	};

	/**
	 * Testimonials Sliders
	 */
	var testimonialSlides2Handler = function ($scope, $) {
		$scope.find('.mf-elementor-testimonial-slides-2').each(function () {
			var $selector = $(this),
				elementSettings = $selector.data('settings');

			var options = {
				rtl: $('body').hasClass('rtl'),
				slidesToShow: 1,
				arrows: false,
				dots: true,
				infinite: 'yes' === elementSettings.infinite,
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed),
				speed: parseInt(elementSettings.speed),
			};

			$selector.find('.testimonial-list').not('.slick-initialized').slick(options);

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});

		});
	};

	/**
	 * Partner Sliders
	 */
	var partnerSlidesHandler = function ($scope, $) {
		$scope.find('.martfury-partner-carousel').each(function () {
			var $selector = $(this),
				elementSettings = $selector.data('settings');

			$selector.find('.list-item').not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: elementSettings.columns,
				infinite: false,
				arrows: false,
				dots: false,
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed),
				responsive: [
					{
						breakpoint: 1200,
						settings: {
							slidesToShow: parseInt(elementSettings.autoplay_speed) > 4 ? 4 : elementSettings.autoplay_speed
						}
					},
					{
						breakpoint: 768,
						settings: {
							slidesToShow: 3
						}
					},
					{
						breakpoint: 480,
						settings: {
							slidesToShow: 2
						}
					}
				]
			});

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});

		});
	};

	var imagesCarouselHandler = function ($scope, $) {
		$scope.find('.mf-images-gallery--slide').each(function () {
			var $selector = $(this),
				elementSettings = $selector.data('settings'),
				slidesToShow = parseInt(elementSettings.slidesToShow),
				slidesToScroll = parseInt(elementSettings.slidesToScroll);

			$selector.find('.images-list').not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: slidesToShow,
				slidesToScroll: slidesToScroll,
				arrows: false,
				dots: 'yes' === elementSettings.dots,
				infinite: 'yes' === elementSettings.infinite,
				prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
				nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed),
				speed: parseInt(elementSettings.speed),
				responsive: [
					{
						breakpoint: 1200,
						settings: {
							slidesToShow: slidesToShow > 4 ? 4 : slidesToShow,
							slidesToScroll: slidesToScroll > 4 ? 4 : slidesToScroll
						}
					},
					{
						breakpoint: 992,
						settings: {
							slidesToShow: slidesToShow > 3 ? 3 : slidesToShow,
							slidesToScroll: slidesToScroll > 3 ? 3 : slidesToScroll
						}
					},
					{
						breakpoint: 767,
						settings: {
							slidesToShow: slidesToShow > 2 ? 2 : slidesToShow,
							slidesToScroll: slidesToScroll > 2 ? 2 : slidesToScroll
						}
					}
				]
			});

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});

		});
	};

	var imageBoxCarouselHandler = function ($scope, $) {
		$scope.find('.martfury-image-box-carousel__wrapper').each(function () {
			var $selector = $(this),
				elementSettings = $selector.data('settings'),
				slidesToShow = parseInt(elementSettings.slidesToShow),
				slidesToScroll = parseInt(elementSettings.slidesToScroll);

			$selector.not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: slidesToShow,
				slidesToScroll: slidesToScroll,
				arrows: true,
				dots: true,
				infinite: 'yes' === elementSettings.infinite,
				prevArrow: $scope.find('.slick-prev-arrow'),
				nextArrow: $scope.find('.slick-next-arrow'),
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed),
				speed: parseInt(elementSettings.speed),
				responsive: [
					{
						breakpoint: 1600,
						settings: {
							slidesToShow: slidesToShow > 6 ? 6 : slidesToShow,
							slidesToScroll: slidesToScroll > 6 ? 6 : slidesToScroll
						}
					},
					{
						breakpoint: 1366,
						settings: {
							slidesToShow: slidesToShow > 5 ? 5 : slidesToShow,
							slidesToScroll: slidesToScroll > 5 ? 5 : slidesToScroll
						}
					},
					{
						breakpoint: 1200,
						settings: {
							slidesToShow: slidesToShow > 4 ? 4 : slidesToShow,
							slidesToScroll: slidesToScroll > 4 ? 4 : slidesToScroll
						}
					},
					{
						breakpoint: 992,
						settings: {
							slidesToShow: slidesToShow > 3 ? 3 : slidesToShow,
							slidesToScroll: slidesToScroll > 3 ? 3 : slidesToScroll
						}
					},
					{
						breakpoint: 767,
						settings: {
							slidesToShow: slidesToShow > 2 ? 2 : slidesToShow,
							slidesToScroll: slidesToScroll > 2 ? 2 : slidesToScroll
						}
					}
				]
			});

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});

		});
	};

	/**
	 * Testimonials Sliders
	 */
	var journeyHandler = function ($scope, $) {
		$scope.find('.martfury-journey-els').each(function () {
			var $el = $(this),
				$tabs = $el.find('ul li a'),
				$first = $tabs.filter(':first'),
				$content = $el.find('.journey-wrapper'),
				width = $el.find('ul').width(),
				elementSettings = $el.data('settings'),
				num = parseInt(elementSettings.number),
				space, pos, val;

			if (num == 1) {
				space = 0;
			} else {
				space = (width - 40) / (num - 1);
			}

			for (var i = 1; i <= num; i++) {
				var $this = $('.journey-tab-' + i);
				if ($('body').hasClass('rtl')) {
					if ($this.hasClass('reverse')) {
						pos = 'left';
						val = (i - num) * space * -1;
					} else {
						pos = 'right';
						val = (i - 1) * space;
					}
				} else {
					if ($this.hasClass('reverse')) {
						pos = 'right';
						val = (i - num) * space * -1;
					} else {
						pos = 'left';
						val = (i - 1) * space;
					}
				}

				$this.css(pos, val);
			}

			$first.addClass('active');
			$content.filter(':first').addClass('active');

			$tabs.on('click', function (e) {
				e.preventDefault();

				var $this = $(this),
					tab_id = $this.attr('data-tab');

				if ($this.hasClass('active')) {
					return;
				}

				$tabs.removeClass('active');
				$content.removeClass('active');

				$this.addClass('active');
				$('#' + tab_id).addClass('active');
			});
		});
	};

	var counterHandler = function ($scope, $) {
		$scope.find('.martfury-counter-els').each(function () {
			var $selector = $(this);
			$selector.find('.counter-value').counterUp();

		});
	};

	/**
	 * Product Of Category
	 */

	var productsOfCategoryHandler = function ($scope, $) {
		var $selector = $scope.find('.mf-products-of-category'),
			elementSettings = $selector.data('settings');
		if (!$selector.hasClass('no-infinite')) {
			productsOfCategoryAJAX($selector, elementSettings);
		} else {
			bannersCarousel($selector, elementSettings);

		}

		function productsOfCategoryAJAX($selector, elementSettings) {

			$(window).on('scroll', function () {

				if ($selector.hasClass('no-infinite')) {
					return;
				}

				var offSet = 0;

				if ($selector.is(':in-viewport(' + offSet + ')')) {
					productsOfCatAjax($selector, elementSettings);
					$selector.addClass('no-infinite');
				}

			}).trigger('scroll');
		}

		function productsOfCatAjax($selector, elementSettings) {

			if (typeof mf_elementor_data === 'undefined') {
				return;
			}

			var dataSettings = $selector.find('.mf-products-of-category-loading').data('settings'),
				ajax_url = mf_elementor_data.ajax_url.toString().replace('%%endpoint%%', 'mf_elementor_get_elements');


			$.ajax({
				url: ajax_url,
				dataType: 'json',
				method: 'post',
				data: {
					params: JSON.stringify(dataSettings),
					element: 'productsOfCat'
				},
				success: function (response) {
					$selector.html(response.data);
					bannersCarousel($selector, elementSettings);
					lazyLoadHandler($selector);
					$(document.body).trigger('martfury_get_products_ajax_success');
				}
			});
		}

		/**
		 * Catagory Banners Carousel
		 */
		function bannersCarousel($selector, elementSettings) {
			$selector.find('.images-list').not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: 1,
				arrows: 'yes' === elementSettings.arrows,
				prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
				nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
				infinite: 'yes' === elementSettings.infinite,
				autoplay: 'yes' === elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplay_speed),
				dots: 'yes' === elementSettings.dots,
				speed: parseInt(elementSettings.speed),
			});

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});
		}

	};

	/**
	 * Product Of Category 2
	 */
	var productOfCategory2Handler = function ($scope, $) {
		var $selector = $scope.find('.mf-products-of-category-2');

		if (!$selector.hasClass('no-infinite')) {
			productsOfCategory2Infinite($selector);
		} else {
			bannerCarousel($selector);
			productCarousel($selector);
			productTabsActive($selector);
		}

		function productsOfCategory2Infinite($selector) {

			$(window).on('scroll', function () {

				if ($selector.hasClass('no-infinite')) {
					return;
				}

				var offSet = 0;

				if ($selector.is(':in-viewport(' + offSet + ')')) {
					productsOfCat2Ajax($selector);
					$selector.addClass('no-infinite');
				}

			}).trigger('scroll');
		}

		function productsOfCat2Ajax($selector) {

			if (typeof mf_elementor_data === 'undefined') {
				return;
			}

			var dataSettings = $selector.find('.mf-products-of-category-loading').data('settings'),
				ajax_url = mf_elementor_data.ajax_url.toString().replace('%%endpoint%%', 'mf_elementor_get_elements');


			$.ajax({
				url: ajax_url,
				dataType: 'json',
				method: 'post',
				data: {
					params: JSON.stringify(dataSettings),
					element: 'productsOfCat2'
				},
				success: function (response) {
					$selector.html(response.data);
					bannerCarousel($selector);
					productCarousel($selector);
					lazyLoadHandler($selector);
					productTabsActive($selector);
					$(document.body).trigger('martfury_get_products_ajax_success');
				}
			});
		}

		function bannerCarousel($selector) {
			var $banners = $selector.find('.images-slider'),
				bannerSettings = $banners.data('settings');

			$banners.find('.images-list').not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: 1,
				arrows: 'yes' === bannerSettings.arrows,
				dots: false,
				infinite: 'yes' === bannerSettings.infinite,
				prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
				nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
				autoplay: 'yes' === bannerSettings.autoplay,
				autoplaySpeed: parseInt(bannerSettings.autoplay_speed),
				speed: parseInt(bannerSettings.speed),
			});

			$banners.on('afterChange', function () {
				lazyLoadHandler($banners);
			});
		}

		function productCarousel($selector) {
			var $products = $selector.find('.mf-products-tabs'),
				productSettings = $products.data('settings'),
				slidesToShow = parseInt(productSettings.slidesToShow),
				slidesToScroll = parseInt(productSettings.slidesToScroll);

			$products.find('ul.products').not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: slidesToShow,
				slidesToScroll: slidesToScroll,
				arrows: true,
				dots: true,
				infinite: 'yes' === productSettings.infinite,
				prevArrow: '<span class="icon-chevron-left slick-prev-arrow"></span>',
				nextArrow: '<span class="icon-chevron-right slick-next-arrow"></span>',
				autoplay: 'yes' === productSettings.autoplay,
				autoplaySpeed: parseInt(productSettings.autoplay_speed),
				speed: parseInt(productSettings.speed),
				responsive: [
					{
						breakpoint: 1200,
						settings: {
							slidesToShow: slidesToShow > 4 ? 4 : slidesToShow,
							slidesToScroll: slidesToScroll > 4 ? 4 : slidesToScroll
						}
					},
					{
						breakpoint: 992,
						settings: {
							slidesToShow: slidesToShow > 3 ? 3 : slidesToShow,
							slidesToScroll: slidesToScroll > 3 ? 3 : slidesToScroll
						}
					},
					{
						breakpoint: 767,
						settings: {
							slidesToShow: 2,
							slidesToScroll: 2
						}
					}
				]
			});

			$products.on('afterChange', function () {
				lazyLoadHandler($products);
			});

			$products.find('.tabs-nav').on('click', 'a', function (e) {
				e.preventDefault();
				getProductsTabsAJAXHandler($(this), $products, true);
			});
		}

	};

	/**
	 * Slides Widget
	 * @param $scope
	 * @param $
	 */
	var slideCarouselHandler = function ($scope, $) {
		$scope.find('.mf-slides-wrapper').each(function () {
			var $selector = $(this).find('.mf-slides'),
				elementSettings = $selector.data('slider_options'),
				$arrow_wrapper = $(this).find('.arrows-inner'),
				slidesToShow = parseInt(elementSettings.slidesToShow);

			$selector.not('.slick-initialized').slick({
				rtl: $('body').hasClass('rtl'),
				slidesToShow: slidesToShow,
				arrows: elementSettings.arrows,
				appendArrows: $arrow_wrapper,
				dots: elementSettings.dots,
				infinite: elementSettings.infinite,
				prevArrow: '<span class="slick-prev-arrow"><i class="icon-chevron-left"></i></span>',
				nextArrow: '<span class="slick-next-arrow"><i class="icon-chevron-right"></i></span>',
				autoplay: elementSettings.autoplay,
				autoplaySpeed: parseInt(elementSettings.autoplaySpeed),
				speed: parseInt(elementSettings.speed),
				pauseOnHover: elementSettings.pauseOnHover,
				fade: elementSettings.fade,
				cssEase: elementSettings.fade ? 'linear' : '',
				responsive: []
			});

			$selector.imagesLoaded(function () {
				$selector.closest('.mf-slides-wrapper').removeClass('loading');
			});

			var animation = $selector.data('animation');

			if (animation) {
				$selector
					.on('beforeChange', function () {
						var $sliderContent = $selector.find('.mf-slide-content'),
							$sliderPriceBox = $selector.find('.mf-slide-price-box');

						$sliderContent.removeClass('animated' + ' ' + animation).hide();

						$sliderPriceBox.removeClass('animated zoomIn').hide();
					})
					.on('afterChange', function (event, slick, currentSlide) {
						var $currentSlide = $(slick.$slides.get(currentSlide)).find('.mf-slide-content'),
							$currentPriceBox = $(slick.$slides.get(currentSlide)).find('.mf-slide-price-box');

						$currentSlide.show().addClass('animated' + ' ' + animation);

						$currentPriceBox.show().addClass('animated zoomIn');
					});
			}

			$selector.on('afterChange', function () {
				lazyLoadHandler($selector);
			});
		});
	};

	/**
	 * Elementor JS Hooks
	 */
	$(window).on("elementor/frontend/init", function () {

		// Product Deals Carousel
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-product-deals-carousel.default",
			productDealsCarouselHandler
		);

		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-product-deals-carousel.default",
			countDownHandler
		);

		//Product Deals Grid

		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-product-deals-grid.default",
			countDownHandler
		);

		// Product Deals Carousel 2
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-product-deals-carousel-2.default",
			productDealsCarousel2Handler
		);

		// Products of Category
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-products-of-category.default",
			productsOfCategoryHandler
		);


		// Category Tabs
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-category-tabs.default",
			categoryTabsCarouselHandler
		);

		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-category-tabs.default",
			categoryTabsHandler
		);

		// Products Tabs Carousel

		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-product-tabs-carousel.default",
			productTabsCarouselHandler
		);

		// Products Tabs Grid
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-product-tabs-grid.default",
			productTabsGridHandler
		);

		// Products List Carousel
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-products-list-carousel.default",
			productsListCarouselHandler
		);

		// Products Carousel
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-products-carousel.default",
			productsCarouselHandler
		);

		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-recently-viewed-products-carousel.default",
			recentlyViewedProductsCarouselHandler
		);

		// Testimonial
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-testimonial-slides.default",
			testimonialSlidesHandler
		);

		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-testimonial-slides-2.default",
			testimonialSlides2Handler
		);

		// Counter
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-counter.default",
			counterHandler
		);

		// Journey
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-journey.default",
			journeyHandler
		);

		// Product Grid
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-products-grid.default",
			productsGridHandler
		);

		// Product Brand
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-products-brands.default",
			productBrandsHandler
		);

		// Product Brand Images Carousel
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/mf-brand-images-carousel.default",
			brandsImagesCarouselHandler
		);

		// All widgets ready
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/widget",
			lazyLoadHandler
		);

		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-countdown.default",
			countDownHandler
		);

		// Product Of Category 2
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-products-of-category-2.default",
			productOfCategory2Handler
		);

		// Partner
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-partner.default",
			partnerSlidesHandler
		);

		// Image Gallery - Slide
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-images-carousel.default",
			imagesCarouselHandler
		);

		// Image Box Carousel
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-image-box-carousel.default",
			imageBoxCarouselHandler
		);

		// Slides
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/martfury-slides.default",
			slideCarouselHandler
		);
	});
})
(jQuery);