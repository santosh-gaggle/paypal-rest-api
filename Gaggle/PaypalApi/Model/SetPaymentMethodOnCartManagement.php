<?php
/**
 * Copyright © @Gaggle_PaypalApi All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Gaggle\PaypalApi\Model;


use Magento\QuoteGraphQl\Model\Cart\CheckCartCheckoutAllowance;
use Magento\QuoteGraphQl\Model\Cart\SetPaymentMethodOnCart as SetPaymentMethodOnCartModel;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use \Gaggle\PaypalApi\Helper\ApiHelper;
use Magento\PaypalGraphQl\Model\Provider\Config as ConfigProvider;
use Magento\PaypalGraphQl\Model\Provider\Checkout as CheckoutProvider;

class SetPaymentMethodOnCartManagement implements \Gaggle\PaypalApi\Api\SetPaymentMethodOnCartManagementInterface
{

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    private $allowedPaymentMethodCodes = [];

    /**
     * @var SetPaymentMethodOnCartModel
     */
    private $setPaymentMethodOnCart;

    /**
     * @var CheckoutProvider
     */
    private $checkoutProvider;

    /**
     * @var CheckCartCheckoutAllowance
     */
    private $checkCartCheckoutAllowance;

    protected $quoteFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param SetPaymentMethodOnCartModel $setPaymentMethodOnCart
     * @param CheckCartCheckoutAllowance $checkCartCheckoutAllowance
     * @param array $allowedPaymentMethodCodes
     */
    public function __construct(
        SetPaymentMethodOnCartModel $setPaymentMethodOnCart,
        CheckCartCheckoutAllowance $checkCartCheckoutAllowance,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $cartRepository,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        ApiHelper $apiHelper,
        ConfigProvider $configProvider,
        CheckoutProvider $checkoutProvider,
        array $allowedPaymentMethodCodes = []
    ) {
        $this->setPaymentMethodOnCart = $setPaymentMethodOnCart;
        $this->checkCartCheckoutAllowance = $checkCartCheckoutAllowance;
        $this->_storeManager = $storeManager;
        $this->cartRepository = $cartRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->apiHelper = $apiHelper;
        $this->configProvider = $configProvider;
        $this->checkoutProvider = $checkoutProvider;
        $this->allowedPaymentMethodCodes = $allowedPaymentMethodCodes;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentMethodOnCart($cart_id = false, $payment_method = false, $customer_id = null, $payer_id, $token)
    {
        try {
            
            if (!$cart_id) {
                return [[
                        'code' => 400,'message' => 'Required parameter "cart_id" is missing.'
                    ]];
            }

            if (!$payment_method) {

                return [[
                        'code' => 400,'message' => 'Required parameter "payment_method" is missing.'
                    ]];
            }

            $customerId = $customer_id ? (int) $customer_id : $customer_id;

            $paymentData['code'] = $payment_method;
            $paymentData[$payment_method]['payer_id'] = $payer_id;
            $paymentData[$payment_method]['token'] = $token;
            /** @var StoreInterface $store */
            $store = $this->_storeManager->getStore();
            $storeId = (int)$store->getId();

            $cart = $this->apiHelper->getCart($cart_id, $customerId, $storeId);

            if (!$cart->getCustomerEmail()) {
               
               if ($cart->getBillingAddress() && $cart->getBillingAddress()->getEmail()!= null)  {
                   $cart->setCustomerEmail($cart->getBillingAddress()->getEmail());
                  // $cart->save();
               }else{
                    return [[
                        'code' => 404,'message' => "billing address missing."
                    ]];
               }
            }

            $this->checkCartCheckoutAllowance->execute($cart);
            $this->setPaymentMethodOnCart->execute($cart, $paymentData);
            $cart = $this->apiHelper->getCart($cart_id, $customerId, $storeId);

            if (!$this->isAllowedPaymentMethod($paymentData['code'])) {
                return [[
                        'code' => 400,'message' => 'this payment method not allowed'
                    ]];
            }

            $payerId = $paymentData[$payment_method]['payer_id'] ?? null;
            $token = $paymentData[$payment_method]['token'] ?? null;

            if ($payerId && $token) {
                $config = $this->configProvider->getConfig($paymentData['code']);

                $checkout = $this->checkoutProvider->getCheckout($config, $cart);

                try {
                    $checkout->returnFromPaypal($token, $payerId);
                } catch (\Exception $e) {
                    return [[
                        'code' => 400,'message' => $e->getMessage()
                    ]];
                }
            }

            return [[
                    'code' => 200,'selected_payment_method' => ['code' => $cart->getPayment()->getMethod() ?? '','title'=> $cart->getPayment()->getMethodInstance()->getTitle() ?? '' ]
            ]];
        } catch (\Exception $e) {
            
            return [[
                    'code' => 400,'message' => $e->getMessage()
            ]];
        }  
    }

    private function isAllowedPaymentMethod(string $paymentCode): bool
    {

        return !empty($paymentCode) && in_array($paymentCode, $this->allowedPaymentMethodCodes);
    }
}

