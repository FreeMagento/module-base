<?php
declare(strict_types=1);

namespace FreeMagento\Base\Model;

use FreeMagento\Base\Api\ModuleRegistryInterface;
use FreeMagento\Base\Api\UpdateCheckerInterface;
use FreeMagento\Base\Model\Feed\Client;
use Magento\AdminNotification\Model\InboxFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\Notification\MessageInterface;

class UpdateChecker implements UpdateCheckerInterface
{
    private const FLAG_FEED = 'freemagento_base_feed';          // ['time' => int, 'packages' => [...], 'licences' => [...]]
    private const FLAG_NOTIFIED = 'freemagento_base_notified';  // ['package@version' => time]
    private const TTL_OK = 6 * 3600;
    private const TTL_FAIL = 1800;

    public function __construct(
        private readonly Config $config,
        private readonly ModuleRegistryInterface $registry,
        private readonly Client $client,
        private readonly FlagManager $flagManager,
        private readonly InboxFactory $inboxFactory
    ) {
    }

    public function check(bool $force = false): array
    {
        $feed = (array)($this->flagManager->getFlagData(self::FLAG_FEED) ?: []);
        if ($this->config->isUpdateCheckEnabled() && ($force || $this->isStale($feed))) {
            $fresh = $this->client->fetch();
            $feed = $fresh
                ? ['time' => time(), 'ok' => true, 'packages' => $fresh['packages'], 'licences' => $fresh['licences'] ?? []]
                : ['time' => time(), 'ok' => false, 'packages' => $feed['packages'] ?? [], 'licences' => $feed['licences'] ?? []];
            $this->flagManager->saveFlag(self::FLAG_FEED, $feed);
        }
        $status = $this->buildStatus($feed);
        if ($this->config->shouldNotifyAdmin()) {
            $this->notify($status);
        }
        return $status;
    }

    public function getLastCheckedAt(): ?int
    {
        $feed = (array)($this->flagManager->getFlagData(self::FLAG_FEED) ?: []);
        return !empty($feed['ok']) ? (int)$feed['time'] : null;
    }

    private function isStale(array $feed): bool
    {
        if (empty($feed['time'])) {
            return true;
        }
        return time() - (int)$feed['time'] > (!empty($feed['ok']) ? self::TTL_OK : self::TTL_FAIL);
    }

    private function buildStatus(array $feed): array
    {
        $byPackage = (array)($feed['packages'] ?? []);
        $licences = (array)($feed['licences'] ?? []);
        $status = [];
        foreach ($this->registry->getModules() as $name => $module) {
            $remote = (array)($byPackage[$module['package']] ?? []);
            $latest = (string)($remote['latest'] ?? '');
            $installed = $module['version'];
            $status[$name] = [
                'module' => $name,
                'package' => $module['package'],
                'installed' => $installed,
                'latest' => $latest,
                'update_available' => $latest !== '' && $installed !== '' && version_compare($latest, $installed, '>'),
                'security' => (bool)($remote['security'] ?? false),
                'url' => (string)($remote['url'] ?? ''),
                'notes' => (string)($remote['notes'] ?? ''),
                'licence' => (string)($licences[$module['package']]['status'] ?? ''),   // '', active, expiring, expired, unlicensed
                'licence_until' => (string)($licences[$module['package']]['updates_until'] ?? ''),
            ];
        }
        return $status;
    }

    /** One admin-inbox message per package+version; expiring licences once per package+date. */
    private function notify(array $status): void
    {
        $notified = (array)($this->flagManager->getFlagData(self::FLAG_NOTIFIED) ?: []);
        $changed = false;
        foreach ($status as $row) {
            if ($row['update_available']) {
                $key = $row['package'] . '@' . $row['latest'];
                if (!isset($notified[$key])) {
                    $this->inbox(
                        $row['security'] ? MessageInterface::SEVERITY_CRITICAL : MessageInterface::SEVERITY_NOTICE,
                        sprintf('%s %s is available (installed: %s)', $row['package'], $row['latest'], $row['installed'] ?: '?'),
                        $row['notes'] ?: 'Update with composer update ' . $row['package'] . ' then bin/magento setup:upgrade.',
                        $row['url']
                    );
                    $notified[$key] = time();
                    $changed = true;
                }
            }
            if (in_array($row['licence'], ['expiring', 'expired'], true)) {
                $key = $row['package'] . '#licence@' . $row['licence'] . '@' . $row['licence_until'];
                if (!isset($notified[$key])) {
                    $this->inbox(
                        MessageInterface::SEVERITY_MAJOR,
                        $row['licence'] === 'expired'
                            ? sprintf('Updates for %s have expired', $row['package'])
                            : sprintf('Updates for %s expire on %s', $row['package'], $row['licence_until']),
                        'The extension keeps working. Renew to keep receiving updates and support.',
                        'https://freemagento.co.uk/customer/account/'
                    );
                    $notified[$key] = time();
                    $changed = true;
                }
            }
        }
        if ($changed) {
            $this->flagManager->saveFlag(self::FLAG_NOTIFIED, $notified);
        }
    }

    private function inbox(int $severity, string $title, string $description, string $url): void
    {
        // parse() inserts with the given severity and skips a message whose title+url already exist
        $this->inboxFactory->create()->parse([[
            'severity' => $severity,
            'date_added' => date('Y-m-d H:i:s'),
            'title' => $title,
            'description' => $description,
            'url' => $url,
        ]]);
    }
}
