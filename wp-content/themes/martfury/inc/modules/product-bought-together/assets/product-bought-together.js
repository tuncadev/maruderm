(function ($) {
    'use strict';

	function navigationProduct () {
		$( '#mf-product-fbt .products-list li' ).on( 'click', 'a', function (e) {
			e.preventDefault();

			if( $(this).closest( '.products-list__item' ).hasClass( 'product-current') ) {
				return;
			}

			var $idProduct = $(this).attr( 'data-id' );

			$(this).closest( '.products-list__item' ).toggleClass( 'uncheck' );

			if ( $(this).closest( '.products-list__item' ).hasClass( 'uncheck' ) ) {
				$(this).closest( 'li.products-list__item' ).addClass( 'un-active' );
			} else {
				$(this).closest( 'li.products-list__item' ).removeClass( 'un-active' );
			}

			 $( '#mf-product-fbt ul.products' ).find( '.pbt-product-' + $idProduct + ' .product-id' ).trigger( 'click' );

			return false;
		});
	}

	function selectProduct () {
		$( '#mf-product-fbt .product-select' ).on( 'click', 'a', function (e) {
			e.preventDefault();

			var $this				 = $(this).closest( '#mf-product-fbt' ),
				subTotalData      	 = $this.find('#martfury-data_subtotal'),
				subTotal             = parseFloat($this.find('#martfury-data_subtotal').attr('data-price')),
				totalPriceData       = $this.find('#mf-data_price'),
				totalPrice           = parseFloat($this.find('#mf-data_price').attr('data-price')),
				$discountAll         = parseFloat($this.find('#martfury-data_discount-all').data('discount')),
				$quantityDiscountAll = parseFloat($this.find('#martfury-data_quantity-discount-all').data('quantity')),
				$subTotal            = $this.find('.martfury-pbt-subtotal .woocommerce-Price-amount'),
				$savePrice           = $this.find('.martfury-pbt-save-price .woocommerce-Price-amount'),
				$percent             = $this.find('.martfury-pbt-save-price .percent'),
				$priceAt             = $this.find('.mf-total-price .woocommerce-Price-amount'),
				$button              = $this.find('.mf_add_to_cart_button'),
				currentPrice 		 = $(this).closest( '.product-select' ).find( '.s-price' ).attr( 'data-price' ),
				$productsVariation   = $this.find('li.product[data-type="variable"]'),
				$martfury_variation_id  = $this.find('input[name="martfury_variation_id"]'),
				$product_ids 		 = '',
				$productVariation_ids= 0,
				$i 					 = 0,
				$numberProduct 		 = [];

			if( $(this).closest( '.product-select' ).hasClass( 'product-current' ) ) {
				return false;
			}

			$(this).closest( '.product-select' ).toggleClass( 'uncheck' );

			$this.find( '.product-select' ).each(function () {
				if ( ! $(this).hasClass( 'uncheck' ) ) {
					if( $(this).hasClass( 'product-current' ) ) {
						$product_ids = $(this).find('.product-id').attr('data-id');
					} else {
						$product_ids += ',' + $(this).find('.product-id').attr('data-id');
					}

					if( parseFloat( $(this).find('.product-id').attr('data-id') ) !== 0 && parseFloat( $(this).find('.s-price').attr('data-price') ) !== 0 ) {
						$numberProduct[$i] = $(this).find('.product-id').attr('data-id');
					}

					$i++;
				}
			});

			$numberProduct = jQuery.grep( $numberProduct, function(n){ return (n); });

			$productsVariation.find( '.product-select' ).each(function () {
				if ( ! $(this).hasClass( 'uncheck' ) ) {
					$productVariation_ids += $(this).find('.product-id').attr('data-id') + ',';
				}

				if( ! $productVariation_ids || $productVariation_ids == 0 ) {
					$productVariation_ids = 0;
				}
			});

			$martfury_variation_id.attr( 'value', $productVariation_ids );
			$button.attr( 'value', $product_ids );

			if ( $(this).closest( '.product-select' ).hasClass( 'uncheck' ) ) {
				$(this).closest( 'li.product' ).addClass( 'un-active' );
				subTotal -= parseFloat(currentPrice);
			} else {
				$(this).closest( 'li.product' ).removeClass( 'un-active' );
				subTotal += parseFloat(currentPrice);
			}

			var savePrice = ( subTotal / 100 ) * $discountAll;

			if( $discountAll || $discountAll !== 0 ) {
				if( $quantityDiscountAll <= $numberProduct.length ) {
					subTotalData.attr( 'data-price', subTotal );
					$subTotal.html(formatNumber(subTotal));
					$savePrice.html(formatNumber(savePrice));
					$percent.text($discountAll);
					$priceAt.html(formatNumber(subTotal - savePrice));
					totalPriceData.attr( 'data-price', subTotal - savePrice );
					$(this).closest( 'ul.products' ).find( '.price-new' ).removeClass( 'hidden' );
					$(this).closest( 'ul.products' ).find( '.price-ori' ).addClass( 'hidden' );
					$(this).closest( 'ul.products' ).find( '.product-variation-price' ).addClass( 'active' );
					$(this).closest( 'ul.products' ).find( '.product-variation-price .price' ).addClass( 'hidden' );
					$(this).closest( 'ul.products' ).find( '.product-variation-price .price-new' ).removeClass( 'hidden' );
				} else {
					subTotalData.attr( 'data-price', subTotal );
					$subTotal.html(formatNumber(subTotal));
					$savePrice.html(formatNumber(0));
					$percent.text(0);
					$priceAt.html(formatNumber(subTotal));
					totalPriceData.attr( 'data-price', subTotal );
					$(this).closest( 'ul.products' ).find( '.price-new' ).addClass( 'hidden' );
					$(this).closest( 'ul.products' ).find( '.price-ori' ).removeClass( 'hidden' );
					$(this).closest( 'ul.products' ).find( '.product-variation-price' ).removeClass( 'active' );
					$(this).closest( 'ul.products' ).find( '.product-variation-price .price' ).removeClass( 'hidden' );
					$(this).closest( 'ul.products' ).find( '.product-variation-price .price-new' ).addClass( 'hidden' );
				}
			} else {
				$priceAt.html(formatNumber(totalPrice));
				totalPriceData.attr( 'data-price', totalPrice );
			}

			check_ready( $this );

			check_button();
		});
	}

	$(document).on( 'found_variation', function(e, t) {
		var $wrap          = $(e['target']).closest('#mf-product-fbt'),
			$product       = $(e['target']).closest('li.product'),
			$productPrice  = $(e['target']).closest('li.product').find( '.s-price' ),
			$productAttrs  = $(e['target']).closest('li.product').find( '.s-attrs' ),
			$productID     = $(e['target']).closest('li.product').find( '.product-id' ),
			$button        = $wrap.find('.mf_add_to_cart_button'),
			$display_price = t['display_price'],
			$stock		   = t['is_in_stock'],
			attrs          = {};

		if ( $product.length ) {
			if( $button.val() == 0 ) {
				$button.attr( 'value', $productID );
			}

			if( ! $stock ) {
				$display_price = 0;
				$product.addClass( 'out-of-stock' );
			} else {
				$product.removeClass( 'out-of-stock' );
			}

			if ( $product.attr( 'data-type' ) == 'variable' ) {
				$productPrice.attr('data-price', $display_price);
				$wrap.find('.products-list .pbt-product-' + $product.attr( 'data-id' ) ).removeClass( 'no-choose' );
		  	}

			$productID.attr('data-id', t['variation_id']);
			if ( $product.find( '.product-select' ).hasClass('product-current') ) {
				$wrap.find('.martfury_variation_id').attr('value', t['variation_id']);
			}

			if ( t['image']['url'] ) {
				// change image
				$product.find('.thumbnail .thumb-ori').css( 'opacity', '0' );
				$product.find('.thumbnail .thumb-new').html('<img src="' + t['image']['url'] + '" srcset="' + t['image']['url'] + '"/>').css( 'opacity', '1' );
			}

			// change attributes
			if (t['is_purchasable'] && t['is_in_stock']) {
				$product.find('select[name^="attribute_"]').each(function() {
					var attr_name = $(this).attr('name');
					attrs[attr_name] = $(this).val();
				});

				$productAttrs.attr('data-attrs', JSON.stringify(attrs));
			} else {
				$productAttrs.attr('data-attrs', '');
			}
		}

		variationProduct( $product, $productID.attr('data-id'), $stock );
	});

	$(document).on('reset_data', function(e) {
		var $wrap     	      = $(e['target']).closest('#mf-product-fbt'),
			$product          = $(e['target']).closest('li.product'),
			$productPrice     = $(e['target']).closest('li.product').find( '.s-price' ),
			$productAttrs  	  = $(e['target']).closest('li.product').find( '.s-attrs' ),
			$productPriceData = parseFloat($(e['target']).closest('li.product').find( '.s-price' ).attr('data-price')),
			$productID        = $(e['target']).closest('li.product').find( '.product-id' ),
			subTotal          = parseFloat($wrap.find('#martfury-data_subtotal').attr('data-price')),
			subTotalData      = $wrap.find('#martfury-data_subtotal');

		if ($product.length) {
			$productID.attr( 'data-id', 0 );
			$productAttrs.attr('data-attrs', '');
			$product.removeClass( 'out-of-stock' );

			// reset thumb
			$product.find('.thumbnail .thumb-new').css( 'opacity', '0' );
			$product.find('.thumbnail .thumb-ori').css( 'opacity', '1' );

		  	// reset price
			if ( $product.attr( 'data-type' ) == 'variable' ) {
				$productPrice.attr('data-price', 0);
				$wrap.find('.products-list .pbt-product-' + $product.attr( 'data-id' ) ).addClass( 'no-choose' );
			}

			if ( $product.find( '.product-select' ).hasClass('product-current') ) {
				$wrap.find('.martfury_variation_id').attr( 'value', 0 );
			}

			subTotalData.attr('data-price', subTotal - $productPriceData );
		}

		variationProduct( $product, $productID.attr('data-id') );
	});

	function variationProduct ( $this, $productID = 0 ) {
		if( $this.attr( 'data-type' ) !== 'variable' ) {
			return;
		}

		if( $this.find( '.product-select' ).hasClass( 'unckeck' ) ) {
			return;
		}

		var $pbtProducts            = $this.closest('#mf-product-fbt'),
			$products		        = $pbtProducts.find('li.product'),
			$productsVariable       = $pbtProducts.find('li.product[data-type="variable"]'),
			$subTotal               = $pbtProducts.find('.martfury-pbt-subtotal .woocommerce-Price-amount'),
			$priceAt                = $pbtProducts.find('.mf-total-price .woocommerce-Price-amount'),
			$discountAll            = parseFloat( $pbtProducts.find('#martfury-data_discount-all').data('discount')),
			$discountHtml           = $pbtProducts.find('.martfury-pbt-save-price .woocommerce-Price-amount'),
			$quantityDiscountAll    = parseFloat($pbtProducts.find('#martfury-data_quantity-discount-all').data('quantity')),
			$martfury_product_id       = parseFloat( $pbtProducts.find('input[name="martfury_product_id"]').val()),
			$martfury_variation_id     = $pbtProducts.find('input[name="martfury_variation_id"]'),
			$martfury_variation_id_val = $martfury_variation_id.val(),
			$martfury_variation_attrs  = $pbtProducts.find('input[name="martfury_variation_attrs"]'),
			$button                 = $pbtProducts.find('.mf_add_to_cart_button'),
			$percent                = $pbtProducts.find('.martfury-pbt-save-price .percent'),
			subTotal                = parseFloat( $pbtProducts.find('#martfury-data_subtotal').attr('data-price') ),
			subTotalData            = $pbtProducts.find('#martfury-data_subtotal'),
			totalPriceData          = $pbtProducts.find('#mf-data_price'),
			$variation_attrs 		= {},
			$product_ids 		    = '',
			$martfury_variation_ids 	= '',
			$savePrice				= parseFloat( $pbtProducts.find('#martfury-data_save-price').attr('data-price') ),
			$savePriceData			= $pbtProducts.find('#martfury-data_save-price'),
			$total 					= 0,
			$i 						= 0,
			$numberProduct 		    = [];

		$pbtProducts.find( '.product-select' ).each(function () {
			if ( ! $(this).hasClass( 'uncheck' ) ) {
				if( $(this).hasClass( 'product-current' ) ) {
					$product_ids = $(this).find('.product-id').attr('data-id');
				} else {
					$product_ids += ',' + $(this).find('.product-id').attr('data-id');
				}

				if( parseFloat( $(this).find('.product-id').attr('data-id') ) !== 0 && parseFloat( $(this).find('.s-price').attr('data-price') ) !== 0 ) {
					$numberProduct[$i] = $(this).find('.product-id').attr('data-id');
				}

				$i++;
			}
		});

		$numberProduct = jQuery.grep( $numberProduct, function(n){ return (n); });

		$button.attr( 'value', $product_ids );

		if( $martfury_variation_id_val == 0 ) {
			$martfury_variation_id.attr( 'value', $productID );

			$variation_attrs[$productID] = $this.find('.s-attrs').attr('data-attrs');
			$martfury_variation_attrs.attr( 'value', JSON.stringify($variation_attrs) );
		} else {
			$productsVariable.find( '.product-select' ).each( function () {
				if ( ! $(this).hasClass( 'uncheck' ) ) {
					var $pid 	= $(this).find('.product-id').attr('data-id'),
						$pattrs = $(this).find('.s-attrs').attr('data-attrs');

					$martfury_variation_ids += $pid + ',';
					$variation_attrs[$pid] = $pattrs;
				}
			});

			$martfury_variation_id.attr( 'value', $martfury_variation_ids );
			$martfury_variation_attrs.attr( 'value', JSON.stringify($variation_attrs) );
		}

		$products.find( '.product-select' ).each( function () {
			if ( ! $(this).hasClass( 'uncheck' ) ) {
				var $pPrice = $(this).find('.s-price').attr('data-price');

				$total += parseFloat($pPrice);
			}
		});

		subTotal = $total;

		if( $discountAll !== 0 && $quantityDiscountAll <= $numberProduct.length ) {
			$savePrice = ( subTotal / 100 ) * $discountAll;
			$percent.text($discountAll);

			if( ! $this.hasClass( 'product-primary' ) ) {
				$this.closest( 'ul.products' ).find( '.product-primary .price-ori' ).addClass( 'hidden' );
				$this.closest( 'ul.products' ).find( '.product-primary .price-new' ).removeClass( 'hidden' );
			}
		} else {
			$savePrice = 0;
			$percent.text(0);

			if( ! $this.hasClass( 'product-primary' ) ) {
				$this.closest( 'ul.products' ).find( '.product-primary .price-ori' ).removeClass( 'hidden' );
				$this.closest( 'ul.products' ).find( '.product-primary .price-new' ).addClass( 'hidden' );
			}
		}

		if( $martfury_product_id == 0 ) {
			$savePrice = 0;

			if( $martfury_variation_id !== 0 && $quantityDiscountAll <= $numberProduct.length ) {
				$savePrice = ( subTotal / 100 ) * $discountAll;
				$percent.text($discountAll);

				$this.closest( 'ul.products' ).find( '.product-variation-price' ).addClass( 'active' );
				$this.closest( 'ul.products' ).find( '.product-variation-price .price' ).addClass( 'hidden' );
				$this.closest( 'ul.products' ).find( '.product-variation-price .price-new' ).removeClass( 'hidden' );
			} else {
				$percent.text(0);
			}
		}

		$savePriceData.attr( 'data-price', $savePrice );
		$discountHtml.html(formatNumber($savePrice));

		subTotalData.attr( 'data-price', subTotal );
		$subTotal.html(formatNumber(subTotal));
		totalPriceData.attr( 'data-price', subTotal - $savePrice );
		$priceAt.html(formatNumber(subTotal - $savePrice ));
		$pbtProducts.find('#mf-data_price').attr( 'data-price', subTotal - $savePrice );

		check_button();
	}

    // Add to cart ajax
    function fbtAddToCartAjax() {
        if (! $('body').hasClass('single-product')) {
			return;
		}

        var $fbtProducts = $('#mf-product-fbt');

        if ( $fbtProducts.length <= 0 ) {
            return;
        }

        $fbtProducts.on('click', '.mf_add_to_cart_button.ajax_add_to_cart', function (e) {
            e.preventDefault();

            var $singleBtn = $(this);

            if ( $singleBtn.data('requestRunning') || $singleBtn.hasClass( 'disabled' ) ) {
				return;
			}

            $singleBtn.data('requestRunning', true);
            $singleBtn.addClass('loading');

            var pro_title = '',
                i = 0,
                $fbtProducts = $('#mf-product-fbt');

            $fbtProducts.find('.products-list > li.products-list__item').each(function () {
                if ( ! $(this).hasClass('uncheck') && ! $(this).hasClass( 'no-choose' ) ) {
					if( $(this).find('a').data('title') !== 'undefined' ) {
						if (i > 0) {
							pro_title += ',';
						}
						pro_title += ' ' + $(this).find('a').data('title');

						i++;
					}
                }
            });

            var $cartForm = $singleBtn.closest('.fbt-cart'),
				formData = $cartForm.serializeArray(),
				formAction = $cartForm.attr('action');

			if ($singleBtn.val() != '') {
				formData.push({name: $singleBtn.attr('name'), value: $singleBtn.val()});
			}

			$(document.body).trigger('adding_to_cart', [$singleBtn, formData]);

            $.ajax({
				url: formAction,
				method: 'post',
				data: formData,
				error: function (response) {
					window.location = formAction;
				},
                success: function (response) {
                    if (typeof wc_add_to_cart_params !== 'undefined') {
                        if (wc_add_to_cart_params.cart_redirect_after_add === 'yes') {
                            window.location = wc_add_to_cart_params.cart_url;
                            return;
                        }
                    }

                    $(document.body).trigger('wc_fragment_refresh');

                    addedToCartNotice(pro_title, false, 'success', true);
                    $singleBtn.removeClass('loading');
					$singleBtn.data('requestRunning', false);
                }
            });

        });

    };

    function check_ready( $wrap = $( '#mf-product-fbt' ) ) {
		var $products    	= $wrap.find( 'ul.products' ),
			$alert          = $wrap.find( '.martfury-pbt-alert' ),
			$selection_name = '',
			$is_selection   = false,
			$vatiable_count = 0;

		$products.find( 'li.product' ).each(function() {
			var $this = $(this),
				$type = $this.attr( 'data-type' ),
				$ptype = $this.attr( 'data-ptype' );

			if ( ! $this.find( '.product-select' ).hasClass( 'uncheck' ) && $type == 'variable' && $ptype !== 'variation' ) {
				$is_selection = true;
				$vatiable_count ++;
				$selection_name = $this.attr( 'data-name' );
			}
		});

		if ( $is_selection ) {
			if( $vatiable_count == 1 ) {
				$alert.html( martfuryPbt.pbt_alert.replace( '[name]', '<strong>' + $selection_name + '</strong>') ).slideDown();
			} else {
				$alert.html( martfuryPbt.pbt_alert_multiple ).slideDown();
			}
			$(document).trigger( 'martfury_pbt_check_ready', [false, $is_selection, $wrap] );
		} else {
			$alert.html('').slideUp();
			$(document).trigger( 'martfury_pbt_check_ready', [true, $is_selection, $wrap] );
		}

		check_button();
	}

    function check_button() {
		var $pbtProducts = $('#mf-product-fbt'),
			$total = parseFloat( $pbtProducts.find( '#mf-data_price' ).attr( 'data-price' ) ),
			$pID = parseFloat( $pbtProducts.find( '.martfury_product_id' ).val() ),
			$pVID = parseFloat( $pbtProducts.find( '.martfury_variation_id' ).val() ),
			$button = $pbtProducts.find( '.mf_add_to_cart_button' );

		if( parseFloat( $pbtProducts.find( '.product-select.product-current .s-price' ).attr( 'data-price' ) ) == 0 ) {
			$button.addClass( 'disabled' );
		} else {
			if( $total == 0 || ( $pID == 0 && $pVID == 0 ) ) {
				$button.addClass( 'disabled' );
			} else {
				$button.removeClass( 'disabled' );
			}
		}
	}

    function productVariationChange() {
        $('.mf-product-fbt .variations_form').on( 'show_variation', function () {
            var $container          = $(this).closest( '.product-content' ).find( 'div.price' ),
                $price_new          = $(this).find( '.woocommerce-variation-price' ).html();

			if( $price_new ) {
				if( $container.hasClass( 'hidden' ) ) {
					$container.parent().find( '.product-variation-price' ).remove();
				} else {
					$container.addClass( 'hidden' );
				}

				if( $container.parent().find( '.product-variation-price' ).length ) {
					$container.after( $price_new );
				} else {
					$container.after( '<div class="product-variation-price">' + $price_new + '</div>' );
				}

				$container.parent().find( '.product-variation-price' ).addClass( 'active' );
			}
			check_button();
        });

        $('.mf-product-fbt .variations_form').on( 'hide_variation', function () {
            var $container = $(this).closest( '.product-content' ).find( 'div.price' );

            if( $container.hasClass( 'hidden' ) ) {
				$container.removeClass( 'hidden' );
				$container.parent().find( '.product-variation-price' ).remove();
			}

			check_button();
        });
    }

	function formatNumber(number) {
		var n = number,
            currency = martfuryPbt.currency_symbol,
            thousand = martfuryPbt.thousand_sep,
            decimal = martfuryPbt.decimal_sep,
            price_decimals = martfuryPbt.price_decimals,
            currency_pos = martfuryPbt.currency_pos;

		if (parseInt(price_decimals) > 0) {
			number = number.toFixed(price_decimals) + '';
			var x = number.split('.');
			var x1 = x[0],
				x2 = x.length > 1 ? decimal + x[1] : '';
			var rgx = /(\d+)(\d{3})/;
			while (rgx.test(x1)) {
				x1 = x1.replace(rgx, '$1' + thousand + '$2');
			}

			n = x1 + x2
		}


		switch (currency_pos) {
			case 'left' :
				return currency + n;
				break;
			case 'right' :
				return n + currency;
				break;
			case 'left_space' :
				return currency + ' ' + n;
				break;
			case 'right_space' :
				return n + ' ' + currency;
				break;
		}
	}

    // Add to wishlist  ajax
    function fbtAddToWishlistAjax() {
        var $fbtProducts = $('#mf-product-fbt');

        var product_ids = getProductIds();

        if (product_ids.length == 0) {
            $fbtProducts.find('.btn-view-to-wishlist').addClass('showed');
            $fbtProducts.find('.btn-add-to-wishlist').addClass('hided');
        }

        $fbtProducts.on('click', '.btn-add-to-wishlist', function (e) {
            e.preventDefault();

            var $singleBtn = $(this);
            product_ids = getProductIds();

            if (product_ids.length == 0) {
                return;
            }

            var pro_title = '',
                index = 0;
            $fbtProducts.find('.products-list li').each(function () {
                if (!$(this).hasClass('uncheck')) {
                    if (index > 0) {
                        pro_title += ',';
                    }
                    pro_title += ' ' + $(this).find('a').data('title');

                    if( ! $(this).find( '.wcboost-wishlist-button' ).hasClass( 'added' ) ) {
                        wishlistCallBack(product_ids[index]);
                        $singleBtn.addClass('loading');

                        $(document.body).on('added_to_wishlist', function () {
                            $fbtProducts.find('.btn-view-to-wishlist').addClass('showed');
                            $fbtProducts.find('.btn-add-to-wishlist').addClass('hided');
                            addedToWishlistNotice('', pro_title, false, 'success', true);
                            $singleBtn.removeClass('loading');
                        });
                    }

                    index++;
                }
            });
        });
    };

	function getProductIds() {
		var $fbtProducts = $('#mf-product-fbt'),
            product_ids = [];

		$fbtProducts.find('li.product').each(function () {
			if (!$(this).hasClass('un-active') && !$(this).hasClass('product-buttons') && ! $(this).find( '.wcboost-wishlist-button' ).hasClass( 'added' ) && ! $(this).find('.yith-wcwl-add-to-wishlist').hasClass('exists') ) {
				if (product_ids.indexOf($(this).data('id')) == -1) {
					product_ids.push($(this).data('id'));
				}
			}

		});

		return product_ids;
	}

	function wishlistCallBack(id) {
		var $fbtProducts = $('#mf-product-fbt'),
            $product = $fbtProducts.find('.add-to-wishlist-' + id),
			$productWCboost = $fbtProducts.find( '.wcboost-wishlist-button[data-product_id="' + id + '"]');

		$productWCboost.trigger('click');
		$product.find('.yith-wcwl-add-button .add_to_wishlist').trigger('click');
	}

    function addedToWishlistNotice($message, $content, single, className, multiple) {
        if (typeof martfuryData.added_to_wishlist_notice === 'undefined' || !$.fn.notify) {
            return;
        }

        if (multiple) {
            $content += ' ' + martfuryData.added_to_wishlist_notice.added_to_wishlist_texts;
        } else {
            $content += ' ' + martfuryData.added_to_wishlist_notice.added_to_wishlist_text;
        }

        $message += '<a href="' + martfuryData.added_to_wishlist_notice.wishlist_view_link + '" class="btn-button">' + martfuryData.added_to_wishlist_notice.wishlist_view_text + '</a>';

        if (single) {
            $message = '<div class="message-box">' + $message + '</div>';
        }

        $.notify.addStyle('martfury', {
            html: '<div><i class="icon-checkmark-circle message-icon"></i><span data-notify-text/>' + $message + '<span class="close icon-cross2"></span> </div>'
        });
        $.notify($content, {
            autoHideDelay: martfuryData.added_to_wishlist_notice.wishlist_notice_auto_hide,
            className: className,
            style: 'martfury',
            showAnimation: 'fadeIn',
            hideAnimation: 'fadeOut'
        });
    };

    function addedToCartNotice($message, single, className, multiple) {
        if (typeof martfuryData.added_to_cart_notice === 'undefined' || !$.fn.notify) {
            return;
        }

        if (!single) {
            if (multiple) {
                $message += ' ' + martfuryData.added_to_cart_notice.added_to_cart_texts;
            } else {
                $message += ' ' + martfuryData.added_to_cart_notice.added_to_cart_text;
            }
        }


        $message += '<a href="' + martfuryData.added_to_cart_notice.cart_view_link + '" class="btn-button">' + martfuryData.added_to_cart_notice.cart_view_text + '</a>';

        if (single) {
            $message = '<div class="message-box">' + $message + '</div>';
        }

        $.notify.addStyle('martfury', {
            html: '<div><i class="icon-checkmark-circle message-icon"></i>' + $message + '<span class="close icon-cross2"></span> </div>'
        });

        $.notify('&nbsp', {
            autoHideDelay: martfuryData.added_to_cart_notice.cart_notice_auto_hide,
            className: className,
            style: 'martfury',
            showAnimation: 'fadeIn',
            hideAnimation: 'fadeOut'
        });
    };

    /**
     * Document ready
     */
    $(function () {
		if ( typeof martfuryPbt === 'undefined' ) {
			return false;
		}

		if (! $('body').hasClass('single-product')) {
			return;
		}

		var $pbtProducts = $('#mf-product-fbt');

		if ( $pbtProducts.length <= 0) {
			return;
		}

		navigationProduct();

		check_button();

        selectProduct();
        fbtAddToCartAjax();
        fbtAddToWishlistAjax();
        check_ready();

        productVariationChange();
    });

})(jQuery);