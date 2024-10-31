<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPreselect\Plugin\ConfigurableProduct\Block\Product\View\Type;

use FeWeDev\Base\Json;
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

    public function __construct(Json $json, Stores $storeHelper, Data $helper)
    {
        $this->json = $json;
        $this->storeHelper = $storeHelper;
        $this->helper = $helper;
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
                    $config[ 'defaultValues' ] = $preselectedAttributes;
                }
            }
        } else {
            $config[ 'preselect' ] = [
                'enable' => false
            ];
        }

        return $this->json->encode($config);
    }
}
