# Adding a Tool

A *tool* is a callable function the LLM can decide to invoke. Tools read/write Magento state on the LLM's behalf.

## Minimal example

`MyVendor/MyModule/Model/Tool/SayHelloTool.php`:

```php
<?php
declare(strict_types=1);
namespace MyVendor\MyModule\Model\Tool;

use Gaudit\AiCommerce\Model\Tool\AbstractTool;

class SayHelloTool extends AbstractTool
{
    public function getName(): string { return 'say_hello'; }
    public function getDescription(): string { return 'Greet the customer by name.'; }
    public function getInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => ['name' => ['type' => 'string']],
            'required' => ['name'],
        ];
    }
    public function execute(array $input): array {
        return ['greeting' => "Hello, {$input['name']}!"];
    }
}
```

## Register in your module's `etc/di.xml`

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="Gaudit\AiCommerce\Model\ToolRegistry">
        <arguments>
            <argument name="tools" xsi:type="array">
                <item name="say_hello" xsi:type="object">MyVendor\MyModule\Model\Tool\SayHelloTool</item>
            </argument>
        </arguments>
    </type>
</config>
```

After `bin/magento setup:upgrade`, the tool is:
- Auto-discovered by the orchestrator
- Auto-listed in MCP `tools/list`
- Auto-shown in `bin/magento aicommerce:list-tools`

No central list to edit — that's the design.

## Best practices

- **Names are snake_case** and globally unique. Prefix with your vendor if generic (`mybrand_send_quote`).
- **Descriptions are read by the LLM**. Be specific — say *when* the tool should be called, not just *what* it does.
- **Input schema is JSON Schema**. Same format Anthropic / OpenAI / Gemini accept. Mark `required` fields explicitly.
- **Return JSON-serializable data**. Strings get printed verbatim; arrays/objects are JSON-encoded.
- **Throw** `Gaudit\AiCommerce\Exception\ToolExecutionException` on validation/business errors — the orchestrator surfaces these to the LLM, which usually retries gracefully.
- **Gate by store** in `isEnabled(?int $storeId)` when the tool only makes sense in some contexts.
- **Don't perform destructive ops without confirmation** — let the LLM negotiate that with the user; design tools to be idempotent where possible.
