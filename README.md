# Multaparts Apparaatpagina Registry

Standalone, read-only WordPress/WooCommerce frontend for the canonical device registry. Version **0.1.1** renders device pages at `/onderdelen/{brand}/{type}/` without a WordPress page, taxonomy archive, importer, or another Multaparts frontend plugin.

## Requirements

- WordPress 6.2+, PHP 7.4+, WooCommerce active.
- Existing readable registry tables: `psa_device_models`, `psa_device_variants`, and `psa_product_device_links`, with the site's WordPress table prefix.

The plugin never creates, repairs, migrates, indexes, or writes to those tables. It deliberately has no persistent application cache.

## Behaviour

- Public requests include published products only. A model without visible products is a normal 404.
- Authorized administrators can explicitly request `?mapr_preview=1`. Private products additionally require `read_post`; preview responses are private/no-store and noindex/nofollow.
- `?soort-onderdeel={slug}` filters the already bounded registry result; `?sorteer=price-asc|price-desc|name` sorts it.
- Canonicals always point to the clean model URL.
- Product families are read through the WooCommerce product-attribute API from the local or global attribute named `Soort onderdeel` (`pa_soort-onderdeel`). Labels that differ only in case normalize to one family; products without that explicit attribute remain in “Alle onderdelen” only.

## Architecture

The request pipeline is route → exact canonical model-key lookup → grouped links query → known-variants query → bounded WooCommerce hydration → family/scope presentation → template. Infrastructure contains the only knowledge of the current physical `psa_*` table names. Routing depends on the replaceable `Model_Route_Resolver` interface, so alias routing can be introduced without changing the application or view.

No compatibility JSON, legacy compatibility taxonomy, all-product scan, or per-product registry query exists. Assets and SEO hooks act only after a valid device-page view has been built.

## Install

Install the release ZIP in WordPress and activate it. Activation and deactivation are the only times rewrite rules are flushed. Permalinks must otherwise be functional normally.

## Extension hooks

- `mapr_assurance_items`: centrally replace the four short assurance labels.
- `mapr_support_article_links`: provide at most two arrays containing `label` and `url`.

## Development

Run the dependency-free checks:

```bash
php tests/run.php
find . -name '*.php' -not -path './dist/*' -print0 | xargs -0 -n1 php -l
```

Build an installable artifact from the repository parent:

```bash
./build-zip.sh
```

Live/staging acceptance: published content is visible at `/onderdelen/zanker/at5000/`; while all three acceptance products remain private, only an authorized administrator using `/onderdelen/zanker/at5000/?mapr_preview=1` can see them.
