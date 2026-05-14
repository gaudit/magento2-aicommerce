<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Console\Command;

use Gaudit\AiCommerce\Model\ChannelRegistry;
use Gaudit\AiCommerce\Model\Data\OutboundMessage;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Send a one-shot test message via a channel without going through the LLM.
 *
 * Useful for verifying channel credentials/connectivity from the CLI before
 * pointing real users (or a demo audience) at the webhook.
 */
class TestChannelCommand extends Command
{
    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('aicommerce:test-channel')
            ->setDescription('Send a hardcoded text message via a channel to a target conversation/chat ID.')
            ->addArgument('channel', InputArgument::REQUIRED, 'Channel id (telegram, evolution, meta_cloud, bridge)')
            ->addArgument('target', InputArgument::REQUIRED, 'External conversation id (chat_id, phone, jid)')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Message text', 'Hello from Gaudit_AiCommerce — channel test ✓')
            ->addOption('store', null, InputOption::VALUE_REQUIRED, 'Store ID', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('adminhtml');
        } catch (\Throwable $e) {
            // already set
        }

        $channelId = (string)$input->getArgument('channel');
        $target = (string)$input->getArgument('target');
        $text = (string)$input->getOption('message');
        $storeId = (int)$input->getOption('store');

        $channel = $this->channels->get($channelId);
        if (!$channel) {
            $output->writeln("<error>Unknown channel: {$channelId}</error>");
            $output->writeln('Available: telegram, evolution, meta_cloud, bridge');
            return Command::FAILURE;
        }

        if (!$channel->isEnabled($storeId)) {
            $output->writeln("<error>Channel '{$channelId}' is disabled for store {$storeId}. Enable in admin.</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Sending via {$channelId} → {$target}</info>");

        try {
            $channel->send(OutboundMessage::text($channelId, $target, $text));
            $output->writeln('<info>✓ Message dispatched (no HTTP error from upstream).</info>');
            $output->writeln('<comment>Note: success here means the API returned 2xx. Check the channel itself to confirm delivery.</comment>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>FAILED:</error> {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
