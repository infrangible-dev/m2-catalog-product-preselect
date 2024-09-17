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
                    if (! $.isEmptyObject(this.options.jsonConfig.defaultValues)) {
                        this._EmulateSelected(this.options.jsonConfig.defaultValues);
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
