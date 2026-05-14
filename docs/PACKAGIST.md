# Publishing to Packagist

Run once, after the first public release. Takes ~3 minutes.

## 1. Submit the package

1. Log into [packagist.org](https://packagist.org)
2. Top menu → **Submit**
3. Repository URL: `https://github.com/gaudit/magento2-aicommerce`
4. **Check**, then **Submit**

Packagist will index the repo, detect MIT license + Magento module type, and create the package page at `packagist.org/packages/gaudit/magento2-aicommerce`.

## 2. Set up auto-update on push

By default Packagist only re-crawls once per day. To get instant updates:

- Profile → **Show API token** → copy it
- In the GitHub repo → **Settings → Secrets and variables → Actions** → add:
  - `PACKAGIST_USERNAME` = your Packagist username
  - `PACKAGIST_TOKEN` = the API token from above

The release workflow (`.github/workflows/release.yml`) will hit the Packagist update endpoint on every tag push.

Alternative: add a GitHub webhook directly (Packagist also documents this on each package page → **Settings → Update package**).

## 3. Verify

```bash
composer search gaudit/magento2-aicommerce
# expected: package found
```

## After publish, install becomes:

```bash
composer require gaudit/magento2-aicommerce:dev-main   # bleeding edge
composer require gaudit/magento2-aicommerce:^0.1.0     # stable when v0.1.0 ships
```
