<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AvailabilityValidator extends AbstractValidator
{
    /**
     * Validate
     *
     * @param bool  $isValid
     * @param array $fails
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        return $this->createResult(true);
    }
}
