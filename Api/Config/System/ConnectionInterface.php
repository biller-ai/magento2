<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Api\Config\System;

/**
 * Connection group config repository interface
 */
interface ConnectionInterface extends SettingsInterface
{

    /** Connection Group */
    public const BILLER_MODE = 'payment/biller_gateway/mode';
    public const BILLER_WEBSHOP_UID = 'payment/biller_gateway/webshop_uid';
    public const BILLER_SANDBOX_USERNAME = 'payment/biller_gateway/sandbox_username';
    public const BILLER_SANDBOX_PASSWORD = 'payment/biller_gateway/sandbox_password';
    public const BILLER_LIVE_USERNAME = 'payment/biller_gateway/username';
    public const BILLER_LIVE_PASSWORD = 'payment/biller_gateway/password';

    /**
     * Flag for sanbox mode
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isSandbox(?int $storeId = null): bool;

    /**
     * Get associated array of credentials
     *
     * @param int|null  $storeId
     * @param bool|null $forceSandbox
     *
     * @return array
     */
    public function getCredentials(?int $storeId = null, ?bool $forceSandbox = null): array;

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getWebshopUid(?int $storeId = null): string;
}
