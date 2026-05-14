# Adding an LLM Provider

The Claude (Anthropic) client is the working reference. To add a new provider, implement `Gaudit\AiCommerce\Api\LlmClientInterface` and register it.

## Skeleton

`MyVendor/MyModule/Model/Llm/GroqClient.php`:

```php
<?php
declare(strict_types=1);
namespace MyVendor\MyModule\Model\Llm;

use Gaudit\AiCommerce\Api\Data\LlmRequestInterface;
use Gaudit\AiCommerce\Api\Data\LlmResponseInterface;
use Gaudit\AiCommerce\Api\LlmClientInterface;
use Gaudit\AiCommerce\Model\Config;
use Gaudit\AiCommerce\Model\Data\LlmResponse;
use Magento\Framework\HTTP\Client\Curl;

class GroqClient implements LlmClientInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Curl $curl
    ) {}

    public function getProviderId(): string { return 'groq'; }
    public function supportsToolUse(): bool { return true; }

    public function chat(LlmRequestInterface $request): LlmResponseInterface
    {
        $apiKey = $this->config->getLlmApiKey($request->getStoreId());
        $payload = [
            'model' => $request->getModel(),
            'messages' => $this->mapMessages($request->getMessages()),
            'tools' => $this->mapTools($request->getTools()),
            'max_tokens' => $request->getMaxTokens(),
            'temperature' => $request->getTemperature(),
        ];

        $this->curl->setHeaders([
            'authorization' => "Bearer {$apiKey}",
            'content-type' => 'application/json',
        ]);
        $this->curl->post('https://api.groq.com/openai/v1/chat/completions', json_encode($payload));
        $body = json_decode((string)$this->curl->getBody(), true);

        return new LlmResponse(
            text: $body['choices'][0]['message']['content'] ?? null,
            toolCalls: $this->extractToolCalls($body),
            stopReason: $body['choices'][0]['finish_reason'] ?? 'stop',
            usage: [
                'input_tokens' => $body['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $body['usage']['completion_tokens'] ?? 0,
            ],
        );
    }

    // mapMessages / mapTools / extractToolCalls = provider-specific
}
```

## Register

```xml
<type name="Gaudit\AiCommerce\Model\Llm\LlmClientFactory">
    <arguments>
        <argument name="clients" xsi:type="array">
            <item name="groq" xsi:type="object">MyVendor\MyModule\Model\Llm\GroqClient</item>
        </argument>
    </arguments>
</type>
```

Add the provider to the dropdown by extending `Gaudit\AiCommerce\Model\Config\Source\LlmProvider` (preference) or adding a plugin.

## Things to get right

- **Message translation** — `MessageInterface` has roles `user|assistant|tool`. The Claude client serializes `tool` as `role=user` with `tool_result` blocks (Anthropic convention). OpenAI uses `role=tool`. Match your provider.
- **Tool schemas** — all three big providers accept JSON Schema, but the wrapping differs (Anthropic: `tools`; OpenAI: `tools[].function`; Gemini: `tools.functionDeclarations`).
- **Stop reason mapping** — return `"tool_use"` when the orchestrator should execute tools, `"end_turn"` otherwise.
- **Token counting** — normalize to `{ input_tokens, output_tokens }` for budget tracking.
