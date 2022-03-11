<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\GraphQL\Resolver;

use Biller\Connect\Service\Order\ProcessReturn;
use Biller\Connect\Service\Transaction\GenerateToken;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

class BillerProcessReturn implements ResolverInterface
{
    /**
     * @var ProcessReturn
     */
    private $processReturn;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var GenerateToken
     */
    private $generateToken;

    public function __construct(
        ProcessReturn $processReturn,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        GenerateToken $generateToken
    ) {
        $this->processReturn = $processReturn;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->generateToken = $generateToken;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $quoteId = $this->maskedQuoteIdToQuoteId->execute($args['cart_id']);
        $token = $this->generateToken->execute($quoteId);
        $result = $this->processReturn->execute($token);

        return [
            'success' => $result['success'],
            'status' => $result['status'],
            'message' => $result['msg'] ?? null,
        ];
    }
}
