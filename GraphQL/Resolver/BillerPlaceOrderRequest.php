<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\GraphQL\Resolver;

use Biller\Connect\Service\Order\MakeRequest;
use Biller\Connect\Service\Transaction\GenerateToken;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

class BillerPlaceOrderRequest implements ResolverInterface
{
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var MakeRequest
     */
    private $makeOrderRequest;

    /**
     * @var GenerateToken
     */
    private $generateToken;

    public function __construct(
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        MakeRequest $makeOrderRequest,
        GenerateToken $generateToken
    ) {
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->makeOrderRequest = $makeOrderRequest;
        $this->generateToken = $generateToken;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $input = $args['input'];
        $data = ['additional_data' => $input['company_info']];

        if (isset($input['urls'])) {
            $data['seller_urls'] = $input['urls'];
        }

        $quoteId = $this->maskedQuoteIdToQuoteId->execute($input['cart_id']);
        $token = $this->generateToken->execute($quoteId);

        try {
            $url = $this->makeOrderRequest->execute($token, $data);

            return [
                'success' => true,
                'redirect_url' => $url,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
