<?php
declare(strict_types=1);

namespace FreeMagento\Base\Api;

/**
 * Lists the FreeMagento packages present in this installation.
 */
interface ModuleRegistryInterface
{
    public const VENDOR_PREFIX = 'FreeMagento_';

    /**
     * @return array<string, array{module: string, package: string, version: string, enabled: bool, path: string}>
     *         keyed by module name (e.g. FreeMagento_Base)
     */
    public function getModules(): array;

    /** Installed version of one module ('' when unknown). */
    public function getVersion(string $moduleName): string;
}
