<?php
/**
 * Copyright © Biller.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Transaction;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Biller\Connect\Api\Transaction\Data\DataInterface;
use Biller\Connect\Api\Transaction\Data\DataInterfaceFactory;
use Biller\Connect\Api\Transaction\Data\SearchResultsInterface;
use Biller\Connect\Api\Transaction\Data\SearchResultsInterfaceFactory;
use Biller\Connect\Api\Transaction\RepositoryInterface;
use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;

/**
 * Transaction Repository class
 */
class Repository implements RepositoryInterface
{

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var DataInterfaceFactory
     */
    private $dataFactory;

    /**
     * @var ResourceModel
     */
    private $resource;

    /**
     * @var LogRepository
     */
    private $logger;

    /**
     * Repository constructor.
     *
     * @param SearchResultsInterfaceFactory $searchResultFactory
     * @param CollectionFactory             $collectionFactory
     * @param ResourceModel                 $resource
     * @param DataInterfaceFactory          $dataFactory
     * @param LogRepository                 $logger
     */
    public function __construct(
        SearchResultsInterfaceFactory $searchResultFactory,
        CollectionFactory $collectionFactory,
        ResourceModel $resource,
        DataInterfaceFactory $dataFactory,
        LogRepository $logger
    ) {
        $this->searchResultFactory = $searchResultFactory;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
        $this->dataFactory = $dataFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function create(): DataInterface
    {
        return $this->dataFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        /* @var Collection $collection */
        $collection = $this->collectionFactory->create();
        return $this->searchResultFactory->create()
            ->setSearchCriteria($searchCriteria)
            ->setItems($collection->getItems())
            ->setTotalCount($collection->getSize());
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $entityId): bool
    {
        $entity = $this->get((int)$entityId);
        return $this->delete($entity);
    }

    /**
     * @inheritDoc
     */
    public function get(int $entityId): DataInterface
    {
        if (!$entityId) {
            $exceptionMsg = static::INPUT_EXCEPTION;
            throw new InputException(__($exceptionMsg));
        } elseif (!$this->resource->isExists($entityId)) {
            $exceptionMsg = self::NO_SUCH_ENTITY_EXCEPTION;
            throw new NoSuchEntityException(__($exceptionMsg, $entityId));
        }
        return $this->dataFactory->create()
            ->load($entityId);
    }

    /**
     * @inheritDoc
     */
    public function delete(DataInterface $entity): bool
    {
        try {
            $this->resource->delete($entity);
        } catch (\Exception $exception) {
            $this->logger->addErrorLog('Quote repository', $exception->getMessage());
            $exceptionMsg = self::COULD_NOT_DELETE_EXCEPTION;
            throw new CouldNotDeleteException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function save(DataInterface $entity): DataInterface
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            $this->logger->addErrorLog('Quote repository', $exception->getMessage());
            $exceptionMsg = self::COULD_NOT_SAVE_EXCEPTION;
            throw new CouldNotSaveException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function getByDataSet(array $dataSet, bool $getFirst = false)
    {
        $collection = $this->collectionFactory->create();
        foreach ($dataSet as $attribute => $value) {
            if (is_array($value)) {
                $collection->addFieldToFilter($attribute, ['in' => $value]);
            } else {
                $collection->addFieldToFilter($attribute, $value);
            }
        }
        if ($getFirst) {
            return $collection->getFirstItem();
        } else {
            return $collection;
        }
    }

    /**
     * @inheritDoc
     */
    public function getByQuoteId(int $quoteId, bool $uuidCheck = false): DataInterface
    {
        if (!$quoteId) {
            throw new InputException(__('An QuoteId is needed. Set the QuoteId and try again.'));
        } elseif (!$this->resource->isQuoteIdExists($quoteId)) {
            throw new NoSuchEntityException(__('No record found for QuoteId: %1.', $quoteId));
        }

        if (!$uuidCheck) {
            return $this->dataFactory->create()->load($quoteId, 'quote_id');
        }
        $transaction = $this->getByDataSet(
            [
                'quote_id' => $quoteId,
                'uuid' => ['empty' => true]
            ],
            true
        );
        if ($transaction->getData() == null) {
            throw new NoSuchEntityException(__('No record found for QuoteId: %1. with empty uuid', $quoteId));
        }
        return $transaction;
    }

    /**
     * @inheritDoc
     */
    public function getByOrderId(int $orderId): DataInterface
    {
        if (!$orderId) {
            throw new InputException(__('An OrderID is needed. Set the OrderID and try again.'));
        } elseif (!$this->resource->isOrderIdExists($orderId)) {
            throw new NoSuchEntityException(__('No record found for OrderID: %1.', $orderId));
        }
        return $this->dataFactory->create()
            ->load($orderId, 'order_id');
    }

    /**
     * @inheritDoc
     */
    public function getByUuid(string $uuid): DataInterface
    {
        if (!$uuid) {
            throw new InputException(__('An uuid is needed. Set the uuid and try again.'));
        } elseif (!$this->resource->isUuidExists($uuid)) {
            throw new NoSuchEntityException(__('No record found for uuid: %1.', $uuid));
        }
        return $this->dataFactory->create()
            ->load($uuid, 'uuid');
    }

    /**
     * @inheritDoc
     */
    public function getByToken(string $token): DataInterface
    {
        if (!$token) {
            throw new InputException(__('An token is needed. Set the token and try again.'));
        } elseif (!$this->resource->isTokenExist($token)) {
            throw new NoSuchEntityException(__('No record found for token: %1.', $token));
        }
        return $this->dataFactory->create()
            ->load($token, 'token');
    }

    /**
     * @inheritDoc
     */
    public function lock(DataInterface $entity): bool
    {
        return $this->resource->lockTransaction($entity);
    }

    /**
     * @inheritDoc
     */
    public function unlock(DataInterface $entity): DataInterface
    {
        $entity->setIsLocked(0);
        return $this->save($entity);
    }

    /**
     * @inheritDoc
     */
    public function isLocked(DataInterface $entity): bool
    {
        return $this->resource->isLocked($entity);
    }

    /**
     * @inheritDoc
     */
    public function checkOrderIsPlaced(DataInterface $entity): bool
    {
        return $this->resource->isOrderPlaced($entity);
    }
}
