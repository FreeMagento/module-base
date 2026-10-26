<?php
declare(strict_types=1);

namespace FreeMagento\Base\Model;

use FreeMagento\Base\Api\ModuleRegistryInterface;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Discovers FreeMagento_* modules and reads their version from the module's own composer.json
 * (works for app/code installs and for vendor/ installs alike - no dependency on composer's installed.json).
 */
class ModuleRegistry implements ModuleRegistryInterface
{
    /** @var array<string, array>|null */
    private ?array $modules = null;

    public function __construct(
        private readonly FullModuleList $fullModuleList,
        private readonly ModuleManager $moduleManager,
        private readonly ModuleDirReader $dirReader,
        private readonly FileDriver $file,
        private readonly Json $json
    ) {
    }

    public function getModules(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }
        $this->modules = [];
        foreach ($this->fullModuleList->getNames() as $name) {
            if (!str_starts_with($name, self::VENDOR_PREFIX)) {
                continue;
            }
            [$package, $version, $path] = $this->readComposer($name);
            $this->modules[$name] = [
                'module' => $name,
                'package' => $package,
                'version' => $version,
                'enabled' => $this->moduleManager->isEnabled($name),
                'path' => $path,
            ];
        }
        ksort($this->modules);
        return $this->modules;
    }

    public function getVersion(string $moduleName): string
    {
        return $this->getModules()[$moduleName]['version'] ?? '';
    }

    /** @return array{0: string, 1: string, 2: string} package name, version, module dir */
    private function readComposer(string $moduleName): array
    {
        try {
            $dir = $this->dirReader->getModuleDir('', $moduleName);
            $file = rtrim($dir, '/') . '/composer.json';
            if (!$this->file->isExists($file)) {
                return ['', '', $dir];
            }
            $data = $this->json->unserialize($this->file->fileGetContents($file));
            $package = (string)($data['name'] ?? '');
            // composer strips "version" from published packages; fall back to the vendor's own installed.json via the package name
            $version = (string)($data['version'] ?? $this->versionFromInstalledJson($package, $dir));
            return [$package, $version, $dir];
        } catch (\Throwable) {
            return ['', '', ''];
        }
    }

    private function versionFromInstalledJson(string $package, string $moduleDir): string
    {
        if ($package === '') {
            return '';
        }
        // vendor/freemagento/module-x/composer.json -> vendor/composer/installed.json
        $candidate = dirname($moduleDir, 2) . '/composer/installed.json';
        try {
            if (!$this->file->isExists($candidate)) {
                return '';
            }
            $installed = $this->json->unserialize($this->file->fileGetContents($candidate));
            $packages = $installed['packages'] ?? $installed;
            foreach ((array)$packages as $entry) {
                if (($entry['name'] ?? '') === $package) {
                    return ltrim((string)($entry['version'] ?? ''), 'v');
                }
            }
        } catch (\Throwable) {
        }
        return '';
    }
}
