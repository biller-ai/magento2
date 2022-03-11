<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\ViewModel\Checkout;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Pending ViewModel
 */
class Pending implements ArgumentInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * Pending constructor.
     *
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get refresh url
     *
     * @return string
     */
    public function getRefreshUrl(): string
    {
        $token = $this->request->getParam('token');
        return $this->getBaseUrl() . 'biller/checkout/process/token/' . $token . '/';
    }

    /**
     * Get check url
     *
     * @return string
     */
    public function getCheckUrl(): string
    {
        $token = $this->request->getParam('token');
        return $this->getBaseUrl() . 'rest/V1/biller/check-order-placed/' . $token . '/';
    }

    /**
     * Get base url of the application
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        if (!$this->baseUrl) {
            $this->baseUrl = $this->urlBuilder->getBaseUrl();
        }
        return $this->baseUrl;
    }
}
