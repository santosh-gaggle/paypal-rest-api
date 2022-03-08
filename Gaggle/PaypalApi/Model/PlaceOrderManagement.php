<?php
/**
 * Copyright © @Gaggle_PaypalApi All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Gaggle\PaypalApi\Model;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\CheckCartCheckoutAllowance;
use \Gaggle\PaypalApi\Helper\ApiHelper;

class PlaceOrderManagement implements \Gaggle\PaypalApi\Api\PlaceOrderManagementInterface
{

    public function __construct(
        CheckCartCheckoutAllowance $checkCartCheckoutAllowance,
        StoreManagerInterface $storeManager,
        ApiHelper $apiHelper,
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->checkCartCheckoutAllowance = $checkCartCheckoutAllowance;
        $this->_storeManager = $storeManager;
        $this->apiHelper = $apiHelper;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function placeOrder($cart_id, $customer_id = null)
    {
         if (!$cart_id) {

            return [[
                'code' => 400,'message' => 'Required parameter "cart_id" is missing.'
            ]];
        }

        $store = $this->_storeManager->getStore();
        $storeId = (int)$store->getId();

        $customerId = $customer_id ? (int) $customer_id : $customer_id;
        $cart =$this->apiHelper->getCart($cart_id, $customerId, $storeId);
        $this->checkCartCheckoutAllowance->execute($cart);

        if ((int)$customer_id === 0) {
            if (!$cart->getCustomerEmail()) {

                return [[
                    'code' => 400,'message' => "Guest email for cart is missing."
                ]];
            }
            $cart->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
        }

        try {
            $orderId = $this->cartManagement->placeOrder($cart->getId());
            $order = $this->orderRepository->get($orderId);

            return [[
                'code' => 200,'order_number' => $order->getIncrementId()
            ]];

        } catch (NoSuchEntityException $e) {
            return [[
                'code' => 400,'message' => $e->getMessage()
            ]];
        } catch (LocalizedException $e) {

            return [[
                'code' => 400,'message' => 'Unable to place order:'. $e->getMessage()
            ]];
        }
    }
}

