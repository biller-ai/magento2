<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Config\System;

use Biller\Connect\Api\Config\System\ConnectionInterface;

/**
 * Credentials provider class
 */
class ConnectionRepository extends SettingsRepository implements ConnectionInterface
{

    /**
     * @inheritDoc
     */
    public function getCredentials(?int $storeId = null, ?bool $forceSandbox = null): array
    {
        $isSandBox = $forceSandbox === null ? $this->isSandbox($storeId) : $forceSandbox;
        return [
            'webshop_uid' => $this->getWebshopUid($storeId),
            "username" => $this->getUsername($storeId, $isSandBox),
            "password" => $this->getPassword($storeId, $isSandBox),
        ];
    }

    /**
     * @inheritDoc
     */
    public function isSandbox(?int $storeId = null): bool
    {
        return $this->getStoreValue(self::BILLER_MODE, $storeId) == 'sandbox';
    }

    /**
     * @inheritDoc
     */
    public function getWebshopUid(?int $storeId = null): string
    {
        return (string)$this->getStoreValue(self::BILLER_WEBSHOP_UID, $storeId);
    }

    /**
     * @param int|null $storeId
     * @param false $isSandBox
     *
     * @return string
     */
    private function getUserName(?int $storeId = null, $isSandBox = false): string
    {
        $path = $isSandBox ? self::BILLER_SANDBOX_USERNAME : self::BILLER_LIVE_USERNAME;
        return (string)$this->getStoreValue($path, $storeId);
    }

    /**
     * @param int|null $storeId
     * @param $isSandBox
     *
     * @return string
     */
    private function getPassword(?int $storeId = null, $isSandBox = false): string
    {
        $path = $isSandBox ? self::BILLER_SANDBOX_PASSWORD : self::BILLER_LIVE_PASSWORD;
        if ($value = $this->getStoreValue($path, $storeId)) {
            return $this->encryptor->decrypt($value);
        }

        return '';
    }
}
