<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPreselect\Plugin\ConfigurableProduct\Block\Product\View\Type;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPreselect\Helper\Data;
use Infrangible\CatalogProductPreselect\Model\Config\Source\Mode;
use Infrangible\Core\Helper\Stores;
use InvalidArgumentException;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableBlock;
use Magento\Swatches\Block\Product\Renderer\Listing\Configurable as ListingConfigurableBlock;

/**
 * Adds the preselected variant to the JSON configuration of the options of configurable products, for the product
 * page and the product lists:
 * - "defaultValues": the options of the preselected variant by attribute ID, which the options of Magento without
 *   swatches select (configurable.js), and the options by attribute code in "all" and for every combination of options
 *   that may already be selected (by the URL) in "<code>", "<option ID>", ..., which the swatches select
 *   (swatch-renderer-preselect.js)
 * - "preselect": whether the preselection is enabled, its mode and the attribute combinations in "defaultValues",
 *   longest first
 */
class Configurable
{
    /**
     * @param Json $json
     * @param Stores $storeHelper
     * @param Data $helper
     * @param Arrays $arrays
     * @param Variables $variables
     */
    public function __construct(
        protected Json $json,
        protected Stores $storeHelper,
        protected Data $helper,
        protected Arrays $arrays,
        protected Variables $variables
    ) {
    }

    /**
     * Adds the preselected variant to the JSON configuration.
     *
     * @param ConfigurableBlock $subject
     * @param string $result
     *
     * @return string
     */
    public function afterGetJsonConfig(ConfigurableBlock $subject, string $result): string
    {
        $config = $this->json->decode($result);

        if (! is_array($config)) {
            return $result;
        }

        if ($subject->getProduct()->getData('preconfigured_values')) {
            $config[ 'preselect' ] = ['enable' => false];

            return $this->json->encode($config) ?? $result;
        }

        $site = $subject instanceof ListingConfigurableBlock ? 'listing' : 'page';
        $enabled = $this->storeHelper->getStoreConfigFlag(
            sprintf('infrangible_catalogproductpreselect/%s/enable', $site)
        );
        $mode = $this->getMode($site);

        $config[ 'preselect' ] = [
            'enable' => $enabled,
            'mode'   => $mode
        ];

        if ($enabled) {
            $config = $this->addPreselection($config, $mode);
        }

        return $this->json->encode($config) ?? $result;
    }

    /**
     * Returns the configured mode of the page or the product lists, the first one if none or an unknown is configured.
     *
     * @param string $site
     *
     * @return string
     */
    private function getMode(string $site): string
    {
        $mode = $this->storeHelper->getStoreConfig(sprintf('infrangible_catalogproductpreselect/%s/mode', $site));

        return in_array($mode, Mode::MODES, true) ? $mode : Mode::MODES[ 0 ];
    }

    /**
     * Adds the preselected variant and the preselected variants of all combinations of options.
     *
     * @param array $config
     * @phpstan-param array<string, mixed> $config
     * @param string $mode
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    private function addPreselection(array $config, string $mode): array
    {
        $preselectedProductId = $this->helper->identifyPreselectedProduct($config, $mode);

        if ($preselectedProductId !== null) {
            $defaultValues = $config[ 'defaultValues' ] ?? [];
            $defaultValues = is_array($defaultValues) ? $defaultValues : [];

            // the format of Magento, which the options without swatches use (configurable.js)
            $attributeIds = $this->helper->getPreselectedAttributeIds($config, $preselectedProductId);

            foreach ($attributeIds as $attributeId => $optionId) {
                $defaultValues[ $attributeId ] = $optionId;
            }

            $preselectedAttributes = $this->helper->getPreselectedAttributes($config, $preselectedProductId);

            if ($preselectedAttributes) {
                $defaultValues[ 'all' ][ 'defaultValues' ] = $preselectedAttributes;
            }

            $config[ 'defaultValues' ] = $defaultValues;
        }

        $attributeCombinations = [];

        foreach ($this->getCombinations($this->getAttributeOptionIds($config)) as $attributeOptionCombination) {
            $combinationProductId = $this->helper->identifyPreselectedProduct(
                $config,
                $mode,
                $attributeOptionCombination
            );

            $preselectedAttributes = $combinationProductId === null ?
                [] : $this->helper->getPreselectedAttributes($config, $combinationProductId);

            if ($preselectedAttributes) {
                $defaultValueKey = ['defaultValues'];
                $attributeCodes = [];

                foreach ($attributeOptionCombination as $attributeId => $attributeOptionId) {
                    $attributeKey = $this->arrays->getValue(
                        $config,
                        sprintf('attributes:%d:%s', $attributeId, $this->getDefaultValuesAttributeKey())
                    );

                    $attributeKey = $this->variables->stringValueOrDefault($attributeKey);

                    $defaultValueKey[] = $attributeKey;
                    $defaultValueKey[] = (string)$attributeOptionId;
                    $attributeCodes[] = $attributeKey;
                }

                $defaultValueKey[] = 'defaultValues';

                $config = $this->addDeepValue($config, $defaultValueKey, $preselectedAttributes);

                $attributeCombinations[ implode("\n", $attributeCodes) ] = $attributeCodes;
            }
        }

        $attributeCombinations = array_values($attributeCombinations);

        usort(
            $attributeCombinations,
            static fn (array $a, array $b): int => count($b) <=> count($a)
        );

        $preselect = $config[ 'preselect' ] ?? [];
        $preselect = is_array($preselect) ? $preselect : [];
        $preselect[ 'attributeCombinations' ] = $attributeCombinations;
        $config[ 'preselect' ] = $preselect;

        return $config;
    }

    /**
     * Adds a value to the configuration with the keys of the path to it.
     *
     * @param array $config
     * @phpstan-param array<string, mixed> $config
     * @param list<string> $keys
     * @param array $value
     * @phpstan-param array<string, string> $value
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    private function addDeepValue(array $config, array $keys, array $value): array
    {
        $result = [];

        foreach ($this->arrays->addDeepValue($config, $keys, $value) as $key => $item) {
            $result[ (string)$key ] = $item;
        }

        return $result;
    }

    /**
     * Returns the IDs of the options of every attribute.
     *
     * @param array $config
     * @phpstan-param array<string, mixed> $config
     *
     * @return array<int, list<int>>
     */
    private function getAttributeOptionIds(array $config): array
    {
        $attributes = $config[ 'attributes' ] ?? [];
        $attributeOptionIds = [];

        if (! is_array($attributes)) {
            return [];
        }

        foreach ($attributes as $attributeId => $attributeData) {
            $attributeOptions = is_array($attributeData) ? ($attributeData[ 'options' ] ?? []) : [];

            if (! is_array($attributeOptions)) {
                continue;
            }

            foreach ($attributeOptions as $attributeOption) {
                try {
                    $optionId = is_array($attributeOption) ? ($attributeOption[ 'id' ] ?? null) : null;

                    $attributeOptionIds[ $this->variables->intValue($attributeId) ][] =
                        $this->variables->intValue($optionId);
                } catch (InvalidArgumentException) {
                    continue;
                }
            }
        }

        return $attributeOptionIds;
    }

    /**
     * Returns every combination of options, for one option of one attribute up to one option of every attribute.
     *
     * @param array $attributeOptionIds the option IDs by attribute ID
     * @phpstan-param array<int, list<int>> $attributeOptionIds
     *
     * @return array
     * @phpstan-return list<array<int, int>>
     */
    public function getCombinations(array $attributeOptionIds): array
    {
        $result = [[]];

        foreach ($attributeOptionIds as $attributeId => $attributeOptions) {
            $combinations = [];

            foreach ($result as $resultItem) {
                if (! empty($resultItem)) {
                    $combinations[] = $resultItem;
                }

                foreach ($attributeOptions as $attributeOptionId) {
                    if (! empty($resultItem)) {
                        $combinations[] = [$attributeId => $attributeOptionId];
                    }

                    $combinations[] = $resultItem + [$attributeId => $attributeOptionId];
                }
            }

            $result = $combinations;
        }

        $unique = [];

        foreach ($result as $combination) {
            $unique[ http_build_query($combination) ] ??= $combination;
        }

        return array_values($unique);
    }

    /**
     * Returns the key of the attributes in the configuration that the default values of the combinations use.
     *
     * @return string
     */
    public function getDefaultValuesAttributeKey(): string
    {
        return 'code';
    }
}
