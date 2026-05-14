# Architecture

## One-paragraph version

`Gaudit_AiCommerce` lets the Magento store be addressed as a conversational agent. Inbound: any channel (Telegram, WhatsApp, MCP, REST) hands a message to the **Orchestrator**, which runs a tool-use loop against a configured **LLM** (Claude, OpenAI, Gemini). Tools are PHP classes that read/write Magento via native APIs (catalog, quote, sales). Outbound: the orchestrator's text response goes back through the originating channel. There is no separate runtime — everything lives inside the Magento PHP process.

## Diagram

```
                    ┌───────────────────────────────────────────────────────┐
                    │                  Magento (PHP)                        │
                    │                                                       │
 inbound webhook    │   Controller/Webhook/*         Controller/Mcp/Index   │
 (TG / WA / Meta) ─▶│       │                              │                │
                    │       ▼                              ▼                │
                    │   ChannelInterface              Protocol/Mcp/Server   │
                    │    parseInbound()                JSON-RPC handler     │
                    │       │                              │                │
                    │       └──────────────┬───────────────┘                │
                    │                      ▼                                │
                    │              ┌──────────────┐                         │
                    │              │ Orchestrator │                         │
                    │              └──────┬───────┘                         │
                    │      ┌──────────────┼──────────────┐                  │
                    │      ▼              ▼              ▼                  │
                    │  LlmClient    ToolRegistry    Conversation            │
                    │ (Claude/      ┌───┴────┐      Repository              │
                    │  OpenAI/      ▼        ▼      (MySQL)                 │
                    │  Gemini)   Tool…  …Tool…                              │
                    │              │                                        │
                    │              ▼                                        │
                    │       Magento\Catalog, \Quote, \Sales (native APIs)   │
                    │                                                       │
 outbound message   │   ChannelInterface.send()                             │
 (TG / WA / Meta) ◀─│                                                       │
                    └───────────────────────────────────────────────────────┘
```

## Request lifecycle (chat turn)

1. **Webhook arrives** → `Controller/Webhook/{channel}` (extends `AbstractWebhook`)
2. **Auth check** → `Channel::verifyRequest($body, $headers)` (HMAC / bearer / shared secret)
3. **Parse** → `Channel::parseInbound()` returns a normalized `InboundMessage`
4. **Load conversation** → `ConversationRepository::getOrCreate($channel, $external_id, $store_id)` + history
5. **Orchestrator loop** (max 8 turns):
   - serialize tools → call `LlmClient::chat(LlmRequest)`
   - if response has `tool_use` calls, execute each via `ToolRegistry::get($name)->execute($input)`
   - append tool results to history, repeat
   - otherwise, capture final text and exit loop
6. **Persist** assistant message → `ConversationRepository::appendMessage()`
7. **Send** → `Channel::send(OutboundMessage)`

## Extension points

| You want to add… | Where | How |
|---|---|---|
| A new AI tool | `Model/Tool/MyTool.php` | implement `ToolInterface`, register in your module's `di.xml` under `Gaudit\AiCommerce\Model\ToolRegistry` |
| A new chat channel | `Model/Channel/MyChannel.php` | implement `ChannelInterface`, register under `Gaudit\AiCommerce\Model\ChannelRegistry`, add `Controller/Webhook/My.php` extending `AbstractWebhook` |
| A new LLM provider | `Model/Llm/MyLlmClient.php` | implement `LlmClientInterface`, register under `Gaudit\AiCommerce\Model\Llm\LlmClientFactory.clients`, add option to `Model\Config\Source\LlmProvider` |
| A new open protocol | `Model/Protocol/Foo/*` + `Controller/Foo/*` | follow MCP server as a template |

## Why no Node.js runtime

We considered a hybrid (PHP module + Node sidecar) but rejected:

- **Distribution friction.** `composer require` is the Magento community norm. A required sidecar breaks one-command install.
- **State.** Chat turns are request/response — no long-lived connection required. PHP's request lifecycle fits perfectly.
- **Async.** Tool calls and LLM round-trips are sequential by design; PHP-FPM handles parallelism via concurrent requests.

A `BridgeChannel` exists for users with an existing Node bot — it proxies inbound through to the Node service, letting you migrate channel-by-channel.

## What's intentionally NOT in v0.1

- Streaming responses (LLM SSE): channels we ship (TG, WA) don't render partials anyway; deferred to v0.2 for MCP/REST.
- Vector search / embeddings: out of scope; use a sibling module if you want it.
- Admin grids for conversations: backend access is via raw tables for now; UI grid in v0.2.
- Multi-modal (image/voice in): scaffolded in `InboundMessage.attachments[]` but no tool consumes it yet.
