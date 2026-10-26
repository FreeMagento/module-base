<?php
declare(strict_types=1);

namespace FreeMagento\Base\Cron;

use FreeMagento\Base\Api\UpdateCheckerInterface;
use Psr\Log\LoggerInterface;

class CheckUpdates
{
    public function __construct(
        private readonly UpdateCheckerInterface $updateChecker,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        try {
            $this->updateChecker->check();
        } catch (\Throwable $e) {
            // never let a feed problem fail the shared cron group
            $this->logger->notice('freemagento_base_check_updates: ' . $e->getMessage());
        }
    }
}
