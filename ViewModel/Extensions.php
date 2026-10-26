<?php
declare(strict_types=1);

namespace FreeMagento\Base\ViewModel;

use FreeMagento\Base\Api\UpdateCheckerInterface;
use FreeMagento\Base\Model\Config;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Extensions implements ArgumentInterface
{
    public function __construct(
        private readonly UpdateCheckerInterface $updateChecker,
        private readonly Config $config,
        private readonly UrlInterface $url
    ) {
    }

    public function getRows(): array
    {
        return $this->updateChecker->check();
    }

    public function getLastCheckedAt(): ?int
    {
        return $this->updateChecker->getLastCheckedAt();
    }

    public function isUpdateCheckEnabled(): bool
    {
        return $this->config->isUpdateCheckEnabled();
    }

    public function hasLicenceKey(): bool
    {
        return $this->config->getLicenceKey() !== '';
    }

    public function getCheckUrl(): string
    {
        return $this->url->getUrl('freemagento/extensions/check');
    }

    public function getSettingsUrl(): string
    {
        return $this->url->getUrl('adminhtml/system_config/edit', ['section' => 'freemagento_base']);
    }
}
