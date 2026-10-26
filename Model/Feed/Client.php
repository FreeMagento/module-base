<?php
declare(strict_types=1);

namespace FreeMagento\Base\Model\Feed;

use FreeMagento\Base\Api\ModuleRegistryInterface;
use FreeMagento\Base\Model\Config;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

/**
 * Fetches https://freemagento.co.uk/feed/packages (JSON). Optionally posts the installed-package report so the
 * feed can flag security releases; otherwise a plain anonymous GET.
 *
 * Feed shape: {"generated": 1700000000, "packages": {"freemagento/module-x": {"module": "FreeMagento_X",
 *   "latest": "1.2.0", "url": "...", "notes": "...", "security": false, "min_magento": "2.4.6"}}, "licences": {...}}
 */
class Client
{
    private const TIMEOUT = 8;

    public function __construct(
        private readonly Config $config,
        private readonly ModuleRegistryInterface $registry,
        private readonly Curl $curl,
        private readonly Json $json,
        private readonly UrlInterface $url,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly LoggerInterface $logger
    ) {
    }

    /** @return array|null decoded feed, or null when it could not be fetched */
    public function fetch(): ?array
    {
        $feedUrl = $this->config->getFeedUrl();
        if ($feedUrl === '') {
            return null;
        }
        try {
            $this->curl->setTimeout(self::TIMEOUT);
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->addHeader('User-Agent', 'FreeMagento-Base/' . $this->registry->getVersion('FreeMagento_Base'));
            if ($this->config->shouldSendInstanceReport()) {
                $this->curl->addHeader('Content-Type', 'application/json');
                $this->curl->post($feedUrl, $this->json->serialize($this->buildReport()));
            } else {
                $this->curl->get($feedUrl);
            }
            if ($this->curl->getStatus() !== 200) {
                $this->logger->notice('FreeMagento feed returned HTTP ' . $this->curl->getStatus());
                return null;
            }
            $data = $this->json->unserialize($this->curl->getBody());
            return is_array($data) && isset($data['packages']) ? $data : null;
        } catch (\Throwable $e) {
            $this->logger->notice('FreeMagento feed unavailable: ' . $e->getMessage());
            return null;
        }
    }

    /** What the instance report contains - kept deliberately small and documented in system.xml. */
    public function buildReport(): array
    {
        $packages = [];
        foreach ($this->registry->getModules() as $module) {
            $packages[] = ['module' => $module['module'], 'package' => $module['package'], 'version' => $module['version'], 'enabled' => $module['enabled']];
        }
        $host = (string)parse_url((string)$this->url->getBaseUrl(['_scope' => Store::DEFAULT_STORE_ID]), PHP_URL_HOST);
        return [
            'domain' => $host,
            'magento' => $this->productMetadata->getVersion(),
            'edition' => $this->productMetadata->getEdition(),
            'php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            'licence_key' => $this->config->getLicenceKey(),
            'packages' => $packages,
        ];
    }
}
