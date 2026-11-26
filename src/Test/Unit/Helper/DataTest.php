<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPreselect\Test\Unit\Helper;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPreselect\Helper\Data;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests finding the variant to preselect.
 */
class DataTest extends TestCase
{
    /**
     * Returns modes, selected options and the variant that is preselected.
     *
     * @return array<string, array{string, array<int, int>, int|null}>
     */
    public static function preselectProvider(): array
    {
        return [
            'first'                     => ['first', [], 11],
            'lowest'                    => ['lowest', [], 13],
            'highest'                   => ['highest', [], 14],
            'lowest of the color'       => ['lowest', [93 => 60], 13],
            'highest of the color'      => ['highest', [93 => 60], 12],
            'lowest of color and size'  => ['lowest', [93 => 57, 144 => 169], 14],
            'option without variant'    => ['lowest', [93 => 99], null]
        ];
    }

    /**
     * Tests the variant to preselect.
     *
     * @dataProvider preselectProvider
     *
     * @param string $mode
     * @param array<int, int> $attributes
     * @param int|null $expected
     *
     * @return void
     */
    #[DataProvider('preselectProvider')]
    public function testIdentifyPreselectedProduct(string $mode, array $attributes, ?int $expected): void
    {
        $this->assertSame(
            $expected,
            $this->createHelper()->identifyPreselectedProduct(self::config(), $mode, $attributes)
        );
    }

    /**
     * A configuration without index or prices has no variant to preselect.
     *
     * @return void
     */
    public function testIdentifyWithoutVariants(): void
    {
        $this->assertNull($this->createHelper()->identifyPreselectedProduct([], 'first'));
    }

    /**
     * Tests the options of the variant by attribute code and by attribute ID.
     *
     * @return void
     */
    public function testPreselectedAttributes(): void
    {
        $helper = $this->createHelper();

        $this->assertSame(['color' => '60', 'size' => '168'], $helper->getPreselectedAttributes(self::config(), 12));
        $this->assertSame(['93' => '60', '144' => '168'], $helper->getPreselectedAttributeIds(self::config(), 12));
        $this->assertSame([], $helper->getPreselectedAttributeIds(self::config(), 99));
    }

    /**
     * Returns the JSON configuration of a product with four variants in two colors and three sizes.
     *
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        return [
            'attributes'   => [
                '93'  => ['code' => 'color', 'options' => [['id' => '57'], ['id' => '60']]],
                '144' => ['code' => 'size', 'options' => [['id' => '166'], ['id' => '168'], ['id' => '169']]]
            ],
            'index'        => [
                '11' => ['93' => '57', '144' => '166'],
                '12' => ['93' => '60', '144' => '168'],
                '13' => ['93' => '60', '144' => '166'],
                '14' => ['93' => '57', '144' => '169']
            ],
            'optionPrices' => [
                '11' => ['finalPrice' => ['amount' => 34]],
                '12' => ['finalPrice' => ['amount' => 25]],
                '13' => ['finalPrice' => ['amount' => 20]],
                '14' => ['finalPrice' => ['amount' => 50]]
            ]
        ];
    }

    /**
     * Returns the helper.
     *
     * @return Data
     */
    private function createHelper(): Data
    {
        return new Data(new Arrays(new Variables()), new Variables());
    }
}
