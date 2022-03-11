<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Config\System;

use Biller\Connect\Api\Config\System\DebugInterface;

/**
 * Debug provider class
 */
class DebugRepository extends BaseRepository implements DebugInterface
{

    /**
     * @inheritDoc
     */
    public function isDebugLoggingEnabled(): bool
    {
        return $this->isSetFlag(self::XML_PATH_LOGGING);
    }

    /**
     * @inheritDoc
     */
    public function getAutorization(?int $storeId = null): ?array
    {
        if (!$this->usesAuthorization($storeId)) {
            return null;
        }

        $username = $this->getStoreValue(self::XML_PATH_DEBUG_USERNAME, $storeId);
        $password = $this->getStoreValue(self::XML_PATH_DEBUG_PASSWORD, $storeId);

        if (empty($username) || empty($password)) {
            return null;
        }

        return [
            "username" => $username,
            "password" => $password,
        ];
    }

    /**
     * Check if we need to append authorization
     *
     * @param int|null $storeId
     * @return bool
     */
    private function usesAuthorization(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_DEBUG_AUTHORIZATION, $storeId);
    }
}
