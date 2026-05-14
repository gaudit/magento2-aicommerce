# Channels

Each channel is a transport for chat. Enable individually in admin → **Stores → Configuration → Gaudit → AI Commerce → Channels**.

## Telegram

**Webhook:** `POST https://your-store.com/aicommerce/webhook/telegram`

Setup:

1. Get a bot token from [@BotFather](https://t.me/BotFather)
2. Paste it into admin: *Telegram → Bot Token*
3. Optional: set a webhook secret in admin and pass `?secret_token=...` when registering
4. Register the webhook with Telegram:

```bash
curl -X POST "https://api.telegram.org/bot${BOT_TOKEN}/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-store.com/aicommerce/webhook/telegram",
    "secret_token": "your_secret"
  }'
```

## WhatsApp via Evolution API (recommended for getting started)

[Evolution API](https://github.com/EvolutionAPI/evolution-api) is an open-source WhatsApp gateway. It supports both Baileys (unofficial QR-code mode) and the official Cloud API as backends. Self-host it as a sidecar — see [docker-compose.example.yml](../docker-compose.example.yml).

**Webhook:** `POST https://your-store.com/aicommerce/webhook/evolution`

Setup:

1. Run Evolution API somewhere (see compose example)
2. Create an instance, scan QR (Baileys) or connect Cloud API
3. Configure Evolution to POST events to the webhook URL above with `apikey` header
4. In Magento admin: enable channel, set Evolution base URL, instance name, API key

## WhatsApp Cloud API (official Meta)

**Webhook:** `GET/POST https://your-store.com/aicommerce/webhook/meta`

Setup:

1. Create a Meta Business app + add WhatsApp product
2. Get Phone Number ID, Permanent Access Token, App Secret
3. Set a verify token (any string you choose)
4. Configure all four in admin
5. In Meta dashboard, set Webhook URL to the endpoint above, verify token matches
6. Subscribe to `messages` field

Meta will GET the URL once to verify, then POST messages going forward. The GET handshake is handled automatically.

## REST (custom UI / curl)

**Endpoint:** `POST /aicommerce/chat`

```bash
curl -X POST https://your-store.com/aicommerce/chat \
  -H "Content-Type: application/json" \
  -d '{"text": "what products do you have under R$100?", "conversation_id": "abc123"}'
```

Response includes the assistant text, token usage, and conversation_id (echoed or generated).

## Bridge (migration from Node bot)

If you have a co-running Node.js AI bot, set `aicommerce/bridge/node_bot_url` and toggle the bridge channel on. Calls to `POST /aicommerce/bridge` forward to `${url}/bot/chat`. Disable once PHP path reaches parity.

## MCP / ACP / UCP

See [PROTOCOLS.md](PROTOCOLS.md) — those are protocols, not chat channels in the traditional sense.
