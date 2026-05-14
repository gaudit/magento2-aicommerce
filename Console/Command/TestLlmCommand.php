<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Console\Command;

use Gaudit\AiCommerce\Model\Orchestrator;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestLlmCommand extends Command
{
    public function __construct(
        private readonly Orchestrator $orchestrator,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('aicommerce:test-llm')
            ->setDescription('Send a one-shot prompt to the configured LLM and print the response.')
            ->addArgument('message', InputArgument::REQUIRED, 'The user message to send')
            ->addOption('store', null, InputOption::VALUE_REQUIRED, 'Store ID', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('adminhtml');
        } catch (\Throwable $e) {
            // already set
        }

        $storeId = (int)$input->getOption('store');
        $message = (string)$input->getArgument('message');

        $output->writeln("<info>Sending to LLM (store={$storeId})...</info>");
        $start = microtime(true);

        try {
            $result = $this->orchestrator->run($message, [], $storeId);
        } catch (\Throwable $e) {
            $output->writeln("<error>FAILED:</error> {$e->getMessage()}");
            return Command::FAILURE;
        }

        $elapsed = round((microtime(true) - $start) * 1000);

        $output->writeln('');
        $output->writeln('<comment>Response:</comment>');
        $output->writeln($result['text']);
        $output->writeln('');
        $output->writeln(sprintf(
            '<comment>%d turn(s), %d in / %d out tokens, %d ms</comment>',
            $result['turns'],
            $result['usage']['input_tokens'],
            $result['usage']['output_tokens'],
            $elapsed
        ));

        return Command::SUCCESS;
    }
}
