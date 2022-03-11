<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\Test\Integration\GraphQL;

use Magento\Framework\GraphQl\Query\Fields as QueryFields;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GraphQl\Controller\GraphQl;
use Magento\GraphQl\Service\GraphQlRequest;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

abstract class GraphQLTestCase extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var SerializerInterface|mixed
     */
    private $json;

    /**
     * @var GraphQlRequest|mixed
     */
    private $graphQlRequest;

    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->json = $this->objectManager->get(SerializerInterface::class);
        $this->graphQlRequest = $this->objectManager->create(GraphQlRequest::class);
    }

    /**
     * @param string $query
     * @return mixed
     * @throws \Exception
     */
    protected function graphQlQuery(string $query, array $variables = [], string $operation = '', array $headers = [])
    {
        $this->resetGraphQlCache();
        $response = $this->graphQlRequest->send($query, $variables, $operation, $headers);
        $responseData = $this->json->unserialize($response->getContent());

        if (isset($responseData['errors'])) {
            $this->processErrors($responseData);
        }

        return $responseData['data'];
    }

    /**
     * @param array $body
     * @throws \Exception
     */
    private function processErrors(array $body): void
    {
        $errorMessage = '';
        foreach ($body['errors'] as $error) {
            if (!isset($error['message'])) {
                continue;
            }

            $errorMessage .= $error['message'] . PHP_EOL;
            if (isset($error['debugMessage'])) {
                $errorMessage .= $error['debugMessage'] . PHP_EOL;
            }
        }

        throw new \Exception('GraphQL response contains errors: ' . $errorMessage);
    }

    private function resetGraphQlCache(): void
    {
        $this->objectManager->removeSharedInstance(GraphQl::class);
        $this->objectManager->removeSharedInstance(QueryFields::class);
        $this->graphQlRequest = $this->objectManager->create(GraphQlRequest::class);
    }

    protected function prepareGuestCart(): string
    {
        $cartId = $this->graphQlQuery('mutation {createEmptyCart}')['createEmptyCart'];

        $this->graphQlQuery('
            mutation {
                addSimpleProductsToCart(
                    input: {
                        cart_id: "' . $cartId . '"
                        cart_items: [
                            {
                                data: {
                                    quantity: 1
                                    sku: "simple"
                                }
                            }
                        ]
                    }
                ) {
                    cart {
                        items {
                            id
                        }
                    }
                }
            }
        ');

        $this->graphQlQuery('
            mutation {
                setBillingAddressOnCart(
                    input: {
                        cart_id: "' . $cartId . '"
                        billing_address: {
                            address: {
                                firstname: "John"
                                lastname: "Doe"
                                company: "Acme"
                                street: ["Main St", "123"]
                                city: "Anytown"
                                postcode: "1234AB"
                                country_code: "NL"
                                telephone: "123-456-0000"
                                save_in_address_book: false
                            }
                            use_for_shipping: true
                        }
                    }
                ) {
                    cart {
                        billing_address {
                            firstname
                            lastname
                            company
                            street
                            city
                            postcode
                            telephone
                            country {
                                code
                                label
                            }
                        }
                        shipping_addresses {
                            firstname
                            lastname
                            company
                            street
                            city
                            postcode
                            telephone
                            country {
                                code
                                label
                            }
                        }
                    }
                }
            }
        ');

        $result = $this->graphQlQuery('
            query {
                cart(cart_id: "' . $cartId . '") {
                    shipping_addresses {
                        available_shipping_methods {
                            error_message
                            method_code
                            method_title
                        }
                    }
                }
            }
        ');

        $method = $result['cart']['shipping_addresses'][0]['available_shipping_methods'][0];

        $this->graphQlQuery('
            mutation {
                setShippingMethodsOnCart(input: {
                    cart_id: "' . $cartId . '"
                    shipping_methods: [
                        {
                            carrier_code: "' . $method['method_code'] . '"
                            method_code: "' . $method['method_code'] . '"
                        }
                    ]
                }) {
                    cart {
                        shipping_addresses {
                            selected_shipping_method {
                                carrier_code
                                method_code
                                carrier_title
                                method_title
                            }
                        }
                    }
                }
            }
        ');

        $this->graphQlQuery('
            mutation {
                setGuestEmailOnCart(input: {
                    cart_id: "' . $cartId . '"
                    email: "guest@example.com"
                }) {
                    cart {
                        email
                    }
                }
            }
        ');

        $this->graphQlQuery('
            mutation {
                setPaymentMethodOnCart(input: {
                    cart_id: "' . $cartId . '"
                    payment_method: {
                        code: "biller_gateway"
                    }
                }) {
                    cart {
                        selected_payment_method {
                            code
                        }
                    }
                }
            }
        ');

        return $cartId;
    }

    protected function prepareCustomerCart(): string
    {
        /** @var CustomerTokenServiceInterface $customerTokenService */
        $customerTokenService = $this->objectManager->get(CustomerTokenServiceInterface::class);
        $token = $customerTokenService->createCustomerAccessToken('customer@example.com', 'password');

        $cartId = $this->graphQlQuery(
            '{customerCart{id}}',
            [],
            '',
            ['Authorization' => 'Bearer ' . $token]
        )['customerCart']['id'];

        $this->graphQlQuery('
            mutation {
                addSimpleProductsToCart(
                    input: {
                        cart_id: "' . $cartId . '"
                        cart_items: [
                            {
                                data: {
                                    quantity: 1
                                    sku: "simple"
                                }
                            }
                        ]
                    }
                ) {
                    cart {
                        items {
                            id
                        }
                    }
                }
            }
        ');

        $this->graphQlQuery('
            mutation {
                setBillingAddressOnCart(
                    input: {
                        cart_id: "' . $cartId . '"
                        billing_address: {
                            address: {
                                firstname: "John"
                                lastname: "Doe"
                                company: "Acme"
                                street: ["Main St", "123"]
                                city: "Anytown"
                                postcode: "1234AB"
                                country_code: "NL"
                                telephone: "123-456-0000"
                                save_in_address_book: false
                            }
                            use_for_shipping: true
                        }
                    }
                ) {
                    cart {
                        billing_address {
                            firstname
                            lastname
                            company
                            street
                            city
                            postcode
                            telephone
                            country {
                                code
                                label
                            }
                        }
                        shipping_addresses {
                            firstname
                            lastname
                            company
                            street
                            city
                            postcode
                            telephone
                            country {
                                code
                                label
                            }
                        }
                    }
                }
            }
        ');

        $token = $this->graphQlQuery('
            query {
                cart(cart_id: "' . $cartId . '") {
                    shipping_addresses {
                        available_shipping_methods {
                            error_message
                            method_code
                            method_title
                        }
                    }
                }
            }
        ');

        $method = $token['cart']['shipping_addresses'][0]['available_shipping_methods'][0];

        $this->graphQlQuery('
            mutation {
                setShippingMethodsOnCart(input: {
                    cart_id: "' . $cartId . '"
                    shipping_methods: [
                        {
                            carrier_code: "' . $method['method_code'] . '"
                            method_code: "' . $method['method_code'] . '"
                        }
                    ]
                }) {
                    cart {
                        shipping_addresses {
                            selected_shipping_method {
                                carrier_code
                                method_code
                                carrier_title
                                method_title
                            }
                        }
                    }
                }
            }
        ');

        $this->graphQlQuery('
            mutation {
                setPaymentMethodOnCart(input: {
                    cart_id: "' . $cartId . '"
                    payment_method: {
                        code: "biller_gateway"
                    }
                }) {
                    cart {
                        selected_payment_method {
                            code
                        }
                    }
                }
            }
        ');

        return $cartId;
    }
}
