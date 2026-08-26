( function( wp, wc ) {
    // 1. Достаем нужные функции из глобальных объектов
    const el = wp.element.createElement;
    const registerPlugin = wp.plugins.registerPlugin;
    const useSelect = wp.data.useSelect;
    const ExperimentalOrderShippingPackages = wc.blocksCheckout.ExperimentalOrderShippingPackages;

    console.log(wc.blocksCheckout);

    // 2. Создаем наш компонент
    const CustomShippingOptions = function() {
        // Подписываемся на данные корзины
        const cartData = useSelect( function( select ) {
            const store = select( 'wc/store/cart' );
            return {
                cart: store ? store.getCartData() : null,
            };
        } );

        const cart = cartData.cart;

        // Если корзина еще грузится или нет методов доставки — скрываем
        if ( ! cart || ! cart.shippingRates || cart.shippingRates.length === 0 ) {
            return null;
        }

        const firstPackage = cart.shippingRates[0];
        const selectedRate = firstPackage.shipping_rates.find( function( rate ) {
            return rate.selected;
        });

        const myCustomMethodId = 'nova_poshta_shipping'; // <-- Укажите ID вашего метода!

        // Если выбран другой метод — скрываем
        if ( ! selectedRate || selectedRate.method_id !== myCustomMethodId ) {
            return null;
        }

        // Если метод наш — рендерим HTML.
        // el('тег', {атрибуты}, 'содержимое/вложенные теги')
        return el(
            'div',
            {
                id: 'test-smarty',
                className: 'my-custom-shipping-wrapper',
                style: { padding: '15px', background: '#f7f7f7', marginTop: '15px' }
            }
        );
    };

    // 3. Регистрируем плагин для Checkout
    registerPlugin( 'my-custom-shipping-plugin', {
        render: function() {
            return el(
                ExperimentalOrderShippingPackages,
                null,
                el( CustomShippingOptions, null )
            );
        },
        scope: 'woocommerce-checkout',
    } );

} )( window.wp, window.wc );