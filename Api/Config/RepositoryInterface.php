<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Api\Config;

use Magento\Store\Api\Data\StoreInterface;

/**
 * Config repository interface
 */
interface RepositoryInterface extends System\ConnectionInterface
{

    public const EXTENSION_CODE = 'Biller_Connect';
    public const XML_PATH_EXTENSION_VERSION = 'biller_connect/general/version';
    public const MODULE_SUPPORT_LINK = 'https://www.magmodules.eu/help/%s';
    public const BILLER_ENABLE = 'payment/biller_gateway/active';
    public const GENERAL_LOCALE_CODE = 'general/locale/code';

    /**
     * Get extension version
     *
     * @return string
     */
    public function getExtensionVersion(): string;

    /**
     * Get extension code
     *
     * @return string
     */
    public function getExtensionCode(): string;

    /**
     * Get Magento Version
     *
     * @return string
     */
    public function getMagentoVersion(): string;

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool;

    /**
     * Get current store
     *
     * @return StoreInterface
     */
    public function getStore(): StoreInterface;

    /**
     * Get base url of the store
     *
     * @return string
     */
    public function getBaseUrl(int $storeId = null): string;

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink(): string;

    /**
     * Get store locale
     *
     * @param int $storeId
     * @return string
     */
    public function getStoreLocale(int $storeId): string;
}
