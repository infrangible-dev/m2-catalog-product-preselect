# Changelog

## 3.0.0

### Breaking changes

- Requires Magento 2.4.6 or newer and PHP 8.1 or newer. Older versions of Magento and PHP 7.1 to 8.0 are no longer
  supported; use version 2.x for these.
- Requires `infrangible/m2-core` 5.0, `infrangible/m2-foundation` 3.0 and `fewedev/base` 2.4.3.
- The classes have native types and use constructor property promotion; `Helper\Data` takes `FeWeDev\Base\Variables`
  as well. `getCombinations()` of the plugin returns a list.

### Fixed

- Configurable products whose options are dropdown lists (attributes without swatches) got no preselection: the
  plugin replaced Magento's default values (option by attribute ID) with its own structure for the swatches. They are
  now added in Magento's format as well.
- With the preselection enabled but no mode saved (for example set with `bin/magento config:set`), product pages
  failed with a TypeError. The module now has default settings (disabled, "First Product"), and an unknown mode is
  treated as "First Product".

### Added

- `Helper\Data::getPreselectedAttributeIds()`: the options of a variant by attribute ID.
- Translations in 24 locales; the German label of "First Product" is grammatically correct now.
- Unit tests.
