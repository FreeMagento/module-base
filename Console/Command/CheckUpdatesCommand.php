<?php
declare(strict_types=1);

namespace FreeMagento\Base\Console\Command;

use FreeMagento\Base\Api\UpdateCheckerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckUpdatesCommand extends Command
{
    public function __construct(
        private readonly UpdateCheckerInterface $updateChecker,
        private readonly State $appState
    ) {
        parent::__construct('freemagento:updates:check');
    }

    protected function configure(): void
    {
        $this->setDescription('List installed FreeMagento packages and available updates')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Fetch the feed now instead of using the cached copy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Throwable) {
            // already set
        }
        $rows = [];
        foreach ($this->updateChecker->check((bool)$input->getOption('force')) as $row) {
            $rows[] = [
                $row['module'],
                $row['installed'] ?: '-',
                $row['latest'] ?: '-',
                $row['update_available'] ? ($row['security'] ? '<error>security update</error>' : '<comment>update</comment>') : '<info>ok</info>',
                $row['licence'] ?: '-',
            ];
        }
        (new Table($output))->setHeaders(['Module', 'Installed', 'Latest', 'Status', 'Licence'])->setRows($rows)->render();
        $at = $this->updateChecker->getLastCheckedAt();
        $output->writeln($at ? 'Feed checked ' . date('Y-m-d H:i', $at) : '<comment>Feed not reachable yet - showing installed versions only.</comment>');
        return Command::SUCCESS;
    }
}
