<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Block\Adminhtml\Magmodules;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Biller\Connect\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * System Configration Module information Block
 */
class Header extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Biller_Connect::system/config/fieldset/header.phtml';

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * Header constructor.
     *
     * @param Context $context
     * @param ConfigRepository $config
     */
    public function __construct(
        Context $context,
        ConfigRepository $config
    ) {
        $this->configRepository = $config;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->addClass('magmodules');

        return $this->toHtml();
    }

    /**
     * Image with extension and magento version.
     *
     * @return string
     */
    public function getImage(): string
    {
        return sprintf(
            'https://www.magmodules.eu/logo/%s/%s/%s/logo.png',
            $this->configRepository->getExtensionCode(),
            $this->configRepository->getExtensionVersion(),
            $this->configRepository->getMagentoVersion()
        );
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink(): string
    {
        return $this->configRepository->getSupportLink();
    }
}
