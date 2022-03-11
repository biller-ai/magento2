<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Api\Webapi;

interface CheckoutInterface
{
    /**
     * @api
     *
     * @param bool $isLoggedIn
     * @param string $cartId
     * @param mixed $paymentMethod
     * @return mixed
     */
    public function orderRequest(bool $isLoggedIn, string $cartId, $paymentMethod);
}
