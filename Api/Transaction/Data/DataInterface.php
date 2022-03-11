<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Api\Transaction\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface for transaction model
 * @api
 */
interface DataInterface extends ExtensibleDataInterface
{

    public const ENTITY_ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
    public const ORDER_ID = 'order_id';
    public const UUID = 'uuid';
    public const TOKEN = 'token';
    public const STATUS = 'status';
    public const INVOICE_UUID = 'invoice_uuid';
    public const PAYMENT_URL = 'payment_url';
    public const IS_LOCKED = 'is_locked';

    /**
     * Returns the ID.
     *
     * @return int ID.
     */
    public function getEntityId(): int;

    /**
     * Returns the quote ID.
     *
     * @return int|null quote ID.
     */
    public function getQuoteId(): ?int;

    /**
     * Sets the quote ID.
     *
     * @param int $quoteId
     * @return $this
     */
    public function setQuoteId(int $quoteId): self;

    /**
     * Returns the order ID.
     *
     * @return int|null order ID.
     */
    public function getOrderId(): ?int;

    /**
     * Sets the order ID.
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * Return token.
     *
     * @return string|null
     */
    public function getToken(): ?string;

    /**
     * Set token.
     *
     * @param string $value
     * @return $this
     */
    public function setToken(string $value): self;

    /**
     * Return uuuid.
     *
     * @return string|null
     */
    public function getUuid(): ?string;

    /**
     * Set uuuid.
     *
     * @param string $value
     * @return $this
     */
    public function setUuid(string $value): self;

    /**
     * Return status.
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Return invoice_uuid.
     *
     * @return string|null
     */
    public function getInvoiceUuid(): ?string;

    /**
     * Set invoice_uid.
     *
     * @param string $invoiceUuid
     * @return $this
     */
    public function setInvoiceUuid(string $invoiceUuid): self;

    /**
     * Return payment_url.
     *
     * @return string|null
     */
    public function getPaymentUrl(): ?string;

    /**
     * Set payment_url.
     *
     * @param string $paymentUrl
     * @return $this
     */
    public function setPaymentUrl(string $paymentUrl): self;

    /**
     * Return is_locked.
     *
     * @return int|null
     */
    public function getIsLocked(): ?int;

    /**
     * Set is_locked.
     *
     * @param int $isLocked
     * @return $this
     */
    public function setIsLocked(int $isLocked): self;
}
