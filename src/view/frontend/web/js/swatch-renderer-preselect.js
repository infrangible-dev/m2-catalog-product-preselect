/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery'
], function ($) {
    'use strict';

    /**
     * The configuration of the preselection, from Plugin\ConfigurableProduct\Block\Product\View\Type\Configurable.
     *
     * @typedef {Object} CatalogProductPreselectConfig
     * @property {boolean} enable
     * @property {string} mode
     * @property {Array<Array<string>>} attributeCombinations the attribute codes of the combinations, longest first
     */

    /**
     * A level of the preselected options in the default values of the JSON configuration: "all" for the preselected
     * variant, and the levels of the attribute codes and option IDs of every combination of options down to its
     * preselected variant.
     *
     * @typedef {Object<string, CatalogProductPreselectDefaultValues>} CatalogProductPreselectDefaultValues
     * @property {?Object<string, string>} defaultValues the options of the preselected variant by attribute code
     * @property {?CatalogProductPreselectDefaultValues} all the level of the preselected variant
     */

    /**
     * The parts of the JSON configuration of the swatches that the preselection adds.
     *
     * @typedef {Object} CatalogProductPreselectJsonConfig
     * @property {CatalogProductPreselectConfig} preselect
     * @property {CatalogProductPreselectDefaultValues} defaultValues
     */

    /**
     * Returns the preselected options of an attribute combination, if all its attributes are selected already.
     *
     * @param {CatalogProductPreselectDefaultValues} defaultValues
     * @param {Array<string>} attributeCodes
     * @param {Object<string, string>} selectedAttributes
     * @returns {?Object<string, string>}
     */
    const getCombinationValues = function (defaultValues, attributeCodes, selectedAttributes) {
            let values = defaultValues,
                index,
                attributeCode;

            for (index = 0; index < attributeCodes.length; index++) {
                if (!(attributeCodes[index] in selectedAttributes)) {
                    return null;
                }
            }

            for (index = 0; index < attributeCodes.length; index++) {
                attributeCode = attributeCodes[index];

                if (attributeCode in values) {
                    values = values[attributeCode];
                }

                if (selectedAttributes[attributeCode] in values) {
                    values = values[selectedAttributes[attributeCode]];
                }
            }

            return values.defaultValues || null;
        },

        /**
         * Returns the preselected options of the first (longest) attribute combination whose attributes are selected
         * already.
         *
         * @param {CatalogProductPreselectJsonConfig} config the JSON configuration of the swatches
         * @param {Object<string, string>} selectedAttributes
         * @returns {?Object<string, string>}
         */
        findCombinationValues = function (config, selectedAttributes) {
            const preselect = config.preselect,
                combinations = preselect.attributeCombinations || [];
            let index,
                values;

            for (index = 0; index < combinations.length; index++) {
                values = getCombinationValues(config.defaultValues, combinations[index], selectedAttributes);

                if (values) {
                    return values;
                }
            }

            return null;
        };

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', SwatchRenderer, {
            hasSelectedAttributes: false,
            urlSelectedAttributes: {},

            /**
             * Selects the preselected variant after the swatches are rendered; if options are selected already (by
             * the URL), the variant that is preselected for them.
             */
            _RenderControls: function () {
                /** @type {CatalogProductPreselectJsonConfig} */
                const config = this.options.jsonConfig;
                let values;

                this._super();

                if (!config.preselect || !config.preselect.enable || !config.defaultValues) {
                    return;
                }

                values = this.hasSelectedAttributes ? findCombinationValues(config, this.urlSelectedAttributes) : null;

                if (!values && config.defaultValues.all) {
                    values = config.defaultValues.all.defaultValues;
                }

                if (values) {
                    this._EmulateSelected(values);
                }
            },

            /**
             * Remembers the options that are selected by the URL.
             *
             * @param {Object<string, string>} selectedAttributes
             */
            _EmulateSelected: function (selectedAttributes) {
                if (!$.isEmptyObject(selectedAttributes)) {
                    this.hasSelectedAttributes = true;
                    this.urlSelectedAttributes = selectedAttributes;
                }

                this._super(selectedAttributes);
            }
        });

        return $.mage.SwatchRenderer;
    };
});
