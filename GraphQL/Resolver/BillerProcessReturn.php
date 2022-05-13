<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\GraphQL\Resolver;

use Biller\Connect\Service\Order\ProcessReturn;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class BillerProcessReturn implements ResolverInterface
{
    /**
     * @var ProcessReturn
     */
    private $processReturn;

    public function __construct(
        ProcessReturn $processReturn
    ) {
        $this->processReturn = $processReturn;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['token'])) {
            throw new GraphQlInputException(__('The argument "token" is required'));
        }

        $result = $this->processReturn->execute($args['token']);

        return [
            'success' => $result['success'],
            'status' => $result['status'] ?? 'unknown',
            'message' => $result['msg'] ?? null,
        ];
    }
}
