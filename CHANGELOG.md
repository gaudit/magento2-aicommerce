# Changelog

## v0.1.0 — 2026-05 (initial public release)

- Module skeleton with DI, admin config, DB schema
- `LlmClientInterface` + working Claude (Anthropic) implementation
- `ToolInterface` + working `search_products` tool
- 4 stub tools: `get_product_details`, `list_categories`, `add_to_cart`, `get_order_status`
- `ChannelInterface` + skeletons for Telegram, Evolution API, WhatsApp Cloud (Meta), Bridge
- MCP server (HTTP + JSON-RPC 2.0) with `initialize`, `tools/list`, `tools/call`
- ACP endpoint skeleton (`/aicommerce/acp/v1/checkout_sessions`)
- UCP endpoint skeleton (`/aicommerce/ucp`)
- REST chat fallback (`/aicommerce/chat`)
- CLI: `aicommerce:test-llm`, `aicommerce:list-tools`, `aicommerce:test-mcp`, `aicommerce:test-channel`
- Health endpoint `GET /aicommerce/health` (no secrets exposed)
- `db_schema_whitelist.json` for Magento 2.3+ declarative schema
- i18n: en_US + pt_BR translation files
- CODE_OF_CONDUCT.md (Contributor Covenant v2.1)
- Release workflow: tag push creates GitHub Release + notifies Packagist
- MIT license
