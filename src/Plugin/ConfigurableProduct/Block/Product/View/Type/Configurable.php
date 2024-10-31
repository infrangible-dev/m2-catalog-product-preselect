<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPreselect\Plugin\ConfigurableProduct\Block\Product\View\Type;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPreselect\Helper\Data;
use Infrangible\Core\Helper\Stores;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Configurable
{
    /** @var Json */
    protected $json;

    /** @var Stores */
    protected $storeHelper;

    /** @var Data */
    protected $helper;

    /** @var Arrays */
    protected $arrays;

    /** @var Variables */
    protected $variables;

    public function __construct(Json $json, Stores $storeHelper, Data $helper, Arrays $arrays, Variables $variables)
    {
        $this->json = $json;
        $this->storeHelper = $storeHelper;
        $this->helper = $helper;
        $this->arrays = $arrays;
        $this->variables = $variables;
    }

    public function afterGetJsonConfig(
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject,
        string $result
    ): string {
        $config = $this->json->decode($result);

        $currentProduct = $subject->getProduct();

        if (! $currentProduct->getData('preconfigured_values')) {
            $site =
                $subject instanceof \Magento\Swatches\Block\Product\Renderer\Listing\Configurable ? 'listing' : 'page';

            $enabled = $this->storeHelper->getStoreConfigFlag(
                sprintf(
                    'infrangible_catalogproductpreselect/%s/enable',
                    $site
                )
            );

            $mode = $this->storeHelper->getStoreConfig(
                sprintf(
                    'infrangible_catalogproductpreselect/%s/mode',
                    $site
                )
            );

            $config[ 'preselect' ] = [
                'enable' => $enabled,
                'mode'   => $mode
            ];

            if ($enabled) {
                $preselectedProductId = $this->helper->identifyPreselectedProduct(
                    $config,
                    $mode
                );

                $preselectedAttributes = $preselectedProductId ? $this->helper->getPreselectedAttributes(
                    $config,
                    $preselectedProductId
                ) : [];

                if ($preselectedAttributes) {
                    $config[ 'defaultValues' ][ 'all' ][ 'defaultValues' ] = $preselectedAttributes;
                }

                $attributes = $this->arrays->getValue(
                    $config,
                    'attributes',
                    []
                );

                $attributeOptionIds = [];

                try {
                    foreach ($attributes as $attributeId => $attributeData) {
                        $attributeId = $this->variables->intValue($attributeId);

                        $attributeOptions = $this->arrays->getValue(
                            $attributeData,
                            'options',
                            []
                        );

                        foreach ($attributeOptions as $attributeOption) {
                            $attributeOptionId = $this->variables->intValue(
                                $this->arrays->getValue(
                                    $attributeOption,
                                    'id'
                                )
                            );

                            $attributeOptionIds[ $attributeId ][] = $attributeOptionId;
                        }
                    }
                } catch (\Exception $exception) {
                }

                $attributeOptionCombinations = $this->getCombinations($attributeOptionIds);

                $attributeOptionCombinationsAttributeCodes = [];

                foreach ($attributeOptionCombinations as $attributeOptionCombination) {
                    $preselectedProductId = $this->helper->identifyPreselectedProduct(
                        $config,
                        $mode,
                        $attributeOptionCombination
                    );

                    $preselectedAttributes = $preselectedProductId ? $this->helper->getPreselectedAttributes(
                        $config,
                        $preselectedProductId
                    ) : [];

                    if ($preselectedAttributes) {
                        $defaultValueKey = ['defaultValues'];
                        $attributeOptionCombinationAttributeCodes = [];

                        foreach ($attributeOptionCombination as $attributeId => $attributeOptionId) {
                            $attributeKey = $this->arrays->getValue(
                                $config,
                                sprintf(
                                    'attributes:%d:%s',
                                    $attributeId,
                                    $this->getDefaultValuesAttributeKey()
                                )
                            );

                            $defaultValueKey[] = $attributeKey;
                            $defaultValueKey[] = $attributeOptionId;

                            $attributeCode = $this->arrays->getValue(
                                $config,
                                sprintf(
                                    'attributes:%d:%s',
                                    $attributeId,
                                    $this->getDefaultValuesAttributeKey()
                                )
                            );

                            $attributeOptionCombinationAttributeCodes[] = $attributeCode;
                        }

                        $defaultValueKey[] = 'defaultValues';

                        $config = $this->arrays->addDeepValue(
                            $config,
                            $defaultValueKey,
                            $preselectedAttributes
                        );

                        $attributeOptionCombinationsAttributeCodes[] = $attributeOptionCombinationAttributeCodes;
                    }
                }

                $attributeOptionCombinationsAttributeCodes = array_intersect_key(
                    $attributeOptionCombinationsAttributeCodes,
                    array_unique(
                        array_map(
                            'serialize',
                            $attributeOptionCombinationsAttributeCodes
                        )
                    )
                );

                usort(
                    $attributeOptionCombinationsAttributeCodes,
                    function (array $a, array $b) {
                        return count($a) > count($b) ? -1 : 1;
                    }
                );

                $config[ 'preselect' ][ 'attributeCombinations' ] = $attributeOptionCombinationsAttributeCodes;
            }
        } else {
            $config[ 'preselect' ] = [
                'enable' => false
            ];
        }

        return $this->json->encode($config);
    }

    public function getCombinations(array $attributeOptionIds): array
    {
        $result = [[]];

        foreach ($attributeOptionIds as $attributeId => $attributeOptions) {
            $tmp = [];

            foreach ($result as $resultItem) {
                if (! empty($resultItem)) {
                    $tmp[] = $resultItem;
                }
                foreach ($attributeOptions as $attributeOptionId) {
                    if (! empty($resultItem)) {
                        $tmp[] = [$attributeId => $attributeOptionId];
                    }
                    $tmp[] = $resultItem + [$attributeId => $attributeOptionId];
                }
            }

            $result = $tmp;
        }

        return array_intersect_key(
            $result,
            array_unique(
                array_map(
                    'serialize',
                    $result
                )
            )
        );
    }

    public function getDefaultValuesAttributeKey(): string
    {
        return 'code';
    }
}
