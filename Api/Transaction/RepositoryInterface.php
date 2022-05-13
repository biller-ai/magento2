<?php
/**
 * Copyright © Biller.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Api\Transaction;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Biller\Connect\Api\Transaction\Data\DataInterface;
use Biller\Connect\Api\Transaction\Data\SearchResultsInterface;
use Biller\Connect\Model\Transaction\Collection;
use Biller\Connect\Model\Transaction\DataModel;

/**
 * Transaction repository interface
 */
interface RepositoryInterface
{

    /**
     * Exception text
     */
    public const INPUT_EXCEPTION = 'An ID is needed. Set the ID and try again.';
    public const NO_SUCH_ENTITY_EXCEPTION = 'The transaction with id "%1" does not exist.';
    public const COULD_NOT_DELETE_EXCEPTION = 'Could not delete the transaction: %1';
    public const COULD_NOT_SAVE_EXCEPTION = 'Could not save the transaction: %1';

    /**
     * Loads a specified transaction
     *
     * @param int $entityId
     *
     * @return DataInterface
     * @throws LocalizedException
     */
    public function get(int $entityId): DataInterface;

    /**
     * Return transaction object
     *
     * @return DataInterface
     */
    public function create(): DataInterface;

    /**
     * Retrieves a transaction matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return SearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Get data collection by set of attribute values
     *
     * @param array $dataSet
     * @param bool  $getFirst
     *
     * @return Collection|DataModel
     */
    public function getByDataSet(array $dataSet, bool $getFirst = false);

    /**
     * Register entity to delete
     *
     * @param DataInterface $entity
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(DataInterface $entity): bool;

    /**
     * Deletes transaction entity by ID
     *
     * @param int $entityId
     *
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Perform persist operations for one entity
     *
     * @param DataInterface $entity
     *
     * @return DataInterface
     * @throws LocalizedException
     */
    public function save(DataInterface $entity): DataInterface;

    /**
     * Get transaction by Quote ID
     *
     * @param int $quoteId
     * @param bool $uuidCheck
     * @return DataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByQuoteId(int $quoteId, bool $uuidCheck = false): DataInterface;

    /**
     * Get transaction by Order ID
     *
     * @param int $orderId
     *
     * @return DataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): DataInterface;

    /**
     * Get transaction by UUID
     *
     * @param string $uuid
     *
     * @return DataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByUuid(string $uuid): DataInterface;

    /**
     * Get transaction by token
     *
     * @param string $token
     *
     * @return DataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByToken(string $token): DataInterface;

    /**
     * Lock transaction
     *
     * @param DataInterface $entity
     *
     * @return bool
     * @throws LocalizedException
     */
    public function lock(DataInterface $entity): bool;

    /**
     * Unlock transaction
     *
     * @param DataInterface $entity
     *
     * @return DataInterface
     * @throws LocalizedException
     */
    public function unlock(DataInterface $entity): DataInterface;

    /**
     * Check if transaction is locked
     *
     * @param DataInterface $entity
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isLocked(DataInterface $entity): bool;

    /**
     * Check if order is placed for this transaction
     *
     * @param DataInterface $entity
     *
     * @return bool
     * @throws LocalizedException
     */
    public function checkOrderIsPlaced(DataInterface $entity): bool;
}
