<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\Test\Integration\GraphQL;

use Biller\Connect\Service\Order\ProcessReturn;

class BillerProcessReturnTest extends GraphQLTestCase
{
    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea graphql
     * @magentoDataFixture Magento/Checkout/_files/simple_product.php
     * @magentoConfigFixture default_store biller_connect/general/enable 1
     * @magentoConfigFixture default_store payment/biller_gateway/active 1
     */
    public function testReturnsSuccess(): void
    {
        $processReturnMock = $this->createMock(ProcessReturn::class);
        $this->objectManager->addSharedInstance($processReturnMock, ProcessReturn::class);

        $processReturnMock->method('execute')->willReturn([
            'success' => true,
            'status' => 'accepted',
        ]);

        $cartId = $this->prepareGuestCart();

        $result = $this->graphQlQuery('
            query {
                billerProcessReturn(cart_id: "' . $cartId . '") {
                    success
                    status
                    message
                }
            }
        ')['billerProcessReturn'];

        $this->assertTrue($result['success']);
        $this->assertEquals('accepted', $result['status']);
        $this->assertNull($result['message']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea graphql
     * @magentoDataFixture Magento/Checkout/_files/simple_product.php
     * @magentoConfigFixture default_store biller_connect/general/enable 1
     * @magentoConfigFixture default_store payment/biller_gateway/active 1
     */
    public function testReturnsTheMessage(): void
    {
        $processReturnMock = $this->createMock(ProcessReturn::class);
        $this->objectManager->addSharedInstance($processReturnMock, ProcessReturn::class);

        $processReturnMock->method('execute')->willReturn([
            'success' => false,
            'status' => 'cancelled',
            'msg' => ProcessReturn::CANCELLED_MSG,
        ]);

        $cartId = $this->prepareGuestCart();

        $result = $this->graphQlQuery('
            query {
                billerProcessReturn(cart_id: "' . $cartId . '") {
                    success
                    status
                    message
                }
            }
        ')['billerProcessReturn'];

        $this->assertFalse($result['success']);
        $this->assertEquals('cancelled', $result['status']);
        $this->assertEquals(ProcessReturn::CANCELLED_MSG, $result['message']);
    }
}
