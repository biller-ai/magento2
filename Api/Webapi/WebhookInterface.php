<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Api\Webapi;

interface WebhookInterface
{

    /**
     * Process Webhook data
     *
     * @api
     * @return void
     */
    public function processTransfer();

    /**
     * Process Webhook data
     *
     * @api
     * @return void
     */
    public function processAdminTransfer();
}
