<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPreselect\Helper;

use FeWeDev\Base\Arrays;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Data
{
    /** @var Arrays */
    protected $arrays;

    public function __construct(Arrays $arrays)
    {
        $this->arrays = $arrays;
    }

    public function identifyPreselectedProduct(array $config, string $mode, array $attributes = []): ?int
    {
        $index = $this->arrays->getValue(
            $config,
            'index',
            []
        );

        $limitProductIds = [];

        foreach ($index as $productId => $productAttributes) {
            if (empty($attributes)) {
                $limitProductIds[] = $productId;
            } elseif (! array_diff(
                $attributes,
                $productAttributes
            )) {
                $limitProductIds[] = $productId;
            }
        }

        $preselectedPrice = null;
        $preselectedProductId = null;

        $optionPrices = $this->arrays->getValue(
            $config,
            'optionPrices',
            []
        );

        foreach ($optionPrices as $productId => $productPrices) {
            if (! in_array(
                $productId,
                $limitProductIds
            )) {
                continue;
            }

            $optionPrice = $this->arrays->getValue(
                $productPrices,
                'finalPrice:amount'
            );

            if ($preselectedProductId === null || ($mode === 'lowest' && $optionPrice < $preselectedPrice) ||
                ($mode === 'highest' && $optionPrice > $preselectedPrice)) {

                $preselectedPrice = $optionPrice;
                $preselectedProductId = $productId;
            }
        }

        return $preselectedProductId;
    }

    public function getPreselectedAttributes(array $config, int $preselectedProductId): ?array
    {
        $preselectedProductAttributes = $this->arrays->getValue(
            $config,
            sprintf(
                'index:%d',
                $preselectedProductId
            )
        );

        $preselectedAttributes = [];

        foreach ($preselectedProductAttributes as $attributeId => $attributeValue) {
            $attributeCode = $this->arrays->getValue(
                $config,
                sprintf(
                    'attributes:%d:code',
                    $attributeId
                ),
                []
            );

            $preselectedAttributes[ $attributeCode ] = $attributeValue;
        }

        return $preselectedAttributes;
    }
}
