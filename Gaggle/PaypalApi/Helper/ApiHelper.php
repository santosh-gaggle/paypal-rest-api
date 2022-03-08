<?php
/**
 * Copyright © @Gaggle_PaypalApi All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Gaggle\PaypalApi\Helper;

use Magento\Quote\Model\Quote;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class ApiHelper extends AbstractHelper
{

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository,
        StoreRepositoryInterface $storeRepository = null
    ) {
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
        $this->storeRepository = $storeRepository ?: ObjectManager::getInstance()->get(StoreRepositoryInterface::class);
        parent::__construct($context);
    }


    public function getCart(string $cartHash, ?int $customerId, int $storeId): Quote
    {

        if (is_numeric($cartHash)) {
            try {
                /** @var Quote $cart */
                $cart = $this->cartRepository->get($cartHash);
            } catch (NoSuchEntityException $e) {
                throw new GraphQlNoSuchEntityException(
                    __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $cartHash])
                );
            }
        }else{

            try {
                $cartId = $this->maskedQuoteIdToQuoteId->execute($cartHash);
                $cart = $this->cartRepository->get($cartId);
            } catch (NoSuchEntityException $exception) {
                throw new GraphQlNoSuchEntityException(
                    __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $cartHash])
                );
            }
        }

        if (false === (bool)$cart->getIsActive()) {
            throw new GraphQlNoSuchEntityException(__('The cart isn\'t active.'));
        }

        $this->updateCartCurrency($cart, $storeId);

        $cartCustomerId = (int)$cart->getCustomerId();

        /* Guest cart, allow operations */
        if (0 === $cartCustomerId && (null === $customerId || 0 === $customerId)) {
            return $cart;
        }

        if ($cartCustomerId !== $customerId) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current user cannot perform operations on cart "%masked_cart_id"',
                    ['masked_cart_id' => $cartHash]
                )
            );
        }
        return $cart;
    }

    private function updateCartCurrency(Quote $cart, int $storeId)
    {
        $cartStore = $this->storeRepository->getById($cart->getStoreId());
        $currentCartCurrencyCode = $cartStore->getCurrentCurrency()->getCode();
        if ((int)$cart->getStoreId() !== $storeId) {
            $newStore = $this->storeRepository->getById($storeId);
            if ($cartStore->getWebsite() !== $newStore->getWebsite()) {
                throw new GraphQlInputException(
                    __('Can\'t assign cart to store in different website.')
                );
            }
            $cart->setStoreId($storeId);
            $cart->setStoreCurrencyCode($newStore->getCurrentCurrency());
            $cart->setQuoteCurrencyCode($newStore->getCurrentCurrency());
        } elseif ($cart->getQuoteCurrencyCode() !== $currentCartCurrencyCode) {
            $cart->setQuoteCurrencyCode($cartStore->getCurrentCurrency());
        } else {
            return;
        }
        $this->cartRepository->save($cart);
    }
}

