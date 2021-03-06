define(function(require) {
    'use strict';

    var SinglePageCheckoutFormView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var SinglePageCheckoutSubmitButtonView =
        require('orocheckout/js/app/views/single-page-checkout-submit-button-view');
    var SinglePageCheckoutAddressView = require('orocheckout/js/app/views/single-page-checkout-address-view');

    SinglePageCheckoutFormView = BaseView.extend({
        /**
         * @property
         */
        options: {
            submitButtonSelector: '[type="submit"]',
            billingAddressSelector: 'select[data-role="checkout-billing-address"]',
            shippingAddressSelector: 'select[data-role="checkout-shipping-address"]',
            shipToSelector: '[data-role="checkout-ship-to"]',
            transitionFormFieldSelector: '[name*="oro_workflow_transition"]',
            originShippingMethodTypeSelector: '[name$="shippingMethodType"]',
            formShippingMethodSelector: '[name$="[shipping_method]"]',
            formShippingMethodTypeSelector: '[name$="[shipping_method_type]"]',
            originPaymentMethodSelector: '[name="paymentMethod"]',
            formPaymentMethodSelector: '[name$="[payment_method]"]',
            originPaymentFormSelector: '[data-content="payment_method_form"]',
            stateTokenSelector: '[name$="[state_token]"]',
            entityId: null
        },

        /**
         * @inheritDoc
         */
        events: {
            change: 'onChange',
            submit: 'onSubmit'
        },

        /**
         * @inheritDoc
         */
        listen: {
            'before-save-state': 'onBeforeSaveState',
            'after-save-state': 'onAfterSaveState'
        },

        /**
         * @property {string}
         */
        lastSerializedData: null,

        /**
         * @property {number}
         */
        timeout: 50,

        /**
         * @inheritDoc
         */
        constructor: function SinglePageCheckoutFormView() {
            this.onChange = _.debounce(this.onChange, this.timeout);
            SinglePageCheckoutFormView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});

            this.subview('checkoutSubmitButton', new SinglePageCheckoutSubmitButtonView({
                el: this.$el.find(this.options.submitButtonSelector)
            }));

            this.subview('checkoutBillingAddress', new SinglePageCheckoutAddressView({
                el: this.$el.find(this.options.billingAddressSelector),
                entityId: this.options.entityId
            }));

            this.subview('checkoutShippingAddress', new SinglePageCheckoutAddressView({
                el: this.$el.find(this.options.shippingAddressSelector),
                entityId: this.options.entityId
            }));

            this._toggleShipTo();
            this._disableShippingAddress();
            this._changeShippingMethod();
            this._changePaymentMethod();

            SinglePageCheckoutFormView.__super__.initialize.call(this, arguments);
        },

        afterCheck: function($el) {
            if (!$el) {
                $el = this.$el;
            }
            var serializedData = this.getSerializedData();

            if (this.lastSerializedData === serializedData) {
                return;
            }

            this.trigger('after-check-form', serializedData, $el);
            this.lastSerializedData = serializedData;
        },

        /**
         * @param {jQuery.Event} event
         */
        onChange: function(event) {
            if (this.subview('checkoutSubmitButton').isHovered()) {
                return;
            }

            // Do not execute logic when hidden element (form) is refreshed
            if (!$(event.target).is(':visible')) {
                return;
            }

            this._toggleShipTo();
            this._disableShippingAddress();

            this._changeShippingMethod();
            this._changePaymentMethod();

            this.afterCheck($(event.target));
        },

        onBeforeSaveState: function() {
            this._disableShippingAddress();
            this.subview('checkoutSubmitButton').onToggleState();
        },

        onAfterSaveState: function() {
            // Resets submit button element
            this.subview('checkoutSubmitButton').setElement(this.$el.find(this.options.submitButtonSelector));
            this.subview('checkoutSubmitButton').onEnableState();
        },

        /**
         * @param {jQuery.Event} event
         */
        onSubmit: function(event) {
            event.preventDefault();

            var validate = this.$el.validate();
            if (!validate.form()) {
                return;
            }

            var paymentMethod = this.$el.find(this.options.formPaymentMethodSelector).val();
            var eventData = {
                stopped: false,
                resume: _.bind(this.transit, this),
                data: {paymentMethod: paymentMethod}
            };

            mediator.trigger('checkout:payment:before-transit', eventData);

            if (eventData.stopped) {
                return;
            }

            this.transit();
        },

        transit: function() {
            this._changeShippingMethod();
            this._changePaymentMethod();

            var paymentMethod = this.$el.find(this.options.formPaymentMethodSelector).val();
            var eventData = {paymentMethod: paymentMethod};
            mediator.trigger('checkout:payment:before-form-serialization', eventData);

            this.subview('checkoutSubmitButton').onToggleState();

            this.trigger('submit-form', this.getSerializedData());
        },

        getSerializedData: function() {
            var $form = this.$el.closest('form');
            $form.find(this.options.stateTokenSelector).prop('disabled', false);

            return $form.find(this.options.transitionFormFieldSelector).serialize();
        },

        _disableShippingAddress: function() {
            var $element = this.$el.find(this.options.shipToSelector);
            var disable = $element.is(':visible') && $element.is(':checked');
            var $billingAddress = this.subview('checkoutBillingAddress').$el;
            var text = $billingAddress.find(':selected').text();

            this.subview('checkoutShippingAddress').onToggleState(disable, $billingAddress.val(), text);
        },

        _isAvailableShipTo: function() {
            return this.subview('checkoutBillingAddress').isAvailableShippingType('shipping');
        },

        _toggleShipTo: function() {
            var $element = this.$el.find(this.options.shipToSelector);
            var $container = $element.parent();
            if (this._isAvailableShipTo()) {
                $container.removeClass('hidden');
            } else {
                $element.prop('checked', false);
                $container.addClass('hidden');
            }
        },

        _changeShippingMethod: function() {
            var $selectedType = this.$el.find(this.options.originShippingMethodTypeSelector).filter(':checked');

            if (!$selectedType.val()) {
                return;
            }

            this.$el.find(this.options.formShippingMethodSelector).val($selectedType.data('shipping-method'));
            this.$el.find(this.options.formShippingMethodTypeSelector).val($selectedType.data('shipping-type'));
        },

        _changePaymentMethod: function() {
            var $selectedMethodVal = this.$el.find(this.options.originPaymentMethodSelector).filter(':checked').val();

            if (!$selectedMethodVal) {
                return;
            }

            this.$el.find(this.options.formPaymentMethodSelector).val($selectedMethodVal);
        }
    });

    return SinglePageCheckoutFormView;
});
