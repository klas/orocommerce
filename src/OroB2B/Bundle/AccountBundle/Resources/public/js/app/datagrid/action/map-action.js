define(function(require) {
    'use strict';

    var MapAction;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var GoogleMaps = require('oroaddress/js/mapservice/googlemaps');
    var ModelAction = require('oro/datagrid/action/model-action');

    /**
     * Map action. Invokes popup with a map
     *
     * @export  orob2baccount/js/app/datagrid/action/map-action
     * @class   orob2b.account.action.MapAction
     * @extends oro.datagrid.action.ModelAction
     */
    MapAction = ModelAction.extend({
        /**
         * Map options
         *
         * * @property {Object}
         * */
        options: {
            'mapOptions': {
                zoom: 12
            },
            'mapView': GoogleMaps
        },

        /**
         * @property {Boolean}
         */
        dispatched: true,

        /**
         * Initialize launcher options
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var opts = options || {};

            this.model = opts.model;

            this.launcherOptions = _.extend({
                template: require('tpl!orob2baccount/templates/datagrid/action-map-button.html')
            }, this.launcherOptions);

            mediator.on('grid_render:complete', this.onGridRendered, this);

            this.initMapContainer();
            this.initMap();

            //this.mapView.updateMap(this.getAddress(), this.model.get('label'));

            MapAction.__super__.initialize.apply(this, arguments);
        },

        onGridRendered: function() {
            var $popoverTrigger = $('[data-map-popover-trigger]');

            $popoverTrigger.popover({
                title: 'Map',
                placement: 'left',
                content: this.$mapContainerFrame,
                animation: false,
                html: true,
                template: [
                    '<div class="popover">',
                        '<button type="button" class="close" aria-hidden="true" data-map-popover-close>&times;</button>',
                        '<div class="popover-inner">',
                            '<div class="popover-content"></div>',
                        '</div>',
                    '</div>'
                ].join('')
            });

            $popoverTrigger
                .on('shown.bs.popover', function() {
                    $('[data-map-popover-close]').on('click', function() {
                        $popoverTrigger.popover('hide');
                    });
                })
                .on('hide.bs.popover', function() {
                    $('[data-map-popover-close]').off('click');
                });
        },

        initMapContainer: function() {
            this.$mapContainerFrame = $('<div class="map-popup-frame"/>');
        },

        initMap: function() {
            this.mapView = new this.options.mapView({
                'mapOptions': this.options.mapOptions,
                'el': this.$mapContainerFrame
            });
        },

        /**
         *
         * @return {String}
         */
        getAddress: function() {
            return this.model.get('countryName') + ', ' +
                this.model.get('city') + ', ' +
                this.model.get('street') + ' ' + (this.model.get('street2') || '');
        }
    });

    return MapAction;
});
