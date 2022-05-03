<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Service\Order;

use Biller\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Biller\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use Biller\Connect\Service\Api\Adapter;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteRepository;

class MakeRequest
{

    public const MISSING_HOUSENUMBER_EXCEPTION = 'House number is missing. Please fill second line in Street address.';
    public const REQUEST_EXCEPTION = 'Unable to fetch redirect url';

    /**
     * Fields to sign
     */
    public const SIGNATURE_DATA_KEYS = [
        "external_webshop_uid",
        "external_order_uid",
        "buyer_company",
        "order_lines",
        "amount",
        "currency",
        "buyer_representative",
        "shipping_address",
        "billing_address",
    ];

    private $token = null;

    /**
     * @var ConfigRepository
     */
    private $configProvider;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var Adapter
     */
    private $adapter;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var GetOrderLines
     */
    private $orderLines;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var AddressFactory
     */
    private $quoteAddressFactory;
    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * MakeRequest constructor.
     *
     * @param ConfigRepository $configProvider
     * @param Session $checkoutSession
     * @param LogRepository $logRepository
     * @param Adapter $adapter
     * @param QuoteRepository $quoteRepository
     * @param CartManagementInterface $cartManagement
     * @param TransactionRepository $transactionRepository
     * @param GetOrderLines $orderLines
     * @param AddressFactory $quoteAddressFactory
     * @param DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        ConfigRepository $configProvider,
        Session $checkoutSession,
        LogRepository $logRepository,
        Adapter $adapter,
        QuoteRepository $quoteRepository,
        CartManagementInterface $cartManagement,
        TransactionRepository $transactionRepository,
        GetOrderLines $orderLines,
        AddressFactory $quoteAddressFactory,
        DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->configProvider = $configProvider;
        $this->checkoutSession = $checkoutSession;
        $this->logRepository = $logRepository;
        $this->adapter = $adapter;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
        $this->transactionRepository = $transactionRepository;
        $this->orderLines = $orderLines;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * Executes Biller Api for Order Request and returns redirect to platform Url
     *
     * @param string $token
     * @param array $extraData Array of extra checkout data
     *
     * @return string
     * @throws LocalizedException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function execute(string $token, array $extraData, bool $fromAdmin = false): string
    {
        $this->token = $token;
        $transaction = $this->transactionRepository->getByToken($this->token);
        $quote = $this->quoteRepository->get($transaction->getQuoteId());
        $quote->collectTotals();

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
            $this->quoteRepository->save($quote);
        }

        $payload = $this->prepareData($quote, $extraData, $fromAdmin);

        $orderRequest = $this->adapter->execute(
            'order-request',
            'post',
            $payload,
            $quote->getStoreId()
        );

        if (isset($orderRequest['uuid']) && isset($orderRequest['payment_page_url'])) {
            $transaction->setUuid($orderRequest['uuid']);
            $this->transactionRepository->save($transaction);
            $this->duplicateCurrenctQuote($quote);
            return (string)$orderRequest['payment_page_url'];
        }

        $msg = (string)self::REQUEST_EXCEPTION;
        throw new LocalizedException(__($msg));
    }

    /**
     * @param Quote $quote
     *
     * @return array
     * @throws LocalizedException
     */
    private function prepareData(Quote $quote, array $extraData, bool $fromAdmin): array
    {
        $storeUrl = $this->configProvider->getBaseUrl((int)$quote->getStoreId());
        $webhookUrl = sprintf(
            '%srest/V1/webhook/%stransfer',
            $storeUrl,
            ($fromAdmin) ? ('admin-') : ('')
        );
        $customerEmail = $quote->getBillingAddress()->getEmail() ?: $quote->getCustomerEmail();
        $customerEmail = $customerEmail ?: ($extraData['additional_data']['customer_email'] ?? '');
        $data = [
            'order_lines' => $this->orderLines->fromQuote($quote),
            'external_webshop_uid' => $this->configProvider->getWebshopUid($quote->getStoreId()),
            'external_order_uid' => $quote->getReservedOrderId(),
            'amount' => $quote->getBaseGrandTotal() * 100,
            'currency' => $quote->getBaseCurrencyCode(),
            'payment_link_duration' => $this->configProvider->getPaymentLinkDuration($quote->getStoreId()),
            'order_request_label' => $this->configProvider->getOrderRequestLabel($quote->getStoreId()),
            'buyer_company' => [
                'name' => $extraData['additional_data']['company_name'] ?? '',
                'registration_number' => $extraData['additional_data']['registration_number'] ?? '',
                'vat_number' => $extraData['additional_data']['vat_number'] ?? '',
                'website' => $extraData['additional_data']['website'] ?? '',
                'country' => $quote->getBillingAddress()->getCountryId()
            ],
            'buyer_representative' => [
                'first_name' => $quote->getBillingAddress()->getFirstname(),
                'last_name' => $quote->getBillingAddress()->getLastname(),
                'email' => $customerEmail,
                'phone_number' => $quote->getBillingAddress()->getTelephone()
            ],
            'shipping_address' => $this->getAddressData($quote->getShippingAddress(), $quote->getStoreId()),
            'billing_address' => $this->getAddressData($quote->getBillingAddress(), $quote->getStoreId()),
            'webhook_urls' => [
                'webhook_url' => $webhookUrl,
            ],
            'extra' => "amount,billing_address,buyer_company,buyer_representative,currency,external_order_uid" .
                ",external_webshop_uid,order_lines,shipping_address"
        ];

        $data = $this->addSellerUrls($data, $storeUrl, $extraData);

        if ($this->configProvider->getStoreLocale($quote->getStoreId()) == 'nl_NL') {
            $data['locale'] = 'nl';
        }

        return $this->arrayFilterRecursive($data);
    }

    /**
     * Get address data depends of config settings
     *
     * @param $address
     * @param $storeId
     *
     * @return array
     *
     * @throws LocalizedException
     */
    private function getAddressData($address, $storeId): array
    {
        $street = $address->getStreet();
        if ($this->configProvider->isUseSeparateHousenumber($storeId)) {
            if (!isset($street[1]) || !$street[1]) {
                $msg = (string)self::MISSING_HOUSENUMBER_EXCEPTION;
                throw new LocalizedException(__($msg));
            }

            return [
                'street' => isset($street[0]) ? trim($street[0]) : '',
                'house_number' => trim($street[1]),
                'house_number_suffix' => isset($street[2]) ? trim($street[2]) : '',
                'city' => $address->getCity(),
                'postal_code' => $address->getPostcode(),
                'country' => $address->getCountryId()
            ];
        }
        $street = implode(' ', $street);
        $street = trim(str_replace("\t", ' ', $street));
        $houseExtensionPattern = '(?<house_number>\d{1,5})[[:punct:]\-\/\s]*(?<house_number_suffix>[^[:space:]]{1,2})?';
        $streetPattern = '(?<street>.+)';

        $patterns = [
            "/^{$streetPattern}[\s[:space:]]+{$houseExtensionPattern}$/",
            "/^{$houseExtensionPattern}[\s[:space:]]+{$streetPattern}$/",
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $street, $matches)) {
                continue;
            }

            return [
                'street' => trim($matches['street'] ?? ''),
                'house_number' => trim($matches['house_number'] ?? ''),
                'house_number_suffix' => trim($matches['house_number_suffix'] ?? ''),
                'city' => $address->getCity(),
                'postal_code' => $address->getPostcode(),
                'country' => $address->getCountryId()
            ];
        }

        return [
            'street' => $address->getStreetLine(1),
            'house_number' => $address->getStreetLine(2),
            'house_number_suffix' => $address->getStreetLine(3),
            'city' => $address->getCity(),
            'postal_code' => $address->getPostcode(),
            'country' => $address->getCountryId()
        ];
    }

    /**
     * Recursively clean empty values from array
     *
     * @param array $inputArray
     *
     * @return array
     */
    private function arrayFilterRecursive(array $inputArray): array
    {
        foreach ($inputArray as $key => $subArray) {
            if (is_array($subArray)) {
                $inputArray[$key] = $this->arrayFilterRecursive($subArray);
            }
        }

        return array_filter(
            $inputArray,
            function ($value) {
                return ($value !== null && $value !== false && $value !== '');
            }
        );
    }

    /**
     * Duplicate current quote and set this as active session.
     * This prevents quotes to change during checkout process
     *
     * @param Quote $quote
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    private function duplicateCurrenctQuote(Quote $quote)
    {
        $quote->setIsActive(false);
        $this->quoteRepository->save($quote);
        if ($customerId = $quote->getCustomerId()) {
            $cartId = $this->cartManagement->createEmptyCartForCustomer($customerId);
        } else {
            $cartId = $this->cartManagement->createEmptyCart();
        }
        $newQuote = $this->quoteRepository->get($cartId);
        $newQuote->merge($quote);

        $newQuote->removeAllAddresses();
        if (!$quote->getIsVirtual()) {
            $addressData = $this->dataObjectProcessor->buildOutputDataArray(
                $quote->getShippingAddress(),
                AddressInterface::class
            );
            unset($addressData['id']);
            $shippingAddress = $this->quoteAddressFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $shippingAddress,
                $addressData,
                AddressInterface::class
            );
            $newQuote->setShippingAddress(
                $shippingAddress
            );
        }

        $addressData = $this->dataObjectProcessor->buildOutputDataArray(
            $quote->getBillingAddress(),
            AddressInterface::class
        );
        unset($addressData['id']);
        $billingAddress = $this->quoteAddressFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $billingAddress,
            $addressData,
            AddressInterface::class
        );
        $newQuote->setBillingAddress(
            $billingAddress
        );

        $newQuote->setTotalsCollectedFlag(false)->collectTotals();
        $this->quoteRepository->save($newQuote);

        $this->checkoutSession->replaceQuote($newQuote);
    }

    private function addSellerUrls(array $data, string $storeUrl, array $extraData): array
    {
        $successUrl = $storeUrl . 'biller/checkout/process/token/' . $this->token;
        $errorUrl = $storeUrl . 'biller/checkout/process/token/' . $this->token;
        $cancelUrl = $storeUrl . 'biller/checkout/process/token/' . $this->token . '/cancel/1/';
        $pendingUrl = $storeUrl . 'biller/checkout/process/token/' . $this->token;

        if (isset($extraData['seller_urls']['success_url'])) {
            $successUrl = $this->enhanceUrl($extraData['seller_urls']['success_url']);
        }
        if (isset($extraData['seller_urls']['error_url'])) {
            $errorUrl = $this->enhanceUrl($extraData['seller_urls']['error_url']);
        }
        if (isset($extraData['seller_urls']['cancel_url'])) {
            $cancelUrl = $this->enhanceUrl($extraData['seller_urls']['cancel_url']);
        }
        if (isset($extraData['seller_urls']['pending_url'])) {
            $pendingUrl = $this->enhanceUrl($extraData['seller_urls']['pending_url']);
        }

        $data['seller_urls'] = [
            'success_url' => $successUrl,
            'error_url' => $errorUrl,
            'cancel_url' => $cancelUrl,
            'pending_url' => $pendingUrl,
        ];

        return $data;
    }

    private function enhanceUrl(string $url): string
    {
        $replacements = [
            '{{token}}' => $this->token,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $url
        );
    }
}
