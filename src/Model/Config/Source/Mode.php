<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPreselect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Mode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'first', 'label' => __('First Product')],
            ['value' => 'lowest', 'label' => __('Lowest Price')],
            ['value' => 'highest', 'label' => __('Highest Price')]
        ];
    }
}
