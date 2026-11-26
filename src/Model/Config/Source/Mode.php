<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPreselect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

/**
 * The variant of a configurable product that is preselected.
 */
class Mode implements OptionSourceInterface
{
    /** the first variant */
    public const FIRST = 'first';

    /** the variant with the lowest final price */
    public const LOWEST = 'lowest';

    /** the variant with the highest final price */
    public const HIGHEST = 'highest';

    /** all modes, the first one is the default */
    public const MODES = [self::FIRST, self::LOWEST, self::HIGHEST];

    /**
     * Returns the modes with their labels.
     *
     * @return list<array{value: string, label: Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::FIRST, 'label' => __('First Product')],
            ['value' => self::LOWEST, 'label' => __('Lowest Price')],
            ['value' => self::HIGHEST, 'label' => __('Highest Price')]
        ];
    }
}
