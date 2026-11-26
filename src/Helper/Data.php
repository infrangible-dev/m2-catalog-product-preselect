<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPreselect\Helper;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPreselect\Model\Config\Source\Mode;

/**
 * Finds the variant of a configurable product to preselect in the JSON configuration of its options.
 */
class Data
{
    /**
     * @param Arrays $arrays
     * @param Variables $variables
     */
    public function __construct(
        protected Arrays $arrays,
        protected Variables $variables
    ) {
    }

    /**
     * Returns the ID of the variant to preselect: the first one, or the one with the lowest or highest final price.
     *
     * With attributes, only the variants that have these options are considered.
     *
     * @param array $config the JSON configuration of the options of the configurable product
     * @phpstan-param array<string, mixed> $config
     * @param string $mode
     * @param array $attributes option IDs by attribute ID
     * @phpstan-param array<int|string, int|string> $attributes
     *
     * @return int|null
     */
    public function identifyPreselectedProduct(array $config, string $mode, array $attributes = []): ?int
    {
        $index = $config[ 'index' ] ?? [];
        $optionPrices = $config[ 'optionPrices' ] ?? [];

        if (! is_array($index) || ! is_array($optionPrices)) {
            return null;
        }

        $preselectedPrice = null;
        $preselectedProductId = null;

        foreach ($optionPrices as $productId => $productPrices) {
            $productAttributes = $index[ $productId ] ?? null;

            if (! is_array($productAttributes) ||
                count(array_diff_assoc($this->toStrings($attributes), $this->toStrings($productAttributes))) > 0) {
                continue;
            }

            $optionPrice = (float)$this->variables->stringValueOrDefault(
                is_array($productPrices) ? $this->arrays->getValue($productPrices, 'finalPrice:amount') : null,
                '0'
            );

            if ($preselectedProductId === null ||
                ($mode === Mode::LOWEST && $optionPrice < $preselectedPrice) ||
                ($mode === Mode::HIGHEST && $optionPrice > $preselectedPrice)) {

                $preselectedPrice = $optionPrice;
                $preselectedProductId = (int)$productId;
            }
        }

        return $preselectedProductId;
    }

    /**
     * Returns the options of the variant by attribute code.
     *
     * @param array $config the JSON configuration of the options of the configurable product
     * @phpstan-param array<string, mixed> $config
     * @param int $preselectedProductId
     *
     * @return array|null
     * @phpstan-return array<string, string>|null
     */
    public function getPreselectedAttributes(array $config, int $preselectedProductId): ?array
    {
        $preselectedAttributes = [];

        foreach ($this->getPreselectedAttributeIds($config, $preselectedProductId) as $attributeId => $optionId) {
            $attributeCode = $this->arrays->getValue($config, sprintf('attributes:%d:code', $attributeId));

            if (is_string($attributeCode) && $attributeCode !== '') {
                $preselectedAttributes[ $attributeCode ] = $optionId;
            }
        }

        return $preselectedAttributes;
    }

    /**
     * Returns the options of the variant by attribute ID, the format of the default values of Magento.
     *
     * @param array $config the JSON configuration of the options of the configurable product
     * @phpstan-param array<string, mixed> $config
     * @param int $preselectedProductId
     *
     * @return array<string, string>
     */
    public function getPreselectedAttributeIds(array $config, int $preselectedProductId): array
    {
        $productAttributes = $this->arrays->getValue($config, sprintf('index:%d', $preselectedProductId));

        return is_array($productAttributes) ? $this->toStrings($productAttributes) : [];
    }

    /**
     * Returns the values as strings by key as string, as the JSON configuration has them.
     *
     * @param array $values
     * @phpstan-param array<int|string, mixed> $values
     *
     * @return array<string, string>
     */
    private function toStrings(array $values): array
    {
        $strings = [];

        foreach ($values as $key => $value) {
            $strings[ (string)$key ] = $this->variables->stringValueOrDefault($value);
        }

        return $strings;
    }
}
