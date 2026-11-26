<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPreselect\Test\Unit\Plugin\ConfigurableProduct\Block\Product\View\Type;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPreselect\Helper\Data;
use Infrangible\CatalogProductPreselect\Plugin\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Infrangible\CatalogProductPreselect\Test\Unit\Helper\DataTest;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableBlock;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests the preselection in the JSON configuration of configurable products.
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigurableTest extends TestCase
{
    /**
     * The preselected variant is added in the format of Magento's options (by attribute ID) and of the swatches (by
     * attribute code, also for every combination of options).
     *
     * @return void
     */
    public function testPreselection(): void
    {
        $config = $this->getConfig(true, 'lowest');

        $this->assertSame(true, $this->value($config, 'preselect', 'enable'));
        $this->assertSame('lowest', $this->value($config, 'preselect', 'mode'));
        $this->assertSame('60', $this->value($config, 'defaultValues', '93'));
        $this->assertSame('166', $this->value($config, 'defaultValues', '144'));
        $this->assertSame(
            ['color' => '60', 'size' => '166'],
            $this->value($config, 'defaultValues', 'all', 'defaultValues')
        );
        // the cheapest purple variant
        $this->assertSame(
            ['color' => '57', 'size' => '166'],
            $this->value($config, 'defaultValues', 'color', '57', 'defaultValues')
        );
        $this->assertSame(
            [['color', 'size'], ['color'], ['size']],
            $this->value($config, 'preselect', 'attributeCombinations')
        );
    }

    /**
     * An unknown or missing mode is the first one.
     *
     * @return void
     */
    public function testUnknownMode(): void
    {
        $config = $this->getConfig(true, null);

        $this->assertSame('first', $this->value($config, 'preselect', 'mode'));
        $this->assertSame(
            ['color' => '57', 'size' => '166'],
            $this->value($config, 'defaultValues', 'all', 'defaultValues')
        );
    }

    /**
     * Without preselection, the configuration has no default values.
     *
     * @return void
     */
    public function testDisabled(): void
    {
        $config = $this->getConfig(false, 'lowest');

        $this->assertSame(['enable' => false, 'mode' => 'lowest'], $this->value($config, 'preselect'));
        $this->assertArrayNotHasKey('defaultValues', $config);
    }

    /**
     * Products with preconfigured values (editing a cart item) keep them.
     *
     * @return void
     */
    public function testPreconfiguredProduct(): void
    {
        $config = $this->getConfig(true, 'lowest', true);

        $this->assertSame(['enable' => false], $this->value($config, 'preselect'));
    }

    /**
     * Tests the combinations of options.
     *
     * @return void
     */
    public function testCombinations(): void
    {
        $this->assertSame(
            [[1 => 10], [2 => 20], [1 => 10, 2 => 20], [1 => 11], [1 => 11, 2 => 20]],
            $this->createPlugin(false, 'first')->getCombinations([1 => [10, 11], 2 => [20]])
        );
    }

    /**
     * Returns a value of the configuration by the keys of the path to it, null if it is missing.
     *
     * @param array<string, mixed> $config
     * @param string ...$keys
     *
     * @return mixed
     */
    private function value(array $config, string ...$keys): mixed
    {
        $value = $config;

        foreach ($keys as $key) {
            if (! is_array($value) || ! array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[ $key ];
        }

        return $value;
    }

    /**
     * Returns the JSON configuration of the test product after the plugin, decoded.
     *
     * @param bool $enabled
     * @param string|null $mode
     * @param bool $preconfigured
     *
     * @return array<string, mixed>
     */
    private function getConfig(bool $enabled, ?string $mode, bool $preconfigured = false): array
    {
        $product = $this->createStub(Product::class);
        $product->method('getData')->willReturn($preconfigured ? ['super_attribute' => []] : null);

        $block = $this->createStub(ConfigurableBlock::class);
        $block->method('getProduct')->willReturn($product);

        $result = $this->createPlugin($enabled, $mode)->afterGetJsonConfig(
            $block,
            (string)json_encode(DataTest::config())
        );

        $config = json_decode($result, true);
        $this->assertIsArray($config);

        return $config;
    }

    /**
     * Returns the plugin with the configured preselection.
     *
     * @param bool $enabled
     * @param string|null $mode
     *
     * @return Configurable
     */
    private function createPlugin(bool $enabled, ?string $mode): Configurable
    {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStoreConfigFlag')->willReturn($enabled);
        $storeHelper->method('getStoreConfig')->willReturn($mode);

        $variables = new Variables();
        $arrays = new Arrays($variables);

        return new Configurable(new Json($variables), $storeHelper, new Data($arrays, $variables), $arrays, $variables);
    }
}
