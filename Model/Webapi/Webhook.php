<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\Model\Webapi;

use Biller\Connect\Api\Webapi\WebhookInterface;
use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Biller\Connect\Service\Order\ProcessWebhook;
use Biller\Connect\Service\Order\ProcessAdminWebhook;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Class Webhook
 */
class Webhook implements WebhookInterface
{

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var ProcessWebhook
     */
    private $processWebhook;

    /**
     * @var ProcessWebhook
     */
    private $processAdminWebhook;

    /**
     * @var File
     */
    private $file;

    /**
     * Webhook constructor.
     *
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     * @param ProcessWebhook $processWebhook
     * @param ProcessAdminWebhook $processAdminWebhook
     * @param File $file
     */
    public function __construct(
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer,
        ProcessWebhook $processWebhook,
        ProcessAdminWebhook $processAdminWebhook,
        File $file
    ) {
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->processWebhook = $processWebhook;
        $this->processAdminWebhook = $processAdminWebhook;
        $this->file = $file;
    }

    /**
     * @inheritDoc
     */
    public function processTransfer()
    {
        try {
            $post = $this->file->fileGetContents('php://input');
            $postArray = $this->jsonSerializer->unserialize($post);
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Webhook processTransfer postData', $exception->getMessage());
            throw new LocalizedException(__('Post data should be provided.'));
        }

        if (isset($postArray['payload']['order_id'])) {
            try {
                $this->processWebhook->execute($postArray['payload']['order_id']);
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('Webhook processTransfer', $exception->getMessage());
                throw new LocalizedException(__($exception->getMessage()));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function processAdminTransfer()
    {
        try {
            $post = $this->file->fileGetContents('php://input');
            $postArray = $this->jsonSerializer->unserialize($post);
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Webhook processTransfer postData', $exception->getMessage());
            throw new LocalizedException(__('Post data should be provided.'));
        }

        if (isset($postArray['payload']['order_id'])) {
            try {
                $this->processAdminWebhook->execute($postArray['payload']['order_id']);
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('Webhook processAdminTransfer', $exception->getMessage());
                throw new LocalizedException(__($exception->getMessage()));
            }
        }
    }
}
