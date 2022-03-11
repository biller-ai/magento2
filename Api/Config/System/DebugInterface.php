<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Api\Config\System;

/**
 * Debug group config repository interface
 */
interface DebugInterface
{

    /** Debug Group */
    public const XML_PATH_LOGGING = 'biller_connect/debug/logging';
    public const XML_PATH_DEBUG_AUTHORIZATION = 'biller_connect/debug/authorization';
    public const XML_PATH_DEBUG_USERNAME = 'biller_connect/debug/username';
    public const XML_PATH_DEBUG_PASSWORD = 'biller_connect/debug/password';

    /**
     * Check if we need to log debug calls
     *
     * @return bool
     */
    public function isDebugLoggingEnabled(): bool;

    /**
     * Returns array of username & password to append to webhook url
     *
     * @param int|null $storeId
     *
     * @return null|array
     */
    public function getAutorization(int $storeId = null): ?array;
}
