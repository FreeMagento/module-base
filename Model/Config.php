<?php
declare(strict_types=1);

namespace FreeMagento\Base\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Config
{
    private const PATH = 'freemagento_base/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function isUpdateCheckEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH . 'updates/enabled');
    }

    public function shouldNotifyAdmin(): bool
    {
        return $this->isUpdateCheckEnabled() && $this->scopeConfig->isSetFlag(self::PATH . 'updates/notify_admin');
    }

    public function shouldSendInstanceReport(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH . 'updates/send_instance_report');
    }

    public function getFeedUrl(): string
    {
        return trim((string)$this->scopeConfig->getValue(self::PATH . 'updates/feed_url'));
    }

    public function getLicenceKey(): string
    {
        $raw = (string)$this->scopeConfig->getValue(self::PATH . 'account/licence_key');
        return $raw === '' ? '' : trim($this->encryptor->decrypt($raw));
    }
}
