<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Controller\Adminhtml\Credentials;

use Biller\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use Biller\Connect\Service\Api\Adapter;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Credential check controller to validate API data
 */
class Check extends Action
{

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var Json
     */
    private $resultJson;

    /**
     * @var ConfigRepository
     */
    private $configProvider;

    /**
     * Check constructor.
     *
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Adapter $adapter
     * @param ConfigRepository $configProvider
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        Adapter $adapter,
        ConfigRepository $configProvider
    ) {
        $this->adapter = $adapter;
        $this->resultJson = $resultJsonFactory->create();
        $this->configProvider = $configProvider;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $config = $this->getCredentials();
        if (!$config['credentials']['username'] || !$config['credentials']['password']) {
            return $this->resultJson->setData(
                [
                    'success' => true,
                    'msg' => __('Set credentials first')
                ]
            );
        }
        try {
            $this->adapter->execute(
                'CredentialsTest',
                'GET',
                ['credentials' => $config['credentials']],
                (int)$config['store_id']
            );
        } catch (\Exception $exception) {
            return $this->resultJson->setData(
                ['success' => false, 'msg' => $exception->getMessage()]
            );
        }

        $message = __('Credentials correct!')->render() . '<br>';

        return $this->resultJson->setData(
            [
                'success' => true,
                'msg' => $message
            ]
        );
    }

    /**
     * @return array
     */
    private function getCredentials(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store');
        $mode = $this->getRequest()->getParam('mode');
        if ($mode == 'sandbox') {
            $username = $this->getRequest()->getParam('sandbox_username');
            $password = $this->getRequest()->getParam('sandbox_password');
        } else {
            $username = $this->getRequest()->getParam('username');
            $password = $this->getRequest()->getParam('password');
        }

        if ($username == '******') {
            $username = $this->configProvider->getCredentials($storeId)['username'];
        }

        if ($password == '******') {
            $password = $this->configProvider->getCredentials($storeId)['password'];
        }

        return [
            'store_id' => $storeId,
            'credentials' => [
                "username" => $username,
                "password" => $password,
            ]
        ];
    }
}
