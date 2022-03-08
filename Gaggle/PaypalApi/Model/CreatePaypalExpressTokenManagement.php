<?php
/**
 * Copyright © @Gaggle_PaypalApi All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Gaggle\PaypalApi\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\PaypalGraphQl\Model\Provider\Checkout as CheckoutProvider;
use Magento\PaypalGraphQl\Model\Provider\Config as ConfigProvider;
use Magento\PaypalGraphQl\Model\Resolver\Store\Url;
use Magento\Framework\Validation\ValidationException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use \Gaggle\PaypalApi\Helper\ApiHelper;

class CreatePaypalExpressTokenManagement implements \Gaggle\PaypalApi\Api\CreatePaypalExpressTokenManagementInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CheckoutProvider
     */
    private $checkoutProvider;

    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;

    /**
     * @var Url
     */
    private $urlService;

    /**
     * @param CheckoutProvider $checkoutProvider
     * @param ConfigProvider $configProvider
     * @param CheckoutHelper $checkoutHelper
     * @param Url $urlService
     */
    public function __construct(
        CheckoutProvider $checkoutProvider,
        ConfigProvider $configProvider,
        CheckoutHelper $checkoutHelper,
        Url $urlService,
        StoreManagerInterface $storeManager,
        ApiHelper $apiHelper
    ) {
        $this->checkoutProvider = $checkoutProvider;
        $this->configProvider = $configProvider;
        $this->checkoutHelper = $checkoutHelper;
        $this->urlService = $urlService;
        $this->_storeManager = $storeManager;
        $this->apiHelper = $apiHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function createPaypalExpressToken($cart_id,$return_url,$cancel_url,$customer_id = null)
    {
        try {
            $response = [];
            $urls = [];
            $paymentCode = 'paypal_express';
            $usePaypalCredit = false;
            $usedExpressButton = false;
            $customerId = $customer_id ? (int) $customer_id : $customer_id;

            /** @var StoreInterface $store */
            $store = $this->_storeManager->getStore();

            $storeId = (int)$store->getId();
            try {

                $cart = $this->apiHelper->getCart($cart_id, $customerId, $storeId);
                
            } catch (\Exception $e) {
                
                return [[
                    'code' => 404,'message' => $e->getMessage()
                ]];
            }
            

            $config = $this->configProvider->getConfig($paymentCode);
            $checkout = $this->checkoutProvider->getCheckout($config, $cart);

            if ($cart->getIsMultiShipping()) {
                $cart->setIsMultiShipping(0);
                $cart->removeAllAddresses();
            }
            $checkout->setIsBml($usePaypalCredit);

            if ($customerId) {
                $checkout->setCustomerWithAddressChange(
                    $cart->getCustomer(),
                    $cart->getBillingAddress(),
                    $cart->getShippingAddress()
                );
            } else {
                if (!$this->checkoutHelper->isAllowedGuestCheckout($cart)) {

                    return [[
                        'code' => 101,'message' => 'Guest checkout is disabled.'
                    ]];
                }
            }

            if ($return_url) {
                
                $urls['return_url'] = $return_url;
            }

            if ($cancel_url) {
                
                $urls['cancel_url'] = $cancel_url;
            }

            if (!empty($urls)) {
                $args['input']['urls'] = $this->validateAndConvertPathsToUrls($urls, $store);
            }

            $checkout->prepareGiropayUrls(
                $args['input']['urls']['success_url'] ?? '',
                $args['input']['urls']['cancel_url'] ?? '',
                $args['input']['urls']['pending_url'] ?? ''
            );
                 
            $token = $checkout->start(
                $args['input']['urls']['return_url'] ?? '',
                $args['input']['urls']['cancel_url'] ?? '',
                $usedExpressButton
            );
        } catch (LocalizedException $e) {

            return [[
                'code' => 400,'message' => $e->getMessage()
            ]];
        }

        return [[
            'code' => 200,
            'token' => $token,
            'paypal_urls' => [
                'start' => $checkout->getRedirectUrl(),
                'edit' => $config->getExpressCheckoutEditUrl($token)
            ]
        ]];
    }

    /**
     * Validate and convert to redirect urls from given paths
     *
     * @param string $paths
     * @param StoreInterface $store
     * @return array
     */
    private function validateAndConvertPathsToUrls($paths, StoreInterface $store): array
    {
        $urls = [];
        foreach ($paths as $key => $path) {
            try {
                $urls[$key] = $this->urlService->getUrlFromPath($path, $store);
            } catch (ValidationException $e) {
                throw new GraphQlInputException(__($e->getMessage()), $e);
            }
        }
        return $urls;
    }
}

