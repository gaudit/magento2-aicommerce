# Gaudit_AiCommerce

> Open-source AI Commerce module for Magento 2 / MageOS — turn your store into a conversational agent.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/gaudit/magento2-aicommerce.svg)](https://packagist.org/packages/gaudit/magento2-aicommerce)

**Drop-in module.** No extra services required. PHP only. Plug your LLM keys, pick channels, ship.

## What it does

Exposes your Magento catalog, cart, and checkout as **tools** that AI agents can call. Out of the box:

- **LLMs:** Claude (Anthropic), OpenAI, Gemini — pluggable via `LlmClientInterface`
- **Channels:** Telegram, WhatsApp Cloud API, [Evolution API](https://github.com/EvolutionAPI/evolution-api) (open-source WhatsApp gateway), REST
- **Open protocols:**
  - **MCP** ([Model Context Protocol](https://modelcontextprotocol.io)) — your store becomes an MCP server consumable by Claude Desktop, IDEs, any MCP client
  - **ACP** ([Agentic Commerce Protocol](https://openai.com/index/buy-it-in-chatgpt/)) — checkout sessions for ChatGPT and any ACP-compatible agent
  - **UCP** ([Universal Commerce Protocol](https://developers.google.com/merchant/ucp/guides)) — Gemini and Google Shopping integration (skeleton; spec being implemented)
- **Built-in tools:** search products, get product details, list categories, add to cart, get order status — extend via DI

## Install

```bash
composer require gaudit/magento2-aicommerce
bin/magento module:enable Gaudit_AiCommerce
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Configure

Admin → **Stores → Configuration → Gaudit → AI Commerce**

Minimum config to get started:

1. **LLM Provider:** Anthropic, OpenAI, or Gemini
2. **API Key:** your provider key (encrypted at rest)
3. **Channels:** enable Telegram, WhatsApp, or MCP

## Quick test

```bash
# Verify LLM connectivity
bin/magento aicommerce:test-llm "say hello in pt-BR"

# List registered tools
bin/magento aicommerce:list-tools

# Test MCP endpoint
bin/magento aicommerce:test-mcp
```

## Connect Claude Desktop via MCP

Add to your Claude Desktop config (`~/Library/Application Support/Claude/claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "my-store": {
      "url": "https://your-store.com/aicommerce/mcp"
    }
  }
}
```

Restart Claude Desktop. Ask: *"What products do you have under R$100?"* — Claude calls `search_products` against your live catalog.

## Channels at a glance

| Channel | Endpoint | Requires |
|---|---|---|
| Telegram | `POST /aicommerce/webhook/telegram` | Bot token from @BotFather |
| WhatsApp Cloud (official) | `POST /aicommerce/webhook/meta` | Meta Business app + phone number |
| WhatsApp via Evolution API | `POST /aicommerce/webhook/evolution` | Self-hosted [Evolution API](https://github.com/EvolutionAPI/evolution-api) instance |
| MCP server | `GET/POST /aicommerce/mcp` | Any MCP client (Claude Desktop, Cursor, etc) |
| ACP | `POST /aicommerce/acp/checkoutSessions` | OpenAI ChatGPT / any ACP agent (v1.0 will alias `/v1/checkout_sessions` per spec) |
| REST direct | `POST /aicommerce/chat` | Your X-API-Key |

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) for the full picture. Short version:

```
┌──────────────┐   webhook   ┌─────────────────┐   tool calls   ┌──────────┐
│  Channel     │ ──────────▶ │  Orchestrator   │ ─────────────▶ │ Magento  │
│  (TG/WA/MCP) │ ◀────────── │  ↕ LlmClient    │ ◀───── data ── │ (native) │
└──────────────┘  responses  └─────────────────┘                └──────────┘
```

## Extending

| You want to… | Read |
|---|---|
| Add a new AI tool | [docs/ADDING_A_TOOL.md](docs/ADDING_A_TOOL.md) |
| Add a new chat channel | [docs/ADDING_A_CHANNEL.md](docs/ADDING_A_CHANNEL.md) |
| Add a new LLM provider | [docs/ADDING_AN_LLM.md](docs/ADDING_AN_LLM.md) |
| Contribute | [CONTRIBUTING.md](CONTRIBUTING.md) |

## Status

**v0.1 — Alpha.** Public preview release.

| Feature | State |
|---|---|
| Module skeleton, DI, admin config | ✅ |
| Claude (Anthropic) LLM client | ✅ |
| OpenAI / Gemini LLM clients | 🟡 stub |
| `search_products` tool | ✅ |
| Other tools (cart, order, etc) | 🟡 stub |
| MCP server (HTTP+JSON-RPC) | ✅ |
| Telegram channel | 🟡 functional skeleton |
| Evolution API channel | 🟡 functional skeleton |
| Meta Cloud channel | 🟡 functional skeleton |
| ACP endpoints | 🟡 skeleton |
| UCP integration | 🔴 spec analysis |

## License

MIT — see [LICENSE](LICENSE).

## Author

[Wilker Gaudêncio](https://github.com/wilkergaudencio) — built on top of years of MageOS/Magento work.

---

*Presented at* [your-event-here] *— 2026.*
