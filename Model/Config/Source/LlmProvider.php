<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LlmProvider implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'anthropic', 'label' => __('Anthropic (Claude)')],
            ['value' => 'openai', 'label' => __('OpenAI (GPT)')],
            ['value' => 'gemini', 'label' => __('Google (Gemini)')],
        ];
    }
}
