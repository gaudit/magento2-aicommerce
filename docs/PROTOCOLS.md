# Protocols

Three open protocols are first-class in this module. Each is independently toggleable in admin.

## MCP — Model Context Protocol (Anthropic)

**Endpoint:** `POST/GET /aicommerce/mcp`

**Transport:** streamable-http (JSON-RPC 2.0 over POST).

**Methods supported:**

| Method | Purpose |
|---|---|
| `initialize` | Capability handshake |
| `tools/list` | Return all registered tools as JSON Schema |
| `tools/call` | Execute a named tool with arguments |
| `ping` | Liveness check |

**Connecting Claude Desktop:**

Edit `~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "my-store": {
      "url": "https://your-store.com/aicommerce/mcp"
    }
  }
}
```

Restart Claude Desktop. Tools appear in the tool drawer.

**Spec:** https://modelcontextprotocol.io/specification/2025-03-26

## ACP — Agentic Commerce Protocol (OpenAI + Stripe)

**Endpoint:** `POST /aicommerce/acp/checkoutSessions` (v1.0 will alias `/v1/checkout_sessions` per spec via custom router)

**Purpose:** Lets ChatGPT (and any ACP client) create a checkout session against your store, get totals + shipping options, and confirm a purchase — all without leaving the chat surface.

**Status in v0.1:** Skeleton. The endpoint accepts requests and returns a structurally valid session, but does not yet create a real Magento quote. Roadmap:

1. Accept `items`, `buyer`, `shipping_address`
2. Create `Magento\Quote\Api\Data\CartInterface` via `GuestCartManagementInterface`
3. Compute totals and shipping options
4. Return session with `payment_provider_data` for confirmation
5. Honor `Idempotency-Key` header
6. Webhook out on order placed

**Spec:** OpenAI's ACP documentation (announced with ChatGPT's "Buy It" feature).

## UCP — Universal Commerce Protocol (Google / Gemini)

**Endpoint:** `GET/POST /aicommerce/ucp`

**Purpose:** Lets Gemini and Google Shopping discover your catalog and place agent-driven purchases.

**Status in v0.1:** Discovery stub. Spec at https://developers.google.com/merchant/ucp/guides will drive the full implementation. Roadmap:

1. Implement product catalog feed in UCP-prescribed schema
2. Implement agentic action endpoints (cart manipulation, checkout)
3. Wire authentication per Google's UCP auth model
