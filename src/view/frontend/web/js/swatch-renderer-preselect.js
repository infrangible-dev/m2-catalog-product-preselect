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
            urlSelectedAttributes: {},

            _RenderControls: function () {
                this._super();

                const config = this.options.jsonConfig;

                if (config.preselect.enable) {
                    if (this.hasSelectedAttributes) {
                        const urlSelectedAttributes = this.urlSelectedAttributes;

                        let foundCombination = false;

                        for (let i = 0; i < config.preselect.attributeCombinations.length; i++) {
                            const attributeCombination = config.preselect.attributeCombinations[i];
                            let useAttributeCombination = true;

                            for (let j = 0; j < attributeCombination.length; j++) {
                                const attributeCode = attributeCombination[j];
                                if (! (attributeCode in urlSelectedAttributes)) {
                                    useAttributeCombination = false;
                                }
                            }

                            if (useAttributeCombination) {
                                let defaultValues = config.defaultValues;
                                for (let j = 0; j < attributeCombination.length; j++) {
                                    const attributeCode = attributeCombination[j];
                                    if (attributeCode in defaultValues) {
                                        defaultValues = defaultValues[attributeCode];
                                    }
                                    if (attributeCode in urlSelectedAttributes) {
                                        const attributeValue = urlSelectedAttributes[attributeCode];
                                        if (attributeValue in defaultValues) {
                                            defaultValues = defaultValues[attributeValue];
                                        }
                                    }
                                }

                                if (defaultValues.defaultValues) {
                                    this._EmulateSelected(defaultValues.defaultValues);
                                    foundCombination = true;
                                    break;
                                }
                            }
                        }

                        if (! foundCombination) {
                            this._EmulateSelected(config.defaultValues.all.defaultValues);
                        }
                    } else {
                        this._EmulateSelected(config.defaultValues.all.defaultValues);
                    }
                }
            },

            _EmulateSelected: function (selectedAttributes) {
                if (! $.isEmptyObject(selectedAttributes)) {
                    this.hasSelectedAttributes = true;
                    this.urlSelectedAttributes = selectedAttributes;
                }

                this._super(selectedAttributes);
            }
        });

        return $.mage.SwatchRenderer;
    };
});
