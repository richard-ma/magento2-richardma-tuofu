define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'tuofu_method',
                component: 'Richardma_Tuofu/js/view/payment/method-renderer/tuofu-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
