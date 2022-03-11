<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\Controller\Checkout;

use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Biller\Connect\Service\Order\ProcessReturn;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Process Controller
 */
class Process extends Action
{

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ProcessReturn
     */
    private $processReturn;

    /**
     * Process constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param LogRepository $logRepository
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        ProcessReturn $processReturn,
        LogRepository $logRepository
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->processReturn = $processReturn;
        $this->logRepository = $logRepository;
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$token = $this->getRequest()->getParam('token')) {
            $this->messageManager->addErrorMessage(__('Error in return data from Biller'));
            $resultRedirect->setPath('checkout/cart/index');
            return $resultRedirect;
        }
        $cancel = $this->getRequest()->getParam('cancel');

        try {
            $result = $this->processReturn->execute((string)$token, $cancel);
            if ($result['success']) {
                $resultRedirect->setPath('checkout/onepage/success');
            } elseif ($result['status'] == 'accepted') {
                $resultRedirect->setPath('biller/checkout/pending', ['token' => $token]);
            } else {
                $this->messageManager->addErrorMessage($result['msg']);
                $resultRedirect->setPath('checkout/cart/index');
            }
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Checkout Process', $exception->getMessage());
            $this->messageManager->addErrorMessage('Error processing payment');
            $resultRedirect->setPath('checkout/cart/index');
        }
        return $resultRedirect;
    }
}
