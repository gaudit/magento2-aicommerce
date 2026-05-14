# Adding a Channel

A *channel* is a transport for chat (Telegram, Discord, custom widget, …). Adding one requires three pieces: the channel class, a webhook controller, and DI registration.

## 1. Channel class

`MyVendor/MyModule/Model/Channel/DiscordChannel.php`:

```php
<?php
declare(strict_types=1);
namespace MyVendor\MyModule\Model\Channel;

use Gaudit\AiCommerce\Api\Data\InboundMessageInterface;
use Gaudit\AiCommerce\Api\Data\OutboundMessageInterface;
use Gaudit\AiCommerce\Model\Channel\AbstractChannel;
use Gaudit\AiCommerce\Model\Data\InboundMessage;

class DiscordChannel extends AbstractChannel
{
    public function getChannelId(): string { return 'discord'; }
    public function verifyRequest(string $rawBody, array $headers): void { /* signature check */ }
    public function parseInbound(string $rawBody, array $headers): ?InboundMessageInterface {
        $event = json_decode($rawBody, true);
        // ... return new InboundMessage(...) or null to skip
    }
    public function send(OutboundMessageInterface $message): void {
        // ... HTTP POST back to Discord
    }
}
```

`AbstractChannel::isEnabled()` reads `aicommerce/channels/{channel_id}_enabled` — add the matching field to your module's `system.xml`.

## 2. Webhook controller

`MyVendor/MyModule/Controller/Webhook/Discord.php`:

```php
<?php
declare(strict_types=1);
namespace MyVendor\MyModule\Controller\Webhook;

use Gaudit\AiCommerce\Controller\Webhook\AbstractWebhook;

class Discord extends AbstractWebhook
{
    protected function getChannelId(): string { return 'discord'; }
}
```

Add a route in `etc/frontend/routes.xml`:

```xml
<route id="mymodule" frontName="mymodule">
    <module name="MyVendor_MyModule"/>
</route>
```

The webhook URL will be `https://your-store.com/mymodule/webhook/discord`.

## 3. DI registration

```xml
<type name="Gaudit\AiCommerce\Model\ChannelRegistry">
    <arguments>
        <argument name="channels" xsi:type="array">
            <item name="discord" xsi:type="object">MyVendor\MyModule\Model\Channel\DiscordChannel</item>
        </argument>
    </arguments>
</type>
```

## Done

The orchestrator and webhook handler are shared. You only wrote the channel-specific code.
