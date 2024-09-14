<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPreselect\Plugin\ConfigurableProduct\Block\Product\View\Type;

use FeWeDev\Base\Json;
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

    public function __construct(Json $json, Stores $storeHelper)
    {
        $this->json = $json;
        $this->storeHelper = $storeHelper;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function afterGetJsonConfig(
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject,
        string $result
    ): string {
        $config = $this->json->decode($result);

        $config[ 'preselect' ] = [
            'enable' => $this->storeHelper->getStoreConfigFlag('infrangible_catalogproductpreselect/page/enable'),
            'mode'   => $this->storeHelper->getStoreConfig('infrangible_catalogproductpreselect/page/mode')
        ];

        return $this->json->encode($config);
    }
}
