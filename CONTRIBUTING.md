# Contributing

Thanks for considering a contribution. This is a young module — every fix, doc improvement, and new tool/channel/LLM helps.

## Quick start

```bash
git clone https://github.com/gaudit/magento2-aicommerce.git
cd magento2-aicommerce
composer install
```

Place the module inside a Magento 2 / MageOS 2 install at `app/code/Gaudit/AiCommerce/`, or `composer require gaudit/magento2-aicommerce` with a dev branch ref.

## What needs help (in priority order)

1. **OpenAI + Gemini LLM clients** — see `Model/Llm/OpenAiClient.php` and `GeminiClient.php`. The Claude client is the reference.
2. **`add_to_cart` tool full implementation** — wire `Magento\Quote\Api\GuestCartManagementInterface`.
3. **UCP integration** — implement per Google's spec at `developers.google.com/merchant/ucp/guides`.
4. **Admin grid for conversations** — `view/adminhtml/ui_component/aicommerce_conversation_listing.xml`.
5. **More channels** — Discord, Slack, WebChat widget.
6. **Streaming responses** for MCP and REST.

## Coding standards

- PHP 8.1+
- `magento/magento-coding-standard` (PSR-12 + Magento rules) — `vendor/bin/phpcs`
- `phpstan` level 8 — `vendor/bin/phpstan analyse`
- `phpunit` — `vendor/bin/phpunit Test/Unit`

## Pull request checklist

- [ ] Tests added/updated
- [ ] `phpcs` clean
- [ ] `phpstan` clean
- [ ] `CHANGELOG.md` updated under `## Unreleased`
- [ ] Docs updated if you changed public contract (interfaces, system.xml, endpoints)

## Adding a tool

See [docs/ADDING_A_TOOL.md](docs/ADDING_A_TOOL.md).

## Adding a channel

See [docs/ADDING_A_CHANNEL.md](docs/ADDING_A_CHANNEL.md).

## Adding an LLM

See [docs/ADDING_AN_LLM.md](docs/ADDING_AN_LLM.md).

## Reporting issues

GitHub Issues. Include:
- Magento / MageOS version
- PHP version
- Module version
- Steps to reproduce
- `var/log/aicommerce.log` snippet (mask any API keys!)

## License

By contributing, you agree your contributions will be licensed under [MIT](LICENSE).
