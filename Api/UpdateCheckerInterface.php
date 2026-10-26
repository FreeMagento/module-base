<?php
declare(strict_types=1);

namespace FreeMagento\Base\Api;

interface UpdateCheckerInterface
{
    /**
     * Refreshes the feed if it is due (or forced) and returns the per-module status.
     *
     * @return array<string, array{module: string, package: string, installed: string, latest: string, update_available: bool, security: bool, url: string, notes: string, licence: string}>
     */
    public function check(bool $force = false): array;

    /** Unix timestamp of the last successful feed fetch, or null. */
    public function getLastCheckedAt(): ?int;
}
