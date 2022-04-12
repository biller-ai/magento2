<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\Test\Integration\GraphQL;

use Biller\Connect\Service\Order\MakeRequest;
use Magento\Framework\Exception\LocalizedException;

class BillerPlaceOrderRequestTest extends GraphQLTestCase
{
    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea graphql
     * @magentoDataFixture Magento/Checkout/_files/simple_product.php
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store currency/options/default EUR
     * @magentoConfigFixture default_store biller_connect/general/enable 1
     * @magentoConfigFixture default_store payment/biller_gateway/active 1
     */
    public function testReturnsTheUrlForGuest(): void
    {
        $this->returnsTheUrl(function () {
            return $this->prepareGuestCart();
        });
    }

    private function returnsTheUrl(callable $getCartId): void
    {
        $makeRequestMock = $this->createMock(MakeRequest::class);
        $this->objectManager->addSharedInstance($makeRequestMock, MakeRequest::class);

        $makeRequestMock->method('execute')->willReturn('http://biller.example/order');

        // The mock must be created before we initialize the cart, so we use a closure.
        $cartId = $getCartId();

        $result = $this->graphQlQuery('
            mutation {
                billerPlaceOrderRequest(input: {
                    cart_id: "' . $cartId . '"
                    company_info: {
                        company_name: "Acme Company"
                        registration_number: "12345789"
                        vat_number: "987654321"
                    }
                }) {
                    success
                    redirect_url
                    message
                }
            }
        ')['billerPlaceOrderRequest'];

        $this->assertTrue($result['success']);
        $this->assertEquals('http://biller.example/order', $result['redirect_url']);
        $this->assertNull($result['message']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea graphql
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Checkout/_files/simple_product.php
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store currency/options/default EUR
     * @magentoConfigFixture default_store biller_connect/general/enable 1
     * @magentoConfigFixture default_store payment/biller_gateway/active 1
     */
    public function testReturnsTheUrlAsCustomer(): void
    {
        $this->returnsTheUrl(function () {
            return $this->prepareCustomerCart();
        });
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea graphql
     * @magentoDataFixture Magento/Checkout/_files/simple_product.php
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store currency/options/default EUR
     * @magentoConfigFixture default_store biller_connect/general/enable 1
     * @magentoConfigFixture default_store payment/biller_gateway/active 1
     */
    public function testReturnsSuccessFalseWhenThereIsAnException(): void
    {
        $makeRequestMock = $this->createMock(MakeRequest::class);
        $this->objectManager->addSharedInstance($makeRequestMock, MakeRequest::class);

        $makeRequestMock->method('execute')->willThrowException(new LocalizedException(__('Unable to place order')));

        $cartId = $this->prepareGuestCart();

        $result = $this->graphQlQuery('
            mutation {
                billerPlaceOrderRequest(input: {
                    cart_id: "' . $cartId . '"
                    company_info: {
                        company_name: "Acme Company"
                        registration_number: "12345789"
                        vat_number: "987654321"
                    }
                }) {
                    success
                    redirect_url
                    message
                }
            }
        ')['billerPlaceOrderRequest'];

        $this->assertFalse($result['success']);
        $this->assertNull($result['redirect_url']);
        $this->assertEquals('Unable to place order', $result['message']);
    }
}
