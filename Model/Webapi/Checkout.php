<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Webapi;

use Biller\Connect\Api\Webapi\CheckoutInterface;
use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Biller\Connect\Service\Order\MakeRequest as MakeOrderRequest;
use Biller\Connect\Service\Transaction\GenerateToken;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteIdMaskFactory;

class Checkout implements CheckoutInterface
{

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var MakeOrderRequest
     */
    private $orderRequest;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var GenerateToken
     */
    private $generateTokenService;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * Checkout constructor.
     *
     * @param MakeOrderRequest $orderRequest
     * @param LogRepository $logRepository
     * @param CheckoutSession $checkoutSession
     * @param GenerateToken $generateToken
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        MakeOrderRequest $orderRequest,
        LogRepository $logRepository,
        CheckoutSession $checkoutSession,
        GenerateToken $generateToken,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->orderRequest = $orderRequest;
        $this->logRepository = $logRepository;
        $this->checkoutSession = $checkoutSession;
        $this->generateTokenService = $generateToken;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @inheritDoc
     */
    public function orderRequest(bool $isLoggedIn, string $cartId, $paymentMethod)
    {
        $token = $this->getToken($isLoggedIn, $cartId);
        //web api can't return first level associative array
        $return = [];
        try {
            $paymentUrl = $this->orderRequest->execute($token, $paymentMethod);
            $return['response'] = ['success' => true, 'payment_page_url' => $paymentUrl];
            return $return;
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Checkout endpoint', $exception->getMessage());
            $return['response'] = ['success' => false, 'message' => $exception->getMessage()];
            return $return;
        }
    }

    /**
     * @param $isLoggedIn
     *
     * @return string|null
     */
    private function getToken($isLoggedIn, $cartId)
    {
        try {
            if ($isLoggedIn) {
                $quote = $this->checkoutSession->getQuote();
                return $this->generateTokenService->execute((int)$quote->getId());
            } else {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
                return $this->generateTokenService->execute((int)$quoteIdMask->getQuoteId());
            }
        } catch (\Exception $e) {
            return '';
        }
    }
}
