<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Console\Command;

use Gaudit\AiCommerce\Api\ToolRegistryInterface;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListToolsCommand extends Command
{
    public function __construct(
        private readonly ToolRegistryInterface $registry,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('aicommerce:list-tools')
            ->setDescription('List all AI tools registered in the module.')
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
        $tools = $this->registry->getAll($storeId);

        if (empty($tools)) {
            $output->writeln('<comment>No tools registered or all disabled.</comment>');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Name', 'Required params', 'Description']);
        foreach ($tools as $tool) {
            $required = implode(', ', (array)($tool->getInputSchema()['required'] ?? []));
            $table->addRow([
                $tool->getName(),
                $required ?: '—',
                substr($tool->getDescription(), 0, 80) . (strlen($tool->getDescription()) > 80 ? '…' : ''),
            ]);
        }
        $table->render();

        return Command::SUCCESS;
    }
}
