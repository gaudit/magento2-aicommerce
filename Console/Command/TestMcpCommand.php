<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Console\Command;

use Gaudit\AiCommerce\Model\Protocol\Mcp\Server;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestMcpCommand extends Command
{
    public function __construct(
        private readonly Server $server,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('aicommerce:test-mcp')
            ->setDescription('Round-trip the MCP server locally (initialize + tools/list).')
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

        $output->writeln('<info>→ initialize</info>');
        $init = $this->server->handle([
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [],
        ], $storeId);
        $output->writeln(json_encode($init, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $output->writeln('');
        $output->writeln('<info>→ tools/list</info>');
        $list = $this->server->handle([
            'jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list',
        ], $storeId);
        $output->writeln(json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}
