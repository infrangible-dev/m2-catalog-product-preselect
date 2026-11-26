# Infrangible Catalog Product Preselect

Preselects a variant of configurable products on the product page and in product lists, so that shoppers see a
complete product with its price instead of unselected options: the first variant, the cheapest or the most expensive
one.

## How it works

- The plugin on `Magento\ConfigurableProduct\Block\Product\View\Type\Configurable::getJsonConfig()` finds the variant
  by the final prices of the variants in the configuration of the options and adds it as default values.
- Options with swatches are selected by the mixin `Infrangible_CatalogProductPreselect/js/swatch-renderer-preselect`
  of Magento's swatch renderer, on the product page and in product lists. Options that the page address selects
  already (for example `#93=60` for a color) are kept, and the variant is chosen among the variants with these
  options: with "Lowest Price" and a color in the address, the cheapest variant of this color.
- Options shown as dropdown lists (attributes without swatches) are selected by Magento itself from the default
  values, on the product page. An option in the page address replaces the preselected one of its attribute.
- When a shopper edits a product from the cart, the options chosen there are kept and nothing is preselected.

## Configuration

Stores > Configuration > Infrangible Solutions > Product Preselect, separately for **Listing** (product lists) and
**Detail Page**:

- **Enable**: preselects a variant (default: no).
- **Mode**: **First Product** (default), **Lowest Price** or **Highest Price**.

The settings can be changed per website and store view.

For Hyvä themes, the module `infrangible/m2-catalog-product-preselect-hyva` adds the preselection on product pages.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.6 or newer with the Luma theme or a theme based on it
- PHP 8.1 or newer

## Installation

```bash
composer require infrangible/m2-catalog-product-preselect
bin/magento module:enable Infrangible_CatalogProductPreselect
bin/magento setup:upgrade
```

In production mode, also run `bin/magento setup:di:compile` and `bin/magento setup:static-content:deploy`.

## License

Infrangible Catalog Product Preselect is licensed under the MIT License - see the LICENSE file for details.
