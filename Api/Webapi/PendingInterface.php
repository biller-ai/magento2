<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Api\Webapi;

interface PendingInterface
{
    /**
     * @api
     * Check if order is placed by transaction token
     *
     * @param string $token
     *
     * @return bool
     */
    public function checkOrderPlaced(string $token): bool;
}
