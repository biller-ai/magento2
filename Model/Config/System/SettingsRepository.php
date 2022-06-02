<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Config\System;

use Biller\Connect\Api\Config\System\SettingsInterface;

/**
 * Settings provider class
 */
class SettingsRepository extends DebugRepository implements SettingsInterface
{

    /**
     * @inheritDoc
     */
    public function isUseSeparateHousenumber(int $storeId = null): bool
    {
        return $this->isSetFlag(self::BILLER_USE_SEPARATE_HOUSENUMBER, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentLinkDuration(int $storeId = null): string
    {
        return (string)$this->getStoreValue(self::BILLER_PAYMENT_LINK_DURATION, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderRequestLabel(int $storeId = null): string
    {
        return (string)$this->getStoreValue(self::BILLER_ORDER_REQUEST_LABEL, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getEmailSender(int $storeId = null): array
    {
        $identity = $this->getStoreValue(self::BILLER_EMAIL_SENDER, $storeId);
        return [
            'name' => $this->getStoreValue('trans_email/ident_' . $identity . '/name', $storeId),
            'email' => $this->getStoreValue('trans_email/ident_' . $identity . '/email', $storeId)
        ];
    }
}
