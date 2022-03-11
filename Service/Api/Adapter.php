<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Service\Api;

use Biller\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Zend_Http_Client;
use Zend_Http_Response;

/**
 * Adapter class
 *
 * Biller API adapter
 */
class Adapter
{

    /**
     * API URLs pattern
     */
    public const API_URL = [
        'live' => 'https://api.biller.ai/v1/api/%s/',
        'sandbox' => 'https://api.sandbox.biller.ai/v1/api/%s/'
    ];

    public const DEFAULT_TIMEOUT = 15;
    public const HTTP_VER = '1.1';

    /**
     * @var array
     */
    private $token = [];

    /**
     * @var int
     */
    private $storeId = 0;

    /**
     * @var ConfigRepository
     */
    private $configProvider;

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var CurlFactory
     */
    private $httpClientFactory;

    /**
     * @var array
     */
    private $credentials;

    /**
     * @var HttpCode
     */
    private $httpCode;

    /**
     * Adapter constructor.
     *
     * @param CurlFactory $httpClientFactory
     * @param Json $json
     * @param ConfigRepository $configProvider
     * @param LogRepository $logRepository
     * @param HttpCode $httpCode
     */
    public function __construct(
        CurlFactory $httpClientFactory,
        Json $json,
        ConfigRepository $configProvider,
        LogRepository $logRepository,
        HttpCode $httpCode
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->httpCode = $httpCode;
    }

    /**
     * @param       $entry
     * @param       $action
     * @param array $data
     * @param int $storeId
     *
     * @return array|bool|float|int|mixed|string|null
     * @throws LocalizedException
     */
    public function execute($entry, $action, $data = [], $storeId = 0)
    {
        $this->storeId = $storeId;
        if (isset($data['credentials'])) {
            $this->credentials = $data['credentials'];
        }
        $token = $this->getToken();

        if ($entry == 'CredentialsTest') {
            return $token;
        }

        $this->logRepository->addDebugLog(sprintf('Api call: %s', $action), $data);

        $httpClient = $this->getClient();
        $httpClient->write(
            $this->getMethod($action),
            $this->formatUrl($entry),
            self::HTTP_VER,
            $this->getHeaders($token),
            $this->json->serialize($data)
        );

        $response = $httpClient->read();
        $status = Zend_Http_Response::extractCode($response);
        $body = Zend_Http_Response::extractBody($response);

        try {
            $result = $this->json->unserialize($body);
            $this->logRepository->addDebugLog(
                sprintf('API [%s => %s] (status: %s)', $action, $this->formatUrl($entry), $status),
                $result
            );
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog(
                sprintf('API [%s => %s] (status: %s)', $action, $this->formatUrl($entry), $status),
                $exception->getMessage()
            );
            throw new LocalizedException(__($exception->getMessage()));
        }

        if ($status >= 200 && $status < 300) {
            return $result;
        }

        throw new LocalizedException($this->formatApiError($result, $status));
    }

    /**
     * Token retriever
     *
     * @return mixed
     * @throws LocalizedException
     */
    private function getToken(): string
    {
        if (isset($this->token[$this->storeId])) {
            return $this->token[$this->storeId];
        }
        if (!$this->credentials) {
            $this->credentials = $this->configProvider->getCredentials($this->storeId);
        }
        $httpClient = $this->getClient();
        $httpClient->write(
            $this->getMethod('POST'),
            $this->formatUrl('token'),
            self::HTTP_VER,
            $this->getHeaders(),
            $this->json->serialize(
                [
                    'username' => $this->credentials['username'],
                    'password' => $this->credentials['password']
                ]
            )
        );

        $response = $httpClient->read();
        $status = Zend_Http_Response::extractCode($response);
        $body = Zend_Http_Response::extractBody($response);
        $result = $this->json->unserialize($body);

        if ($status == 401) {
            throw new AuthenticationException($this->formatApiError($result, $status));
        }
        if ($status >= 200 && $status < 300) {
            $token = $result['access'] ?? '';
            $this->token[$this->storeId] = (string)$token;
            return $this->token[$this->storeId];
        }

        throw new LocalizedException($this->formatApiError($result, $status));
    }

    /**
     * @return Curl
     */
    private function getClient(): Curl
    {
        $httpClient = $this->httpClientFactory->create();
        $httpClient->setConfig([
            'timeout' => self::DEFAULT_TIMEOUT
        ]);

        return $httpClient;
    }

    /**
     * @param string $method
     *
     * @return string
     */
    private function getMethod(string $method): string
    {
        switch (strtoupper($method)) {
            case 'GET':
                return Zend_Http_Client::GET;
            case 'POST':
                return Zend_Http_Client::POST;
        }
        return '';
    }

    /**
     * @param string $action
     *
     * @return string
     */
    private function formatUrl(string $action): string
    {
        $mode = ($this->configProvider->isSandbox($this->storeId)) ? 'sandbox' : 'live';
        return sprintf(self::API_URL[$mode], $action);
    }

    /**
     * @param string|null $token
     * @return array
     */
    private function getHeaders(?string $token = null): array
    {
        $headers = ['Content-Type: application/json'];
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        return $headers;
    }

    /**
     * @param $result
     *
     * @param $status
     * @return Phrase
     */
    private function formatApiError($result, $status): Phrase
    {
        $errorMsg = $this->json->serialize($result);

        if (!empty($result['detail'])) {
            $errorMsg = $result['detail'];
            $returnMsg = __('Biller: %1', $result['detail']);
        } else {
            $returnMsg = __('Biller: %1', $this->httpCode->execute((int)$status));
        }

        if ($status == 400) {
            $foundIssues = [];
            foreach ($result as $node => $issues) {
                foreach ($issues as $field => $issue) {
                    $foundIssues[] = sprintf(
                        '%s (%s): %s',
                        ucwords(str_replace('_', ' ', $field)),
                        ucwords(str_replace('_', ' ', $node)),
                        implode($issue)
                    );
                }
            }
            if ($foundIssues) {
                $returnMsg = __('Biller: %1', implode(' | ', $foundIssues));
            }
        }

        $this->logRepository->addErrorLog(
            sprintf('API Status %s', $status),
            $errorMsg
        );

        return $returnMsg;
    }
}
