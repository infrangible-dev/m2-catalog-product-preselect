/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', SwatchRenderer, {
            hasSelectedAttributes: false,

            _RenderControls: function () {
                this._super();

                if (this.options.jsonConfig.preselect.enable && ! this.hasSelectedAttributes) {
                    var self = this;
                    var preSelectedPrice = null;
                    var preSelectedProductId = null;

                    $.each(self.options.jsonConfig.optionPrices, function (productId, prices) {
                        var optionPrice = prices.finalPrice.amount;

                        if (preSelectedProductId === null ||
                            (self.options.jsonConfig.preselect.mode === 'lowest' && optionPrice < preSelectedPrice) ||
                            (self.options.jsonConfig.preselect.mode === 'highest' && optionPrice > preSelectedPrice)) {

                            preSelectedPrice = optionPrice;
                            preSelectedProductId = productId;
                        }
                    });

                    var selectedAttributes = {};

                    $.each(self.options.jsonConfig.attributes, function (attributeId, attributeData) {
                        $.each(attributeData.options, function (optionKey, optionData) {
                            $.each(optionData.products, function (productKey, productId) {
                                if (productId === preSelectedProductId) {
                                    selectedAttributes[attributeData.code] = optionData.id;
                                }
                            });
                        });
                    });

                    if (! $.isEmptyObject(selectedAttributes)) {
                        self._EmulateSelected(selectedAttributes);
                    }
                }
            },

            _EmulateSelected: function (selectedAttributes) {
                if (! $.isEmptyObject(selectedAttributes)) {
                    this.hasSelectedAttributes = true;
                }

                this._super(selectedAttributes);
            }
        });

        return $.mage.SwatchRenderer;
    };
});
