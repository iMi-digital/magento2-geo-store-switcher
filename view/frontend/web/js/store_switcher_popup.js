define([
    'uiComponent',
    'jquery',
    'Magento_Ui/js/modal/modal'
], function (Component, $, modal) {
    'use strict';

    var $modalContainer = $("#store-switcher-popup");

    return Component.extend({

        initialize: function () {
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: false,
                buttons: false
            };

            var switcher_popup_element = $('#store-switcher-popup');
            modal(options, switcher_popup_element);

            if (this.isRedirected()) {
                this.openSwitcherModal();
                this.addEventListeners();
            }
        },
        openSwitcherModal: function () {
            $modalContainer.modal('openModal');
        },
        addEventListeners: function () {
            $('#store-switcher-popup button').click(function () {
                $modalContainer.modal('closeModal');
            });
        },
        isRedirected: function () {
            var url = new URL(window.location.href);
            return url.searchParams.get('redirected');
        }
    });
});