(function ($) {
    'use strict';
    var martfury = martfury || {};

    martfury.found_data = false;
    martfury.variation_id = 0;

    martfury.foundVariationImages = function( ) {
        $( '.variations_form:not(.form-cart-pbt)' ).on('found_variation', function(e, $variation){
            if( martfury.variation_id != $variation.variation_id ) {
                martfury.changeVariationImagesAjax($variation.variation_id, $(this).data('product_id'));
                martfury.found_data = true;
                martfury.variation_id = $variation.variation_id;
            }
        });
    }

    martfury.resetVariationImages = function( ) {
        $( '.variations_form:not(.form-cart-pbt)' ).on('reset_data', function(e){
            if( martfury.found_data ) {
                martfury.changeVariationImagesAjax(0, $(this).data('product_id'));
                martfury.found_data = false;
                martfury.variation_id = 0;
            }

        });
    }

    martfury.changeVariationImagesAjax = function(variation_id, product_id) {
        var $productGallery = $('.woocommerce-product-gallery'),
            galleryHeight = $productGallery.height();
            $productGallery.addClass('loading').css( {'overflow': 'hidden' });
            if( ! $productGallery.closest('.single-product').hasClass('quick-view-modal') ) {
                $productGallery.css( {'height': galleryHeight });
            }

        var data = {
            'variation_id': variation_id,
            'product_id': product_id,
            nonce: martfuryData.nonce,
        },
        ajax_url = martfuryData.wc_ajax_url.toString().replace('%%endpoint%%', 'martfury_get_variation_images');

        var xhr = $.post(
            ajax_url,
            data,
            function (response) {
                var $gallery = $(response.data);

                $productGallery.html( $gallery.html() );
                if ( typeof wc_single_product_params !== 'undefined' && $.fn.wc_product_gallery) {
                    $productGallery.removeData('flexslider');
                    $productGallery.off('click', '.woocommerce-product-gallery__image a');
                    $productGallery.off('click', '.woocommerce-product-gallery__trigger');
                    $productGallery.wc_product_gallery( wc_single_product_params );
                    $productGallery.trigger('product_thumbnails_slider');
                    $productGallery.trigger('product_video_slider');
                    $productGallery.trigger('product_gallery');
                }
                $productGallery.trigger('martfury_update_product_gallery_on_quickview');

                $productGallery.imagesLoaded(function () {
                    setTimeout(function() {
                        $productGallery.removeClass('loading').removeAttr( 'style' ).css('opacity', '1');
                    }, 200);
                    $productGallery.trigger( 'martfury_gallery_init_zoom', $productGallery.find('.woocommerce-product-gallery__image').first());
                } );

            }
        );
    }
    /**
     * Document ready
     */
    $(function () {
        if( $('div.product' ).hasClass('product-has-variation-images') ) {
            martfury.foundVariationImages();
            martfury.resetVariationImages();
        }

        $('body').on( 'martfury_product_quick_view_loaded', function() {
            if( $('div.product' ).hasClass('product-has-variation-images') ) {
                martfury.foundVariationImages();
                martfury.resetVariationImages();
            }
        } );
    });

})(jQuery);