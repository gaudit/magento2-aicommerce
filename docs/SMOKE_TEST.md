# Smoke Test — pre-flight checklist

Run these in order on a real Magento install. If any step fails, fix before the live demo.

## 1. Module installed and enabled

```bash
bin/magento module:status | grep Gaudit_AiCommerce
# expected: Module is enabled
```

## 2. Schema migrated

```bash
bin/magento setup:upgrade
bin/magento setup:db-schema:upgrade   # optional, redundant on most versions

# verify tables
bin/magento dev:query "SHOW TABLES LIKE 'gaudit_aicommerce_%'"
# expected:
#  gaudit_aicommerce_conversation
#  gaudit_aicommerce_message
#  gaudit_aicommerce_usage
```

## 3. DI compiled

```bash
bin/magento setup:di:compile
# expected: no errors, finishes cleanly
```

## 4. Admin config visible

Browser → `/<admin>/admin/system_config/edit/section/aicommerce`

Expected: form with sections General, LLM Provider, Channels.

Set at least:
- General → Enable: Yes
- LLM Provider → Provider: Anthropic; API Key: `sk-ant-...`; Model: `claude-sonnet-4-6`
- Channels → MCP Server: Yes

Save. Re-load to confirm persisted (obscure fields will show asterisks).

## 5. CLI: list-tools

```bash
bin/magento aicommerce:list-tools
# expected: table with 5 tools (search_products, get_product_details, list_categories, add_to_cart, get_order_status)
```

## 6. CLI: test-llm

```bash
bin/magento aicommerce:test-llm "Diga olá em pt-BR em uma frase curta."
# expected: assistant text returned, token counts shown
```

Failure modes:
- `Anthropic API key not configured` → step 4 not done or DI cache stale (`bin/magento cache:flush`)
- `Anthropic API returned HTTP 401` → invalid API key
- `Anthropic API returned HTTP 429` → rate-limited, wait a beat

## 7. CLI: test-mcp

```bash
bin/magento aicommerce:test-mcp
# expected: two JSON-RPC responses (initialize result + tools/list result)
```

## 8. HTTP: MCP endpoint

```bash
curl -X POST https://<your-store>/aicommerce/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
# expected: 200 with result.tools array
```

## 9. HTTP: chat endpoint

```bash
curl -X POST https://<your-store>/aicommerce/chat \
  -H "Content-Type: application/json" \
  -d '{"text":"que produtos vocês têm abaixo de R$100?"}'
# expected: 200 with text, conversation_id, usage
```

## 10. Claude Desktop

`~/Library/Application Support/Claude/claude_desktop_config.json`:
```json
{
  "mcpServers": {
    "demo-store": { "url": "https://<your-store>/aicommerce/mcp" }
  }
}
```

Restart Claude Desktop. Open a new chat. The 5 tools should appear in the tool drawer. Ask: *"what products do you have under R$100?"* — expect a `search_products` tool call followed by a natural-language response.

## Recovery if things go sideways

| Symptom | Fix |
|---|---|
| `setup:upgrade` complains about schema not whitelisted | already present: `etc/db_schema_whitelist.json`. If still failing, run `bin/magento setup:db-declaration:generate-whitelist --module-name=Gaudit_AiCommerce` |
| 404 on `/aicommerce/*` | `bin/magento cache:clean config full_page` |
| 500 on `/aicommerce/mcp` | tail `var/log/aicommerce.log` and `var/log/exception.log` |
| Tools list empty | check `etc/di.xml` registry not overridden; `bin/magento cache:clean` |
| Admin config not saving | check ACL: user must have `Gaudit_AiCommerce::config` permission (refresh permissions) |
