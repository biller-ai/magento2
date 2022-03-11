<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Ui;

use Biller\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'biller_gateway';

    /**
     * @var ConfigRepository
     */
    private $configProdiver;

    /**
     * ConfigProvider constructor.
     *
     * @param ConfigRepository $configProdiver
     */
    public function __construct(
        ConfigRepository $configProdiver
    ) {
        $this->configProdiver = $configProdiver;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => [
                    'companyName' => '',
                    'registrationNumber' => '',
                    'vatNumber' => '',
                    'website' => '',
                ]
            ]
        ];
    }
}
