<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Config;

use Biller\Connect\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Config repository class
 */
class Repository extends System\ConnectionRepository implements ConfigRepositoryInterface
{

    /**
     * {@inheritDoc}
     */
    public function getExtensionVersion(): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_VERSION);
    }

    /**
     * {@inheritDoc}
     */
    public function getMagentoVersion(): string
    {
        return $this->metadata->getVersion();
    }

    /**
     * @inheritDoc
     */
    public function getExtensionCode(): string
    {
        return self::EXTENSION_CODE;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag(self::BILLER_ENABLE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getStore(): StoreInterface
    {
        try {
            return $this->storeManager->getStore();
        } catch (\Exception $e) {
            if ($store = $this->storeManager->getDefaultStoreView()) {
                return $store;
            }
        }
        $stores = $this->storeManager->getStores();
        return reset($stores);
    }

    /**
     * @inheritDoc
     */
    public function getBaseUrl(int $storeId = null): string
    {

        try {
            $baseUrl = $this->storeManager->getStore($storeId)->getBaseUrl();
            if ($authorizationData = $this->getAutorization($storeId)) {
                $authorization = sprintf('%s:%s@', $authorizationData['username'], $authorizationData['password']);
                $baseUrl = str_replace('https://', 'https://' . $authorization, $baseUrl);
                $baseUrl = str_replace('http://', 'http://' . $authorization, $baseUrl);
            }

            return $baseUrl;
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    public function getSupportLink(): string
    {
        return '#';
    }

    /**
     * @inheritDoc
     */
    public function getStoreLocale(int $storeId): string
    {
        return (string)$this->getStoreValue(self::GENERAL_LOCALE_CODE, $storeId);
    }
}
