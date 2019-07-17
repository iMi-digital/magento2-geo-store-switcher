define([
    'uiComponent',
    'jquery',
    'Magento_Ui/js/modal/modal',
    'SATA_StoreSwitch/js/store_switcher_cookie'
], function (Component, $, modal, store_switcher_cookie) {
    'use strict';
    let $modalContainer = $("#store-switcher-popup");
    let cookieHandler = new store_switcher_cookie;
    return Component.extend({

        initialize: function () {

            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: false,
                buttons: false
            };

            var switcher_popup_element = $('#store-switcher-popup');
            var popup = modal(options, switcher_popup_element);

            switcher_popup_element.css("display", "block");

            if (!cookieHandler.getStoreLanguageCookie()) {
                this.openSwitcherModal();
            }
            this.addEventListeners();
        },

        openSwitcherModal: function () {
            $modalContainer.modal('openModal');
            cookieHandler.setStoreLanguageCookie(cookieHandler.getActiveStoreLanguage());
        },

        addEventListeners: function () {
            $('#store-switcher-popup button').click(function () {
                $modalContainer.modal('closeModal');
            });
        }
    });
});