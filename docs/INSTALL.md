# Installation

Three ways. Pick the one that matches your repo state.

## Option A — Composer (once published to Packagist)

```bash
composer require gaudit/magento2-aicommerce
bin/magento module:enable Gaudit_AiCommerce
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Option B — Composer from VCS (pre-Packagist)

In your project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/gaudit/magento2-aicommerce"
    }
  ],
  "require": {
    "gaudit/magento2-aicommerce": "dev-main"
  }
}
```

Then:

```bash
composer update gaudit/magento2-aicommerce
bin/magento module:enable Gaudit_AiCommerce
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Option C — Manual drop-in

```bash
cd <magento-root>/app/code
mkdir -p Gaudit
git clone https://github.com/gaudit/magento2-aicommerce.git Gaudit/AiCommerce
cd <magento-root>
bin/magento module:enable Gaudit_AiCommerce
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Post-install

See [SMOKE_TEST.md](SMOKE_TEST.md) for verification steps.

## Configuration

Admin → **Stores → Configuration → Gaudit → AI Commerce**.

Minimum to get the LLM responding:

| Section | Field | Value |
|---|---|---|
| General | Enable | Yes |
| LLM | Provider | Anthropic |
| LLM | API Key | `sk-ant-...` |
| LLM | Model | `claude-sonnet-4-6` |
| Channels | MCP Server | Yes |

Save. Test with `bin/magento aicommerce:test-llm "hello"`.
