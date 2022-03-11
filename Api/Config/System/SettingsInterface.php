<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Api\Config\System;

/**
 * Settings group config repository interface
 */
interface SettingsInterface extends DebugInterface
{

    /** Settings Group */
    public const BILLER_USE_SEPARATE_HOUSENUMBER = 'biller_connect/settings/separate_housenumber';
    public const BILLER_PAYMENT_LINK_DURATION = 'biller_connect/settings/payment_link_duration';
    public const BILLER_ORDER_REQUEST_LABEL = 'biller_connect/settings/order_request_label';

    /**
     * Check if housenumber is set as second street
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isUseSeparateHousenumber(int $storeId = null): bool;

    /**
     * Get payment link duration
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getPaymentLinkDuration(int $storeId = null): string;

    /**
     * Get order request label
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getOrderRequestLabel(int $storeId = null): string;
}
